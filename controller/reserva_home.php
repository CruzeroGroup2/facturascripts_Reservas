<?php

require_model('reserva.php');

require_once 'reserva_habitacion.php';


class reserva_home extends fs_controller {

    protected $reserva = null;

    protected $grupos_cliente = null;

    protected $tarifa = null;

    protected $estado = null;

    protected $categoria_habitacion = null;

    protected $habitacion = null;

    public function __construct() {
        parent::__construct(__CLASS__, "Reserva", "Reserva");
    }

    protected function process() {
        $action = (string)isset($_GET['action']) ? $_GET['action'] : 'list';
        $this->reserva = new reserva();
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

    public function new_url($step=1) {
        $this->page->extra_url = '&action=add';

        if(is_int($step) && $step > 1 ) {
            $this->page->extra_url = $this->page->extra_url.'&step='.$step;
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
        $this->reserva = new reserva();
        $this->reservas = $this->reserva->fetchAll();
        $this->template = 'reserva_home_index';
    }

    public function addAction() {
        $this->page->extra_url = '&action=add';
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        switch($step) {
            default:
            case 1:
                $this->template = 'reserva_new_step1';
                break;
            case 2:
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->reserva->setValues($_POST);
                    //Obtengo la tarifa de la reserva de acuerdo a los paramatros
                    $this->reserva->setTarifa(
                        $this->tarifa->fetchByCategoriaYTipoPasajero(
                            $_POST['idcategoria'],
                            $this->reserva->getCodGrupoCliente()
                        ));
                    //Si la reserva está incompleta la marcamos como sin seña
                    if($this->reserva->getEstado()->getDescripcion() == estado_reserva::INCOMPLETA) {
                        $this->reserva->setEstado(estado_reserva::get(estado_reserva::SINSENA));
                    }
                    if ($this->reserva->save()) {
                        $this->new_message("Reserva agregada correctamente!.");
                    } else {
                        $this->new_error_msg("¡Imposible agregar Reserva!");
                    }
                }
                $this->template = 'reserva_new_step2';
                break;
            case 3:
                $this->reserva = reserva::get($_POST['idreserva']);
                $this->reserva->setValues($_POST);
                if ($this->reserva->save()) {
                    $this->new_message("Reserva actualizada correctamente!.");
                } else {
                    $this->new_error_msg("¡Imposible actualizar Reserva!");
                }
                $this->template = 'reserva_new_step3';
                break;
            case 4:
                $this->reserva = reserva::get($_POST['idreserva']);
                $this->reserva->setValues($_POST);
                if ($this->reserva->save()) {
                    $this->new_message("Reserva actualizada correctamente!.");
                } else {
                    $this->new_error_msg("¡Imposible actualizar Reserva!");
                }
                $this->template = 'reserva_new_step3';
                break;
        }
    }

    public function editAction() {
        $this->page->extra_url = '&action=edit';
        $id = (int)isset($_GET['id']) ? $_GET['id'] : 0;
        $this->reserva = reserva::get($id);
        switch($this->reserva->getEstado()->getDescripcion()) {
            case estado_reserva::INCOMPLETA:
                //Volver a la seleccion de habitaciones
                $this->template = 'reserva_new_step2';
                break;
            case estado_reserva::SINSENA:
                //Volver a la modificacion de pasajeros
                $this->template = 'reserva_new_step3';
                break;
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
        $this->reserva = reserva::get($id);
        if($this->reserva && $this->reserva->delete()) {
            $this->new_message("Reserva eliminada correctamente!.");
        } else {
            $this->new_error_msg("¡Imposible eliminar Reserva!");
        }
        $this->indexAction();
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
}