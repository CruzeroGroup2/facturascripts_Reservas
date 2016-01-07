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
        $this->action = (string)isset($_GET['action']) ? $_GET['action'] : 'list';
        $this->pabellon = new pabellon();

        switch ($this->action) {
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
        $this->pabellones = $this->pabellon->fetchAll();
        $this->template = 'reserva_pabellon_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        $this->template = 'reserva_pabellon_form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->pabellon->setValues($_POST);
            if ($this->pabellon->save()) {
                $this->new_message("Pabellón agregado correctamente!.");
            } else {
                $this->new_error_msg("¡Imposible agregar Pabellón!");
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
                $this->new_message("Pabellón actualizado correctamente!.");
            } else {
                $this->new_error_msg("¡Imposible actualizar pabellón!");
            }
        }
    }

    public function findAction() {
        $by = isset($_GET['by']) ? $_GET['by'] : false;
        $value = isset($_REQUEST['value']) ? $_REQUEST['value'] : null;
        switch($by) {
            case 'idcategoria':
                $data = $this->pabellon->fetchAllByIdCategoria($value);
                break;
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        $this->template = false;
    }

    public function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $this->pabellon = pabellon::get($id);
        if($this->pabellon && $this->pabellon->delete()) {
            $this->new_message("Pabellón actualizado correctamente!.");
        } else {
            $this->new_error_msg("¡Imposible eliminar pabellón!");
        }
        $this->indexAction();
    }

    public function getPabellon() {
        return $this->pabellon;
    }
}