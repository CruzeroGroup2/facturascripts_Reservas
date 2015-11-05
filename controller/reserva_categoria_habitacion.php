<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 27/12/2014
 * Time: 07:57 PM
 */

require_model('categoria_habitacion.php');
require_model('habitacion.php');

require_once 'reserva_controller.php';

class reserva_categoria_habitacion extends reserva_controller {

    protected $categoria_habitacion = null;

    public function __construct() {
        parent::__construct(__CLASS__, "Categoria Habitacion", "Reserva");
    }

    protected function process() {
        $this->action = (string) isset($_GET['action']) ? $_GET['action']: 'list';

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
            case 'delete':
                $this->deleteAction();
                break;
        }
    }

    public function new_url() {
        $this->page->extra_url = '&action=add';
        return $this->url();
    }

    public function edit_url(categoria_habitacion $categoria_habitacion) {
        $this->page->extra_url = '&action=edit&id=' . (int) $categoria_habitacion->getId();
        return $this->url();
    }

    public function delete_url(categoria_habitacion $categoria_habitacion) {
        $this->page->extra_url = '&action=delete&id=' . (int) $categoria_habitacion->getId();
        return $this->url();
    }

    public function anterior_url() {
        return '';
    }

    public function siguiente_url() {
        return '';
    }

    public function indexAction() {
        $this->categoria_habitacion = new categoria_habitacion();
        $this->categorias_habitacion = $this->categoria_habitacion->fetchAll();
        $this->template = 'reserva_categoria_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        $this->categoria_habitacion = new categoria_habitacion();
        $this->template = 'reserva_categoria_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->categoria_habitacion->setValues($_POST);
            if ($this->categoria_habitacion->save()) {
                $this->new_message("Categoría Habitacion agregado correctamente!.");
            } else {
                $this->new_error_msg("¡Imposible agregar Categoría Habitacion!");
            }
        }
    }

    public function editAction() {
        $this->page->extra_url = '&action=edit';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->categoria_habitacion = categoria_habitacion::get($id);
        $this->template = 'reserva_categoria_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->categoria_habitacion->setValues($_POST);
            if ($this->categoria_habitacion->save()) {
                $this->new_message("Categoría Habitacion actualizada correctamente!.");
            } else {
                $this->new_error_msg("¡Imposible actualizar Categoría Habitacion!");
            }
        }
    }

    public function findAction() {

    }

    public function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->categoria_habitacion = categoria_habitacion::get($id);
        $habitacion = new habitacion();
        //TODO: Agregar mensaje de error cuando una serie de habitaciones está usando una categoría
        if($this->categoria_habitacion && $this->categoria_habitacion->delete()) {
            $this->new_message("Categoría Habitacion eliminado correctamente!.");
        } else {
            $this->new_error_msg("¡Imposible eliminar Categoría Habitacion!");
        }
        $this->indexAction();
    }

    public function getCategoriaHabitacion() {
        return $this->categoria_habitacion;
    }

}