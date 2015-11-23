<?php

require_model('reserva.php');

require_once 'reserva_controller.php';

class reserva_home extends reserva_controller {

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

    /**
     * @var cliente
     */
    protected $cliente = null;

    protected $fecha_desde = null;

    protected $fecha_hasta = null;

    /**
     * @var int
     */
    protected $max_pass = 0;

    public function __construct() {
        parent::__construct(__CLASS__, "Reserva", "Reserva");
    }

    protected function process() {

        $action = (string) isset($_GET['action']) ? $_GET['action'] : 'list';
        $this->reserva = new reserva();
        $this->pabellon = new pabellon();
        $this->categoria_habitacion = new categoria_habitacion();
        $this->habitacion = new habitacion();
        $this->grupos_cliente = new grupo_clientes();
        $this->tarifa = new tarifa_reserva();
        $this->estado = new estado_reserva();
        $this->share_extensions();
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
            case 'voucher':
                $this->voucherAction();
                break;
            case 'cancel':
                $this->cancelarAction();
                break;
            case 'checkin':
                $this->checkinAction();
                break;
            case 'checkout':
                $this->checkoutAction();
                break;
            case 'find':
                $this->findAction();
                break;
            //            case 'delete':
            //                $this->deleteAction();
            //                break;
        }
    }

    public function list_url() {
        $this->page->extra_url = '&action=list';

        return $this->url();
    }

    /**
     * @return string
     */
    public function new_url() {
        $this->page->extra_url = '&action=add';

        $args = func_get_args();
        if(!isset($args[0])) {
            $args[0] = false;
        }

        if(is_int($args[0]) && $args[0] > 1) {
            $this->page->extra_url .= '&step=' . $args[0];
        }

        if(is_a($args[0], 'habitacion') && is_a($args[1], 'DateTime')) {
            $this->page->extra_url .= '&fecha_in=' . $args[1]->format('Y-m-d') . '&idsHabitaciones=' . $args[0]->getId();
        }

        return $this->url();
    }

    public function edit_url(reserva $reserva, $prev_step = false) {
        $this->page->extra_url = '&action=edit&id=' . (int) $reserva->getId();

        if($prev_step && $reserva->getStep() != 1) {
            $this->page->extra_url .= '&step='.($reserva->getStep()-1);
        }

        return $this->url();
    }

    public function delete_url(reserva $reserva) {
        $this->page->extra_url = '&action=delete&id=' . (int) $reserva->getId();

        return $this->url();
    }

    public function voucher_url(reserva $reserva) {
        $this->page->extra_url = '&action=voucher&id=' . (int) $reserva->getId();

        return $this->url();
    }

    public function pago_url(reserva $reserva) {
        $this->page->extra_url = '&action=pago&id=' . (int) $reserva->getId();

        return str_replace('page=reserva_home','page=reserva_pagos', $this->url());
    }

    public function checkin_url(reserva $reserva) {
        $this->page->extra_url = '&action=checkin&id=' . (int) $reserva->getId();

        return $this->url();
    }

    public function checkout_url(reserva $reserva) {
        $this->page->extra_url = '&action=checkout&id=' . (int) $reserva->getId();

        return $this->url();
    }

    public function cancel_url(reserva $reserva) {
        $this->page->extra_url = '&action=cancel&id=' . (int) $reserva->getId();

        return $this->url();
    }

    public function anterior_url() {
        return '';
    }

    public function siguiente_url() {
        return '';
    }

    private function indexAction() {
        $this->page->extra_url = '';
        $mes = (int) date('n') + 1;
        if($mes > 12) {
            $mes = 1;
        }
        $mes = str_pad($mes, 2, 0);

        $this->fecha_desde = (isset($_POST['fecha_desde'])) ? $_POST['fecha_desde'] : date('d-m-Y');
        $this->fecha_hasta = (isset($_POST['fecha_hasta'])) ? $_POST['fecha_hasta'] : date('d-' . $mes .'-Y');
        $this->rango_fechas = new DatePeriod(
            new DateTime($this->fecha_desde),
            DateInterval::createFromDateString('+1 day'),
            //Ugly hack to include the frist day
            new DateTime($this->fecha_hasta . ' 23:59:59')
        );

        $this->cantidad_dias = 1;
        foreach($this->rango_fechas as $dt) {
            $this->cantidad_dias ++;
        }

        $this->pabellones = $this->pabellon->fetchAll();
        //$this->reservas = $this->reserva->fetchAll();
        $this->template = 'reserva_home_index';
    }

    private function addAction() {
        $this->page->extra_url = '&action=add';

        if(isset($_GET['fecha_in'])) {
            $this->fecha_desde = $_GET['fecha_in'];
            $this->reserva->setFechaIn($_GET['fecha_in']);
        }

        if(isset($_GET['idsHabitaciones'])) {
            $idhabitacion = $_GET['idsHabitaciones'];
            $this->reserva->setHabitaciones(array($idhabitacion));
            $this->max_pass = $this->reserva->getMaxPasajeros();
        }
        //Si tengo un request por post levantar la reserva
        // actualizar los datos y guardar, mostrar mensage con respecto a la accion realizada
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            if(isset($_POST['idreserva'])) {
                $this->reserva = reserva::get($_POST['idreserva']);
            }
            $this->reserva->setValues($_POST);
            if($this->reserva->save()) {
                if($this->reserva->getStep() > 2) {
                    $this->reserva->setEdit(true);
                    $this->new_message($this->reserva->getSuccesMessage());
                }

                if(!$this->reserva->getNumerosHabitaciones()) {
                    $this->new_advice('La reserva no tiene ninguna habitacion asociada!');
                }
            } else {
                $this->new_error_msg("¡Error al actualizar la reserva!");
            }
        }

        $this->_setTemplate();

    }

    private function editAction() {
        $this->page->extra_url = '&action=edit';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $step = (int) isset($_GET['step']) ? (int) $_GET['step'] : false;
        $this->reserva = reserva::get($id);
        if(!$this->reserva) {
            $this->reserva = new reserva();
            $this->indexAction();
        } else {
            $this->_setTemplate($step);
        }
    }

    private function voucherAction() {
        $this->page->extra_url = '&action=checkin';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->reserva = reserva::get($id);

        require_once 'plugins/reservas/extras/PhpWord/Autoloader.php';
        PhpOffice\PhpWord\Autoloader::register();

        $voucher = new \PhpOffice\PhpWord\TemplateProcessor('plugins/reservas/extras/voucher/template_voucher.docx');

        /** @var direccion_cliente[] $direcciones */
        $direcciones = $this->reserva->getCliente()->get_direcciones();
        if(!isset($direcciones[0]) || !is_a($direcciones[0], 'direccion_cliente')) {
            $direcciones[0] = new direccion_cliente();
            $direcciones[0]->direccion = '';
            $direcciones[0]->ciudad = '';
            $direcciones[0]->codpostal = '';
            $direcciones[0]->provincia = '';
        }
        $voucher->setValue('codigoReserva', htmlspecialchars($this->reserva->getId()));
        $voucher->setValue('nombreReserva', htmlspecialchars($this->reserva->getCliente()->nombre));
        $voucher->setValue('documento', htmlspecialchars($this->reserva->getCliente()->cifnif));
        $voucher->setValue('categoría', htmlspecialchars($this->reserva->getGrupoCliente()->nombre));
        $voucher->setValue('montoTarifa', htmlspecialchars(number_format($this->reserva->getTarifa()->getMonto(), FS_NF0, FS_NF1, FS_NF2)));
        $voucher->setValue('email', htmlspecialchars($this->reserva->getCliente()->email));
        $voucher->setValue('telefono', htmlspecialchars($this->reserva->getCliente()->telefono1));
        $voucher->setValue('telefono2', htmlspecialchars($this->reserva->getCliente()->telefono2));

        $voucher->setValue('domicilio', htmlspecialchars($direcciones[0]->direccion));
        $voucher->setValue('localidad', htmlspecialchars($direcciones[0]->ciudad));
        $voucher->setValue('codigoPostal', htmlspecialchars($direcciones[0]->codpostal));
        $voucher->setValue('provincia', htmlspecialchars($direcciones[0]->provincia));

        $voucher->setValue('fechaIn', htmlspecialchars($this->reserva->getFechaIn()));
        $voucher->setValue('fechaOut', htmlspecialchars($this->reserva->getFechaOut()));
        $voucher->setValue('habitaciones', htmlspecialchars(implode(', ', $this->reserva->getNumerosHabitaciones())));
        $voucher->setValue('adultos', htmlspecialchars($this->reserva->getCantidadAdultos()));
        $voucher->setValue('menores', htmlspecialchars($this->reserva->getCantidadMenores()));
        $voucher->setValue('total', htmlspecialchars(number_format($this->reserva->totales['total'], FS_NF0, FS_NF1, FS_NF2)));

        $voucher->cloneRow('nombrePasajero', $this->reserva->getCantPasajeros());

        foreach($this->reserva->getCantPasajeros(true) as $pasId) {
            $pasajero = $this->reserva->getPasajero($pasId);
            $voucher->setValue('nombrePasajero#'.($pasId+1), htmlspecialchars($pasajero->getNombreCompleto()));
            $voucher->setValue('fechaNacPasajero#'.($pasId+1), htmlspecialchars($pasajero->getFechaNacimiento()));
            $voucher->setValue('dniPasajero#'.($pasId+1), htmlspecialchars($pasajero->getDocumento()));
        }

        $pagos = $this->reserva->getPagos();
        if($pagos) {
            $voucher->cloneRow('numeroRecibo', count($pagos));
            foreach($pagos as $num => $pago) {
                $voucher->setValue('numeroRecibo#'.($num+1), htmlspecialchars($pago->idrecibo));
                $voucher->setValue('fechaRecibo#'.($num+1), htmlspecialchars($pago->fecha));
                $voucher->setValue('montoRecibo#'.($num+1), htmlspecialchars(number_format($pago->importe, FS_NF0, FS_NF1, FS_NF2)));
            }
        } else {
            $voucher->setValue('numeroRecibo', '');
            $voucher->setValue('fechaRecibo','');
            $voucher->setValue('montoRecibo','');
        }

        $voucher->saveAs('tmp/voucher.' . $this->reserva->getId() . '.docx');
        $this->template = false;
        header('Location: tmp/voucher.' . $this->reserva->getId() . '.docx');

    }

    private function checkinAction() {
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->page->extra_url = '&action=checkin&id='.$id;
        $this->reserva = reserva::get($id);
        if(!$this->reserva) {
            $this->reserva = new reserva();
            $this->indexAction();
        } else {
            $this->template = 'reserva_checkin_form';
        }

        $pasajeros = array();
        $pasajero_por_reserva = new pasajero_por_reserva();
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
            foreach($_POST['pasajero'] as $pasajero) {
                if(isset($pasajero['checkin']) && $pasajero['checkin']) {
                    if(isset($pasajero['id']) && $pasajero['id']) {
                        $pasajero_por_reserva = pasajero_por_reserva::get($pasajero['id']);
                    }
                    $pasajero_por_reserva->setValues($pasajero);
                    $pasajero_por_reserva->setFechaIn(date('Y-m-d H:i:s'));
                    $pasajeros[] = clone $pasajero_por_reserva;
                }
            }
            $this->reserva->setPasajeros($pasajeros);
            $this->reserva->setEstado(estado_reserva::get(estado_reserva::CHECKIN));
            if($this->reserva->save()) {
                $this->new_message($this->reserva->getSuccesMessage());
                header('Refresh: 3;url='.$this->reserva->url());
            } else {
                $this->new_error_msg("¡Error en Reserva!");
            }

        }
    }

    private function checkoutAction() {
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->page->extra_url = '&action=checkout&id='.$id;
        $this->reserva = reserva::get($id);
        if(!$this->reserva) {
            $this->reserva = new reserva();
            $this->indexAction();
        } else {
            $this->template = 'reserva_checkout_form';
        }

        $pasajeros = array();
        $pasajero_por_reserva = new pasajero_por_reserva();
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
            foreach($_POST['pasajero'] as $pasajero) {
                if(isset($pasajero['id']) && $pasajero['id']) {
                    $pasajero_por_reserva = pasajero_por_reserva::get($pasajero['id']);
                }
                $pasajero_por_reserva->setValues($pasajero);
                $pasajero_por_reserva->setFechaOut(date('Y-m-d H:i:s'));
                $pasajeros[] = clone $pasajero_por_reserva;
            }
            $this->reserva->setPasajeros($pasajeros);
            $this->reserva->setEstado(estado_reserva::get(estado_reserva::FINALIZADA));
            if($this->reserva->save()) {
                $this->new_message($this->reserva->getSuccesMessage());
            } else {
                $this->new_error_msg("¡Error en Reserva!");
            }

        }    }

    private function cancelarAction() {
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->page->extra_url = '&action=cancel&id='.$id;
        $this->reserva = reserva::get($id);
        if(!$this->reserva) {
            $this->reserva = new reserva();
            $this->indexAction();
        } else {
            $this->template = 'reserva_cancel_form';
        }

        $this->reserva->setCancelDate(new DateTime());

        if($this->reserva->getEstado() == estado_reserva::INCOMPLETA) {
            $this->_cancelReservaInDb();
            $this->indexAction();
        } else {
            $this->cliente = $this->reserva->getCliente();
            $this->factura = $this->reserva->getFacturaCliente();
            if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_reserva'])) {
                //TODO: Crear facura con los gastos de envio
                $this->_cancelReservaInDb();
                $this->indexAction();

            } else {
                $this->new_advice($this->reserva->getCancelMessage());
            }
        }


    }

    private function findAction() {
        $cod = isset($_GET['cod']) ? $_GET['cod'] : null;
        if($cod) {
            $cli = new cliente();
            $this->cliente = $cli->get($cod);
        }
        $from_cliente = isset($_GET['from_client']) ? boolval($_GET['from_client']) : false;
        $this->reservas = $this->reserva->find(array(
            'codcliente' => $cod,
            'order_by' => 'reserva.fecha_in DESC'
        ));
        $this->from_client = $from_cliente;
        $this->template = 'reserva_find';
    }

    private function deleteAction() {
        $this->page->extra_url = '&action=delete';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
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

    private function _cancelReservaInDb() {
        $this->reserva->setEstado(estado_reserva::get(estado_reserva::CANCELADA));
        if($this->reserva->save()) {
            $this->new_message('La reserva a sido cancelada correctamente!');
        } else {
            $this->new_error_msg('Error al cancelar la reserva');
        }
    }

    private function _setTemplate($step=false) {
        if(is_int($step)) {
            $this->step = $step;
        } else {
            $this->step = $this->reserva->getStep();
        }
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

    //Métodos para los templates

    public function getPabellon() {
        return $this->pabellon;
    }

    public function getHabitacion() {
        return $this->habitacion;
    }

    public function getReserva() {
        return $this->reserva;
    }

    public function getCliente() {
        return $this->cliente;
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

    public function getFechaDesde() {
        return $this->fecha_desde;
    }

    public function getFechaHasta() {
        return $this->fecha_hasta;
    }

    public function getMaxPass() {
        $max_pass = false;
        if($this->max_pass > 0) {
            $max_pass = $this->max_pass;
        }
        return $max_pass;
    }

    private $cacheReservasDisponibilidad = array();

    public function getHabitacionCell(habitacion $habitacion, DateTime $fecha) {
        $reserva = false;
        if(!isset($this->cacheReservasDisponibilidad[$habitacion->getId()][$fecha->format('Y-m-d')])) {
            $reservas = $this->reserva->findByHabitacionYFecha($habitacion->getId(), $fecha->format('Y-m-d'));
            if(isset($reservas[0])) {
                $reserva = $reservas[0];
                $fechasReserva = new DatePeriod(
                    new DateTime($reserva->getFechaIn()),
                    DateInterval::createFromDateString('+1 day'),
                    //Ugly hack to include the frist day
                    new DateTime($reserva->getFechaOut() . ' 23:59:59')
                );
                foreach($fechasReserva as $fechaTmp) {
                    $this->cacheReservasDisponibilidad[$habitacion->getId()][$fechaTmp->format('Y-m-d')] = $reserva;
                }
            }
        } else {
            $reserva = $this->cacheReservasDisponibilidad[$habitacion->getId()][$fecha->format('Y-m-d')];
        }

        //Si hay reserva y no hay seña
        if($reserva) {
            $texto = $reserva->getIncialesCliente();
            $estado = (string) $reserva->getEstado();
            if(($estado === estado_reserva::INCOMPLETA ||
               $estado === estado_reserva::SINSENA) &&
               !$reserva->getIdFactura()
            ) {
                $cssClass = "reservada";
            } elseif(($estado === estado_reserva::SENADO ||
                     $estado === estado_reserva::PAGO) ||
                     $reserva->getIdFactura() &&
                     $estado !== estado_reserva::CHECKIN
            ) {
                $cssClass = "reservada_senia";
                //Si hay check_in está ocupada
            } elseif($reserva->isCheckedIn()) {
                $cssClass = "ocupada";
            }
        } else {
            //Si no hay reserva la habitacion está
            $cssClass = "disponible";
            $texto = '&nbsp';
        }

        $cell = '<td>';
        if($reserva) {
            $href = $this->edit_url($reserva);
        } else {
            $href = $this->new_url($habitacion, $fecha);
        }
        $cell .= '<a href="' . $href . '" class="btn ' . $cssClass . '">'.$texto.'</a>';
        if($reserva && $reserva->getFechaOut() == $fecha->format('d-m-Y')) {
            $cell .= '<a href="' . $this->new_url($habitacion, $fecha) . '" style="width: 100%;" class="btn disponible">&nbsp;</a>';
        }

        $cell .= '</td>';

        return $cell;
    }

    private function share_extensions() {
        $extensiones = array(
            array(
                'name' => 'reservas_cliente',
                'page_from' => 'reserva_home',
                'page_to' => 'ventas_cliente',
                'type' => 'tab',
                'text' => '<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span><span class="hidden-xs">&nbsp; Reservas</span>',
                'params' => '&action=find&from_client=true'
            )
        );
        foreach($extensiones as $ext)
        {
            $fsext = new fs_extension($ext);
            if( !$fsext->save() )
            {
                $this->new_error_msg('Imposible guardar los datos de la extensión '.$ext['name'].'.');
            }
        }
    }

}