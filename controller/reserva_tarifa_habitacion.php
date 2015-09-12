<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 27/12/2014
 * Time: 04:21 PM
 */

require_model('tarifa_reserva.php');

require_once 'reserva_controller.php';

class reserva_tarifa_habitacion extends reserva_controller {

    /**
     * @var tarifa_reserva
     */
    protected $tarifa = null;

    /**
     * @var categoria_habitacion
     */
    protected $categoria_habitacion = null;

    /**
     * @var grupo_clientes
     */
    protected $grupo_clientes = null;

    public function __construct() {
        parent::__construct(__CLASS__, "Tarifa", "Reserva");
    }

    protected function process() {
        $this->action = (string) isset($_GET['action']) ? $_GET['action']: 'list';
        $this->tarifa = new tarifa_reserva();
        $this->grupo_clientes = new grupo_clientes();
        $this->categoria_habitacion = new categoria_habitacion();

        switch($this->action) {
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
            case 'find':
                $this->findAction();
                break;
            case 'delete':
                $this->deleteAction();
                break;
        }
    }

    public function new_url() {
        $this->page->extra_url = '&action=add';
        return $this->url();
    }

    public function edit_url(tarifa_reserva $tarifa) {
        $this->page->extra_url = '&action=edit&id=' . (int) $tarifa->getId();
        return $this->url();
    }

    public function delete_url(tarifa_reserva $tarifa) {
        $this->page->extra_url = '&action=delete&id=' . (int) $tarifa->getId();
        return $this->url();
    }

    public function anterior_url() {
        return '';
    }

    public function siguiente_url() {
        return '';
    }

    public function indexAction() {
        $this->tarifas = $this->tarifa->fetchAll();
        $this->template = 'reserva_tarifa_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        $this->template = 'reserva_tarifa_form';
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->tarifa->setValues($_POST);
            if ($this->tarifa->save()) {
                $this->new_message("Tarifa agregada correctamente!");
            } else {
                $this->new_error_msg("¡Imposible agregar Tarifa!");
            }
        }
    }

    public function editAction() {
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->tarifa = tarifa_reserva::get($id);
        $this->page->extra_url = '&action=edit&id=' . (int) $this->tarifa->getId();;
        $this->template = 'reserva_tarifa_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->tarifa->setValues($_POST);
            $this->tarifa->setEdit(true);
            if ($this->tarifa->save()) {
                $this->new_message("Tarifa actualizada correctamente!");
            } else {
                $this->new_error_msg("¡Imposible actualizar Tarifa!");
            }
        }
    }

    public function findAction() {
        $this->page->extra_url = '&action=find';
        $idcategoria =  (int) isset($_POST['idcategoria']) ? $_POST['idcategoria'] : 0;
        $codgrupo =  (int) isset($_POST['codgrupo']) ? $_POST['codgrupo'] : 0;
        $idtarifa =  (int) isset($_POST['idtarifa']) ? $_POST['idtarifa'] : 0;

        if($idcategoria && $codgrupo) {
            $tarifa = $this->tarifa->fetchByCategoriaYTipoPasajero($idcategoria, $codgrupo);
        }

        if($idtarifa) {
            $tarifa = $this->tarifa->get($idtarifa);
        }
        $this->template = FALSE;
        header('Content-Type: application/json');
        echo json_encode($tarifa->__toArray());
    }

    public function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->tarifa = tarifa_reserva::get($id);
        if($this->tarifa && $this->tarifa->delete()) {
            $this->new_message("Tarifa eliminada correctamente!.");
        } else {
            $this->new_error_msg("¡Imposible eliminar Tarifa!");
        }
        $this->indexAction();
    }

    public function getTarifa() {
        return $this->tarifa;
    }

    public function getGruposCliente() {
        return $this->grupo_clientes;
    }

    public function getCategoriaHabitacion() {
        return $this->categoria_habitacion;
    }

}