<?php

require_model('reserva.php');
require_model('pasajero_por_reserva.php');

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

    /**
     * @var pabellon
     */
    protected $pabellon = null;

    public function __construct() {
        parent::__construct(__CLASS__, "Informes", "Reserva");
    }

    protected function process() {
        $this->action = (string) isset($_GET['action']) ? $_GET['action'] : 'list';
        $export = isset($_GET['export']) ? boolval($_GET['export']) : false;
        $this->reserva = new reserva();
        $this->pabellon = new pabellon();
        switch($this->action) {
            case 'reservasSinSenia':
                $this->reservasSinSenia($export);
                break;
            case 'reservasConSenia':
                $this->reservasConSenia($export);
                break;
            case 'ocupacion':
                $this->ocupacion($export);
                break;
            case 'raciones':
                $this->raciones($export);
                break;
            case 'habitaciones':
                $this->habitaciones($export);
                break;
            case 'ingresos':
                $this->ingresos($export);
                break;
        }
    }

    public function sinsenia_url() {
        $this->page->extra_url = '&action=reservasSinSenia';

        return $this->url();
    }

    public function consenia_url() {
        $this->page->extra_url = '&action=reservasConSenia';

        return $this->url();
    }

    public function raciones_url() {
        $this->page->extra_url = '&action=raciones';

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

    public function ingresos_url() {
        $this->page->extra_url = '&action=ingresos';

        return $this->url();
    }

    public function proyeccion_url() {
        $this->page->extra_url = '&action=ingresos_proy';

        return $this->url();
    }

    public function export_url() {
        $url = $this->url() .
               '&action='. urlencode($this->action) .
               '&export=true';

        if(isset($_REQUEST['fecha'])) {
            $url .= '&fecha=' . urlencode($_REQUEST['fecha']);
        }
        return $url;
    }

    private function reservasSinSenia($export = false) {
        $reserva = new reserva();
        $estado = estado_reserva::get(estado_reserva::SINSENA);
        $this->reservas = $reserva->findByEstado($estado->getId());
        if($export) {
            $this->template = false;
            // output headers so that the file is downloaded rather than displayed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=reservas_sin_senia.'.time().'.csv');

            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');
            fputs($output, "sep=,\n");
            // output the column headings
            fputcsv($output, array(
                'Numero',
                'Fecha Creacion',
                'Fecha Ingreso',
                'Fecha Salida',
                'Nombre',
                'Tarifa',
                'Habitaciones',
                'Cant. Ad',
                'Cant. Men',
                'Total'
            ));

            foreach($this->reservas as $row) {
                /** @var $row reserva */
                fputcsv($output, array(
                    $row->getId(),
                    $row->getCreateDate(true),
                    $row->getFechaIn(true),
                    $row->getFechaOut(true),
                    $row->getCliente()->nombre,
                    $row->getTarifa()->getMonto(),
                    implode(",", $row->getNumerosHabitaciones()),
                    $row->getCantidadAdultos(),
                    $row->getCantidadMenores(),
                    $row->getTotal()
                ));
            }
            $hoy = new DateTime();
            fputcsv($output, array("Reporte generado el ". $hoy->format('Y-m-d h:i:s') . ' por el usuario: ' . $this->user->nick));
        } else {
            $this->template = 'reserva_reportes_sinsenia';
        }
    }

    private function reservasConSenia($export = false) {
        $reserva = new reserva();
        $estado = estado_reserva::get(estado_reserva::SENADO);
        $this->reservas = $reserva->findByEstado($estado->getId());
        if($export) {
            $this->template = false;
            // output headers so that the file is downloaded rather than displayed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=reservas_con_senia.'.time().'.csv');

            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');
            fputs($output, "sep=,\n");
            // output the column headings
            fputcsv($output, array(
                'Numero',
                'Fecha Ingreso',
                'Fecha Salida',
                'Nombre',
                'Tarifa',
                'Habitaciones',
                'Cant. Ad',
                'Cant. Men',
                'Total',
                'Seña',
                'Saldo'
            ));

            foreach($this->reservas as $row) {
                /** @var $row reserva */
                fputcsv($output, array(
                    $row->getId(),
                    $row->getFechaIn(true),
                    $row->getFechaOut(true),
                    $row->getCliente()->nombre,
                    $row->getTarifa()->getMonto(),
                    implode(",", $row->getNumerosHabitaciones()),
                    $row->getCantidadAdultos(),
                    $row->getCantidadMenores(),
                    $row->getTotal(),
                    $row->getMontoSeniado(),
                    $row->getSaldo()
                ));
            }
            $hoy = new DateTime();
            fputcsv($output, array("Reporte generado el ". $hoy->format('Y-m-d h:i:s') . ' por el usuario: ' . $this->user->nick));            $this->template = false;
        } else {
            $this->template = 'reserva_reportes_sinsenia';
        }
        $this->template = 'reserva_reportes_consenia';
    }

    private function raciones($export = false) {
        $this->fecha = date('d-m-Y H:i:s');


        $this->template = 'reserva_reportes_raciones';
    }

    private function ocupacion($export = false) {
        $this->template = 'reserva_reportes_ocupacion';
    }

    private function habitaciones($export = false) {
        $this->template = 'reserva_reportes_habitaciones';
    }

    private function ingresos($export = false) {
        $date = isset($_REQUEST['fecha']) ? date_create_from_format('d/m/Y', $_REQUEST['fecha']) : new DateTime('tomorrow');
        $this->fecha_res = $date->format('d/m/Y');
        /** @var $this->reserva reserva */
        $this->reservas = $this->reserva->find(array(
            'fecha_in' => $date->format('d-m-Y'),
            'idestado' => array(2,3,4)
        ));
        if($export) {
            $this->template = false;
            // output headers so that the file is downloaded rather than displayed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=reservas_ingreso_maniana.' . time() . '.csv');

            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');
            fputs($output, "sep=,\n");
            fputcsv($output,array('Listado de Ingresos para el día: '.$date->format('d/m/Y')));
            // output the column headings
            fputcsv($output, array(
                'Numero', 'Fecha Ingreso', 'Fecha Salida', 'Nombre', 'Tarifa', 'Habitaciones', 'Cant. Ad', 'Cant. Men',
                'Total', 'Seña', 'Saldo'
            ));

            foreach ($this->reservas as $row) {
                /** @var $row reserva */
                fputcsv($output, array(
                    $row->getId(), $row->getFechaIn(true), $row->getFechaOut(true), $row->getCliente()->nombre,
                    $row->getTarifa()->getMonto(), implode(",", $row->getNumerosHabitaciones()),
                    $row->getCantidadAdultos(), $row->getCantidadMenores(), $row->getTotal(), $row->getMontoSeniado(),
                    $row->getSaldo()
                ));
            }
            $hoy = new DateTime();
            fputcsv($output, array(
                "Reporte generado el " . $hoy->format('Y-m-d h:i:s') . ' por el usuario: ' . $this->user->nick
            ));
            $this->template = false;
        } else {
            $this->template = 'reserva_reportes_ingresos';
        }

    }

    public function getRaciones(grupo_clientes $grupo_clientes, $clase, $confirmados = true ) {
        $obj = new pasajero_por_reserva();
        $fecha = date('Y-m-d');
        switch($clase) {
            case reserva::ALOJADOS:
                $fecha .= ' 23:59:59';
                break;
            case reserva::DESAYUNOS:
                $fecha .= ' 10:00:00';
                break;
            case reserva::ALMUERZOS:
                $fecha .= ' 12:00:00';
                break;
            case reserva::CENAS:
                $fecha .= ' 18:00:00';
                break;
        }
        return $obj->fetchCantPassByFechaAndCateg($fecha, $grupo_clientes->codgrupo, $confirmados);
    }

    public function getCantPasajerosCheckIn() {
        $obj = new pasajero_por_reserva();

        return $obj->fetchCantCheckInByFecha(date('Y-m-d'));
    }

    public function getCantPlazasDisponibles() {
        $obj = new habitacion();

        return $obj->fetchCountPlazasDisponiblesByFecha(date('Y-m-d'));
    }

    public function getPorcentajeOcupacion() {

        $cantPasajerosCheckIn = $this->getCantPasajerosCheckIn();
        $cantPlazDisponibles = $this->getCantPlazasDisponibles();

        return number_format(($cantPasajerosCheckIn * 100) / $cantPlazDisponibles, FS_NF0, FS_NF1, FS_NF2);

    }

    public function getTipoPasajeros() {
        $grucli = new grupo_clientes();

        return $grucli->all();
    }

    public function getPabellon() {
        return $this->pabellon;
    }

    public function getHabitacionesOcupadasPorPabellon($pabellonId = 0) {
        $habitacion = new habitacion();

        return $habitacion->fetchByPabellonAndResDate($pabellonId, date('d-m-Y'));
    }

}