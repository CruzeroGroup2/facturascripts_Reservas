<?php

require_model('reserva.php');

require_once 'reserva_habitacion.php';


class reserva_home extends fs_controller {

    /**
     * @var reserva
     */
    protected $reserva = null;

    /**
     * @var pabellon
     */
    protected $pabellon = null;

    /**
     * @var grupo_clientes
     */
    protected $grupos_cliente = null;

    /**
     * @var tarifa_reserva
     */
    protected $tarifa = null;

    /**
     * @var estado_reserva
     */
    protected $estado = null;

    /**
     * @var categoria_habitacion
     */
    protected $categoria_habitacion = null;

    /**
     * @var habitacion
     */
    protected $habitacion = null;

    protected $fecha_desde = null;

    protected $fecha_hasta = null;

    public function __construct() {
        parent::__construct(__CLASS__, "Reserva", "Reserva");
    }

    protected function process() {
        $action = (string)isset($_GET['action']) ? $_GET['action'] : 'list';
        $this->reserva = new reserva();
        $this->pabellon = new pabellon();
        $this->categoria_habitacion = new categoria_habitacion();
        $this->habitacion = new habitacion();
        $this->grupos_cliente = new grupo_clientes();
        $this->tarifa = new tarifa_reserva();
        $this->estado = new estado_reserva();
        switch ($action) {
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
        }
    }

    public function list_url() {
        $this->page->extra_url = '&action=list';

        return $this->url();
    }

    public function new_url() {
        $this->page->extra_url = '&action=add';

        $args = func_get_args();
        if(!isset($args[0])) {
            $args[0] = false;
        }

        if(is_int($args[0]) && $args[0] > 1 ) {
            $this->page->extra_url .= '&step='.$args[0];
        }

        if(is_a($args[0], 'habitacion') && is_a($args[1], 'DateTime')) {
            $this->page->extra_url .= '&fecha_in='.$args[1]->format('Y-m-d').'&idsHabitaciones='.$args[0]->getId();
        }

        return $this->url();
    }

    public function edit_url(reserva $reserva) {
        $this->page->extra_url = '&action=edit&id=' . (int)$reserva->getId();

        return $this->url();
    }

    public function delete_url(reserva $reserva) {
        $this->page->extra_url = '&action=delete&id=' . (int)$reserva->getId();

        return $this->url();
    }

    public function anterior_url() {
        return '';
    }

    public function siguiente_url() {
        return '';
    }

    public function indexAction() {
        $this->page->extra_url = '';
        $this->fecha_desde = ( isset( $_POST['fecha_desde'] ) ) ? $_POST['fecha_desde'] : date('Y-m-d');
        $this->fecha_hasta = ( isset( $_POST['fecha_hasta'] ) ) ? $_POST['fecha_hasta'] : date('Y-m-d');
        $this->rango_fechas = new DatePeriod(
            new DateTime($this->fecha_desde),
            DateInterval::createFromDateString('+1 day'),
            //Ugly hack to include the frist day
            new DateTime($this->fecha_hasta . ' 23:59:59')
        );

        $this->cantidad_dias = 1;
        foreach($this->rango_fechas as $dt) {
            $this->cantidad_dias++;
        }

        $this->pabellones = $this->pabellon->fetchAll();
        $this->reservas = $this->reserva->fetchAll();
        $this->template = 'reserva_home_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        if(isset($_GET['fecha_in'])) {
            $this->fecha_desde = $_GET['fecha_in'];
            $this->reserva->setFechaIn($this->getFechaDesde());
        }
        if(isset($_GET['idsHabitaciones'])) {
            $idhabitacion = $_GET['idsHabitaciones'];
            $this->reserva->setHabitaciones(array($idhabitacion));
        }
        //Si tengo un request por post levantar la reserva
        // actualizar los datos y guardar, mostrar mensage con respecto a la accion realizada
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ( isset( $_POST['idreserva'] ) ) {
                $this->reserva = reserva::get( $_POST['idreserva'] );
            }
            $this->reserva->setValues( $_POST );
            if ($this->reserva->save()) {
                if($this->reserva->getStep() > 2) {
                    $this->new_message($this->reserva->getSuccesMessage());
                }
            } else {
                $this->new_error_msg("¡Error en Reserva!");
            }
        }

        $this->step = $this->reserva->getStep();
        switch($this->step) {
            default:
            case 1:
                $this->template = 'reserva_new_step1';
                break;
            case 2:
                if(!$this->get_errors()) {
                    $this->template = 'reserva_new_step2';
                } else {
                    //Volvemos al paso 1
                    $this->template = 'reserva_new_step1';
                }
                break;
            case 3:
                if(!$this->get_errors()) {
                    $this->template = 'reserva_new_step3';
                } else {
                    //Volvemos al paso 2
                    $this->template = 'reserva_new_step2';
                }
                break;
            case 4:
                $this->template = 'reserva_new_step3';
                break;
        }
    }

    public function editAction() {
        $this->page->extra_url = '&action=edit';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $this->reserva = reserva::get($id);
        if(!$this->reserva) {
            $this->reserva = new reserva();
            return $this->indexAction();
        }
        $estado = $this->reserva->getEstado()->getDescripcion();
        $habitaciones = $this->reserva->getHabitaciones();
        //Si la reserva está incompleta y no hay habitaciones
        if($estado == estado_reserva::INCOMPLETA && !$habitaciones) {
            //Volver a la seleccion de habitaciones
            $this->template = 'reserva_new_step2';
        //Si la reserva está incompleta o sin seña pero hay habitaciones
        } elseif(($estado == estado_reserva::INCOMPLETA && $habitaciones) ||
                 $estado == estado_reserva::SINSENA) {
            //Volver a la modificacion de pasajeros
            $this->template = 'reserva_new_step3';
        } else {
            $this->template = 'reserva_new_step3';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->reserva->setValues($_POST);
            if ($this->reserva->save()) {
                $this->new_message("Reserva actualizado correctamente!.");
            } else {
                $this->new_error_msg("¡Imposible actualizar Reserva!");
            }
        }
    }

    public function findAction() {
    }

    public function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        switch($type) {
            case 'pasajero':
                $this->template = 'ajax/reserva_remover_pasajero';
                $pasajero = pasajero_por_reserva::get($id);
                if($pasajero && $pasajero->delete()) {
                    $this->new_message("Pasajero eliminado corectamente!");
                } else {
                    $this->new_error_msg("Error al eliminar pasajero!");
                }
                break;
            case 'reserva':
            default:
                $this->reserva = reserva::get($id);
                if($this->reserva && $this->reserva->delete()) {
                    $this->new_message("Reserva eliminada correctamente!.");
                } else {
                    $this->new_error_msg("¡Imposible eliminar Reserva!");
                }
                $this->indexAction();
        }
    }

    public function getHabitacion() {
        return $this->habitacion;
    }

    public function getReserva() {
       return $this->reserva;
    }

    public function getEstadoReserva() {
        return $this->estado;
    }

    public function getGruposCliente() {
        return $this->grupos_cliente;
    }

    public function getCategoriaHabitacion() {
        return $this->categoria_habitacion;
    }

    public function getTarifa() {
        return $this->tarifa;
    }

    public function getFechaDesde(){
        return $this->fecha_desde;
    }

    public function getFechaHasta() {
        return $this->fecha_hasta;
    }
    public function getHabitacionCell(habitacion $habitacion, DateTime $fecha) {
        $reserva = false;
        $reservas = $this->reserva->findByHabitacionYFecha($habitacion->getId(), $fecha->format('Y-m-d'));
        if(isset($reservas[0])) {
            $reserva = $reservas[0];
        }

        //Si hay reserva y no hay seña
        if($reserva && ($reserva->getEstado()->getDescripcion() === estado_reserva::INCOMPLETA ||
                        $reserva->getEstado()->getDescripcion() === estado_reserva::SINSENA)) {
            $cssClass="reservada";
        } elseif($reserva && ($reserva->getEstado()->getDescripcion() === estado_reserva::SENADO ||
                              $reserva->getEstado()->getDescripcion() === estado_reserva::PAGO)) {
            $cssClass="reservada_senia";
            //Si hay check_in está ocupada
        } elseif($reserva && $reserva->getCheckIn()) {
            $cssClass = "ocupada";
        } else {
            //Si no hay reserva la habitacion está
            $cssClass = "disponible";
        }

        $cell = '<td style="padding: 0;" class"' . $cssClass . '">';
        if($reserva) {
            $href = $this->edit_url($reserva);
        } else {
            $href = $this->new_url($habitacion, $fecha);
        }
        $cell .='<a href="'.$href.'" style="width: 100%;" class="btn '.$cssClass.'">&nbsp;</a>';

        $cell .= '</td>';
        return $cell;
    }

}