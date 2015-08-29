<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 27/12/2014
 * Time: 05:51 PM
 */

require_model('habitacion.php');
require_model('pabellon.php');
require_model('categoria_habitacion.php');
require_model('estado_habitacion.php');

require_once 'reserva_controller.php';

class reserva_habitacion extends reserva_controller {

    /**
     * @var habitacion
     */
    protected $habitacion = null;

    /**
     * @var pabellon
     */
    protected $pabellon = null;

    /**
     * @var categoria_habitacion
     */
    protected $categoria_habitacion = null;

    /**
     * @var estado_habitacion
     */
    protected $estado = null;

    public function __construct() {
        parent::__construct(__CLASS__, "Habitacion", "Reserva");
    }
    protected function process() {
        $action = (string) isset($_GET['action']) ? $_GET['action']: 'list';

        switch($action) {
            default:
            case 'list':
                $this->indexAction();
                break;
            case 'add':
                $this->addAction();
                break;
            case 'edit':
                $this->editAction();
                break;
            case 'delete':
                $this->deleteAction();
                break;
            case 'find':
                $this->findAction();
                break;
        }
    }

    public function new_url() {
        $this->page->extra_url = '&action=add';
        return $this->url();
    }

    public function edit_url(habitacion $habitacion) {
        $this->page->extra_url = '&action=edit&id=' . (int) $habitacion->getId();
        return $this->url();
    }

    public function delete_url(habitacion $habitacion) {
        $this->page->extra_url = '&action=delete&id=' . (int) $habitacion->getId();
        return $this->url();
    }

    public function find_url() {
        $this->page->extra_url = '&action=find';
        return $this->url();
    }

    public function anterior_url() {
        return '';
    }

    public function siguiente_url() {
        return '';
    }

    public function indexAction() {
        $this->habitacion = new habitacion();
        $this->habitaciones = $this->habitacion->fetchAll();
        $this->template = 'reserva_habitacion_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        $this->habitacion = new habitacion();
        $this->pabellon = new pabellon();
        $this->categoria_habitacion = new categoria_habitacion();
        $this->estado = new estado_habitacion();
        $this->template = 'reserva_habitacion_form';
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->habitacion->setValues($_POST);
            if ($this->habitacion->save()) {
                $this->new_message("Habitacion agregado correctamente!.");
            } else {
                $this->new_error_msg("ˇImposible agregar Tarifa!");
            }
        }
    }

    public function editAction() {
        $this->page->extra_url = '&action=edit';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->habitacion = habitacion::get($id);
        $this->template = 'reserva_habitacion_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->habitacion->setValues($_POST);
            if ($this->habitacion->save()) {
                $this->new_message("Habitacion actualizadp correctamente!.");
            } else {
                $this->new_error_msg("ˇImposible actualizar Habitacion!");
            }
        }
    }

    public function findAction() {
        $this->habitacion = new habitacion();
        $this->categoria_habitacion = new categoria_habitacion();
        $adultos = isset($_POST['cantidad_adultos']) ? intval($_POST['cantidad_adultos']) : 0;
        $menores = isset($_POST['cantidad_menores']) ? intval($_POST['cantidad_menores']) : 0;
        $minGuest = isset($_POST['cantidad_por_habitacion']) ? intval($_POST['cantidad_por_habitacion']) : 2;
        $arrival = isset($_POST['fecha_in']) ? $_POST['fecha_in'] : date('Y-m-d');
        $departure = isset($_POST['fecha_out']) ? $_POST['fecha_out'] : date('Y-m-d');

        $origGuestAmount = $adultos + $menores;
        $habitacionesDisponibles = $this->habitacion->findByAmount($minGuest, $arrival, $departure);
        $result = array();
        $guestAmount = $origGuestAmount;
        for($i=0; $i <= 10; $i++) {
            //
            if(!isset($result[$i])) {
                $result[$i] = array('categorias' => array(), 'habitaciones' => array());
            }

            foreach($habitacionesDisponibles as $pos => $habitacion) {
                $maxPer = (int) $habitacion['plaza_maxima'];
                $cantHab = (int) $habitacion['cantidadHabitaciones'];
                $roomsNeeded = ceil($guestAmount / $maxPer);

                // I need only one room or a subset of those
                if($guestAmount <= $maxPer || ($roomsNeeded <= $cantHab)) {
                    //extract the ids from the list that are available
                    //append solution to result list
                    $solucion = $this->__extractHabitacion($habitacionesDisponibles, $pos, $roomsNeeded);
                    $result[$i]['categorias'][] = $solucion['idcategoria'];
                    $result[$i]['habitaciones'][] = $solucion;
                    break;
                } else {
                    //Amount of persons fits, but I don't have the amount of hab available
                    if($roomsNeeded > $cantHab) {
                        $result[$i]['categorias'][] = $habitacion['idcategoria'];
                        $result[$i]['habitaciones'][] = $habitacion;
                        unset($habitacionesDisponibles[$pos]);
                        //I deduct the amount that I can fit in and continue with the
                        $guestAmount -= ($maxPer * $cantHab);
                    } else {
                        break;
                    }
                }
            }
            //Reset guest value
            $guestAmount = $origGuestAmount;
            $i++;
        }
        $this->validateResults($result, $guestAmount);
        $this->result = $result;
        $this->template = 'ajax/reserva_habitaciones_disponibles';
    }

    /** extract one room from  */
    private function __extractHabitacion(&$habList, $pos, $amount = 1) {
        $result = array();
        $solution = $habList[$pos];
        //
        $idsHabitaciones = explode(',', $solution['idsHabitacion']);
        //Verify that I can remove that many
        if(count($idsHabitaciones) < $amount) {
            return false;
        }
        //Extract as many as $amount from $idsHabitaciones
        $newIds = array_slice($idsHabitaciones, 0, $amount);
        //Update the result
        $result['cantidadHabitaciones'] = count($newIds);
        $result['plaza_maxima'] = $solution['plaza_maxima'];
        $result['idsHabitacion'] = implode(',', $newIds);
        $result['idcategoria'] = $solution['idcategoria'];
        //Update the item in $habitacionesDisponibles with the remaining ids
        $remIds = array_slice($idsHabitaciones, $amount);
        if(!empty($remIds)) {
            $habList[$pos]['cantidadHabitaciones'] = count($remIds);
            $habList[$pos]['idsHabitacion'] = implode(',', $remIds);
        } else {
            //remove if there are no more rooms available
            unset($habList[$pos]);
        }
        return $result;
    }

    protected function validateResults(array &$results, $guestAmount) {
        foreach($results as $i => $result) {

            $total = 0;
            $ids = '';
            foreach($result['habitaciones'] as $habitaciones) {
                $ids = $habitaciones['idsHabitacion'] . ',' . $ids;
                $total += ((int) $habitaciones['cantidadHabitaciones'] * (int) $habitaciones['plaza_maxima']);
            }
            if($total < $guestAmount) {
                unset($results[$i]);
            } else {
                $results[$i]['categorias'] = implode(',', array_unique($result['categorias']));
                $results[$i]['total'] = $total;
                $results[$i]['ids'] = rtrim($ids,',');
            }
        }
    }

    public function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->tarifa = tarifa_reserva::get($id);
        if($this->tarifa && $this->tarifa->delete()) {
            $this->new_message("Tarifa eliminado correctamente!.");
        } else {
            $this->new_error_msg("ˇImposible eliminar Tarifa!");
        }
        $this->indexAction();
    }

    /**
     * @return habitacion
     */
    public function getHabitacion() {
        return $this->habitacion;
    }

    /**
     * @return pabellon
     */
    public function getPabellon() {
        return $this->pabellon;
    }

    /**
     * @return categoria_habitacion
     */
    public function getCategoriaHabitacion() {
        return $this->categoria_habitacion;
    }

    /**
     * @return estado_habitacion
     */
    public function getEstado() {
        return $this->estado;
    }

    /**
     * @param $delimiter
     * @param $string
     * @return array
     */
    public function explode($delimiter, $string) {
        return explode($delimiter, $string);
    }

    /**
     * @param int $idcategoria
     * @return string
     */
    public function getCategoriaName($idcategoria) {
        return $this->getCategoriaHabitacion()->get($idcategoria)->getNombre();
    }

}