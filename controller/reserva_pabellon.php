<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 27/12/2014
 * Time: 06:36 PM
 */

require_model('pabellon.php');

require_once 'reserva_controller.php';

class reserva_pabellon extends reserva_controller {

    protected $pabellon;

    public function __construct() {
        parent::__construct(__CLASS__, "Pabellon", "Reserva");
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

    public function edit_url(pabellon $pabellon) {
        $this->page->extra_url = '&action=edit&id=' . (int)$pabellon->getId();

        return $this->url();
    }

    public function delete_url(pabellon $pabellon) {
        $this->page->extra_url = '&action=delete&id=' . (int)$pabellon->getId();

        return $this->url();
    }

    public function anterior_url() {
        return '';
    }

    public function siguiente_url() {
        return '';
    }

    public function indexAction() {
        $this->pabellon = new pabellon();
        $this->pabellones = $this->pabellon->fetchAll();
        $this->template = 'reserva_pabellon_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        $this->pabellon = new pabellon();
        $this->template = 'reserva_pabellon_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->pabellon->setValues($_POST);
            if ($this->pabellon->save()) {
                $this->new_message("Pabell�n agregado correctamente!.");
            } else {
                $this->new_error_msg("�Imposible agregar Pabell�n!");
            }
        }
    }

    public function editAction() {
        $this->page->extra_url = '&action=edit';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $this->pabellon = pabellon::get($id);
        $this->template = 'reserva_pabellon_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->pabellon->setValues($_POST);
            if ($this->pabellon->save()) {
                $this->new_message("Pabell�n actualizado correctamente!.");
            } else {
                $this->new_error_msg("�Imposible actualizar pabell�n!");
            }
        }
    }

    public function findAction() {

    }

    public function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $this->pabellon = pabellon::get($id);
        if($this->pabellon && $this->pabellon->delete()) {
            $this->new_message("Pabell�n actualizado correctamente!.");
        } else {
            $this->new_error_msg("�Imposible eliminar pabell�n!");
        }
        $this->indexAction();
    }

    public function getPabellon() {
        return $this->pabellon;
    }
}