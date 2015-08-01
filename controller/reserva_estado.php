<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 27/12/2014
 * Time: 06:36 PM
 */

require_model('estado_habitacion.php');

class reserva_estado extends fs_controller {

    protected $estado;

    public function __construct() {
        parent::__construct(__CLASS__, "Estado", "Reserva");
    }

    protected function process() {
        $action = (string)isset($_GET['action']) ? $_GET['action'] : 'list';

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

    public function new_url() {
        $this->page->extra_url = '&action=add';

        return $this->url();
    }

    public function edit_url(estado_habitacion $estado) {
        $this->page->extra_url = '&action=edit&id=' . (int)$estado->getId();

        return $this->url();
    }

    public function delete_url(estado_habitacion $estado) {
        $this->page->extra_url = '&action=delete&id=' . (int)$estado->getId();

        return $this->url();
    }

    public function anterior_url() {
        return '';
    }

    public function siguiente_url() {
        return '';
    }

    public function indexAction() {
        $this->estado = new estado_habitacion();
        $this->estados = $this->estado->fetchAll();
        $this->template = 'reserva_estado_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        $this->estado = new estado_habitacion();
        $this->template = 'reserva_estado_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->estado->setValues($_POST);
            if ($this->estado->save()) {
                $this->new_message("Estado agregado correctamente!.");
            } else {
                $this->new_error_msg("¡Imposible agregar Estado!");
            }
        }
    }

    public function editAction() {
        $this->page->extra_url = '&action=edit';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $this->estado = estado_habitacion::get($id);
        $this->template = 'reserva_estado_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->estado->setValues($_POST);
            if ($this->estado->save()) {
                $this->new_message("Estado actualizado correctamente!.");
            } else {
                $this->new_error_msg("¡Imposible actualizar estado_habitacion!");
            }
        }
    }

    public function findAction() {

    }

    public function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $this->estado = estado_habitacion::get($id);
        if($this->estado && $this->estado->delete()) {
            $this->new_message("Estado actualizado correctamente!.");
        } else {
            $this->new_error_msg("¡Imposible eliminar estado_habitacion!");
        }
        $this->indexAction();
    }

    public function getEstado() {
        return $this->estado;
    }
}