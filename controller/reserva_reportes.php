<?php

require_model('reserva.php');

require_once 'reserva_controller.php';

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 12/08/2015
 * Time: 09:36 PM
 */
class reserva_reportes extends reserva_controller {

    /**
     * @var string
     */
    protected $fecha;

    public function __construct() {
        parent::__construct(__CLASS__, "Informes", "Reserva");
    }

    protected function process() {
        $action = (string) isset($_GET['action']) ? $_GET['action'] : 'list';
        switch($action) {
            case 'reservasSinSenia':
                $this->reservasSinSinia();
                break;
            case 'ocupacion':
                $this->ocupacion();
                break;
            case 'habitaciones':
                $this->habitaciones();
                break;

        }
    }

    public function sinsenia_url() {
        $this->page->extra_url = '&action=reservasSinSenia';

        return $this->url();
    }

    public function ocupacion_url() {
        $this->page->extra_url = '&action=ocupacion';

        return $this->url();
    }

    public function habitaciones_url() {
        $this->page->extra_url = '&action=habitaciones';

        return $this->url();
    }

    private function reservasSinSinia() {
        $reserva = new reserva();
        $estado = estado_reserva::get(estado_reserva::SINSENA);
        $this->reservas = $reserva->findByEstado($estado->getId());
        $this->template = 'reserva_reportes_sinsenia';
    }

    private function ocupacion() {
        $this->fecha = date('d-m-Y');
        $this->template = 'reserva_reportes_ocupacion';
    }

    private function habitaciones() {
        $this->fecha = date('d-m-Y');
        $this->template = 'reserva_reportes_habitaciones';
    }

    public function getFecha() {
        return $this->fecha;
    }

    public function getPorcentajeOcupacion() {

    }
    public function getCantidadDePersonas() {

    }
}