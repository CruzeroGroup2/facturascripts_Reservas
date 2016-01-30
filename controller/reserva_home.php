<?php

require_once 'plugins/reservas/extras/functions/boolval.php';

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
            case 'confirm':
                $this->confirmAction();
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

    public function confirm_url(reserva $reserva) {
        $this->page->extra_url = '&action=confirm&id=' . (int) $reserva->getId();

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
        $this->pabellon = isset($_POST['idpabellon']) ? pabellon::get($_POST['idpabellon']) : null;
        $this->habitacion = isset($_POST['numeroHab']) ? habitacion::getByNumero($_POST['numeroHab']) : null;
        $fecha_hasta = new DateTime('now + 15 days');
        $this->fecha_desde = (isset($_POST['fecha_desde'])) ? $_POST['fecha_desde'] : date('d-m-Y');
        $this->fecha_hasta = (isset($_POST['fecha_hasta'])) ? $_POST['fecha_hasta'] : $fecha_hasta->format('d-m-Y');
        if($this->pabellon) {
            $this->pabellones = array($this->pabellon);
        } else {
            $this->pabellon = new pabellon();
            $this->pabellones = $this->pabellon->fetchAll();
        }
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
            if(isset($_POST['idreserva']) && $_POST['idreserva']) {
                $this->reserva = reserva::get($_POST['idreserva']);
            }
            $this->reserva->setValues($_POST);
            $this->reserva->setCodAgente($this->user->codagente);

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
        $generar_orden = isset($_GET['orden']) ? boolval($_GET['orden']) : false;
        $this->reserva = reserva::get($id);
        if(!$this->reserva) {
            $this->reserva = new reserva();
            $this->indexAction();
        } else {
            $this->_setTemplate($step);
        }

        if($generar_orden) {
            $this->generate_order($this->reserva);
        }
    }

    private function generate_order() {
        require_once 'plugins/reservas/extras/PhpWord/Autoloader.php';
        PhpOffice\PhpWord\Autoloader::register();

        $order = new \PhpOffice\PhpWord\TemplateProcessor('plugins/reservas/extras/voucher/template_order.docx');
        //Agregar los valores de la orden de alojamiento

        $this->replace_values($order);

        $order->saveAs('tmp/orden.' . $this->reserva->getId() . '.docx');
        $this->template = false;
        header('Location: tmp/orden.' . $this->reserva->getId() . '.docx');
    }

    private function confirmAction() {
        $this->page->extra_url = '&action=confirm';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->reserva = reserva::get($id);

        require_once 'plugins/reservas/extras/PhpWord/Autoloader.php';
        PhpOffice\PhpWord\Autoloader::register();

        $confirm = new \PhpOffice\PhpWord\TemplateProcessor('plugins/reservas/extras/voucher/template_confirm.docx');

        $this->replace_values($confirm);

        $confirm->saveAs('tmp/confirm.' . $this->reserva->getId() . '.docx');
        $this->template = false;
        header('Location: tmp/confirm.' . $this->reserva->getId() . '.docx');

    }

    private function voucherAction() {
        $this->page->extra_url = '&action=checkin';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->reserva = reserva::get($id);

        require_once 'plugins/reservas/extras/PhpWord/Autoloader.php';
        PhpOffice\PhpWord\Autoloader::register();

        $voucher = new \PhpOffice\PhpWord\TemplateProcessor('plugins/reservas/extras/voucher/template_voucher.docx');

        $this->replace_values($voucher);

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

        if(!$this->reserva->getCheckIn()){
            $this->new_advice("Haciendo checkin the pasajeros en una fecha distinta a la de la reserva!");
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
                    if(!$pasajero_por_reserva->getIdTarifa()) {
                        $pasajero_por_reserva->setIdTarifa($this->reserva->getIdTarifa());
                    }
                    $pasajero_por_reserva->setIdReserva($this->reserva->getId());
                    if(!$pasajero_por_reserva->isCheckIn()) {
                        $pasajero_por_reserva->setCheckIn(date('Y-m-d H:i:s'));
                    }
                    $pasajeros[] = clone $pasajero_por_reserva;
                }
            }
            $this->reserva->setPasajeros($pasajeros);
            $this->reserva->setEstado(estado_reserva::get(estado_reserva::CHECKIN));

            if(!$this->get_errors() && $this->reserva->save()) {
                $this->new_message($this->reserva->getSuccesMessage());
                header('Refresh: 1;url='.$this->edit_url($this->reserva).'&orden=true');
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
                if(isset($pasajero['checkout']) && $pasajero['checkout']) {
                    if (isset($pasajero['id']) && $pasajero['id']) {
                        $pasajero_por_reserva = pasajero_por_reserva::get($pasajero['id']);
                    }
                    $pasajero_por_reserva->setValues($pasajero);
                    if(!$pasajero_por_reserva->getIdTarifa()) {
                        $pasajero_por_reserva->setIdTarifa($this->reserva->getIdTarifa());
                    }
                    $pasajero_por_reserva->setIdReserva($this->reserva->getId());
                    if ($pasajero_por_reserva->isCheckIn() && !$pasajero_por_reserva->isCheckOut()) {
                        $pasajero_por_reserva->setCheckOut(date('Y-m-d H:i:s'));
                    } else {
                        $this->new_error_msg("El pasajero " . $pasajero_por_reserva->getNombreCompleto() .
                                             " no tiene checkin!");
                    }
                    $pasajeros[] = clone $pasajero_por_reserva;
                }
            }
            $this->reserva->setPasajeros($pasajeros);
            $this->reserva->setEstado(estado_reserva::get(estado_reserva::FINALIZADA));
            if($this->reserva->save()) {
                header('Refresh: 1;url='.$this->reserva->url());
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
                $this->template = 'reserva_new_step2';
                break;
            case 3:
            case 4:
                $this->template = 'reserva_new_step3';
                break;
        }
    }

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

    /**
     * @var reserva[]
     */
    private $cacheReservasDisponibilidad = array();

    public function getDisponibilidadHabitacionTable() {
        $this->rango_fechas = new DatePeriod(
            new DateTime($this->fecha_desde),
            DateInterval::createFromDateString('+1 day'),
            //Ugly hack to include the frist day
            new DateTime($this->fecha_hasta . ' 23:59:59')
        );

        $table = '<table id="habitaciones_disponibles" class="table table-condensed table-bordered table-striped">
        <thead>
        <col style="width: 100px;" />
            <tr>
                <th>Hab.</th>'. "\n";
        foreach($this->rango_fechas as $fecha) {
            $table .= '                <th>' . $fecha->format('d') . '</th>' . "\n";
        }
        $table .= '            </tr>
        </thead>
        <tbody>';

        $this->cantidad_dias = 1;
        foreach($this->rango_fechas as $dt) {
            $this->cantidad_dias ++;
        }

        foreach($this->reserva->fetchByRange($this->fecha_desde, $this->fecha_hasta) as $reserva) {
            /** @var $reserva reserva */
            $range = new DatePeriod(
                new DateTime($reserva->getFechaIn(true)),
                DateInterval::createFromDateString('+1 day'),
                new DateTime($reserva->getFechaOut() . '23:59:00')
            );
            foreach($range as $dt) {
                if(!isset($this->cacheReservasDisponibilidad[$dt->format('d-m')])) {
                    $this->cacheReservasDisponibilidad[$dt->format('d-m')] = array();
                }
                foreach($reserva->getHabitaciones() as $habitacion) {
                    if(!isset($this->cacheReservasDisponibilidad[$dt->format('d-m')][$habitacion->getHabitacion()->getId()])) {
                        $this->cacheReservasDisponibilidad[$dt->format('d-m')][$habitacion->getHabitacion()->getId()] = array();
                    }
                    $this->cacheReservasDisponibilidad[$dt->format('d-m')][$habitacion->getHabitacion()->getId()][] = $reserva;
                }
            }
        }
        unset($reserva);

        /** @var pabellon $pabellon */
        foreach($this->pabellones as $pabellon) {
            $table .= '<tr>
                <th colspan="' . $this->cantidad_dias . '" class="text-center">' . $pabellon->getDescripcion() . '</th>
            </tr>';
            foreach ($pabellon->fetchHabitacionesByPabellon() as $habitacion) {
                $table .= '                <tr>
                <th>' . $habitacion->getNumero() . '</th>'."\n";
                foreach($this->rango_fechas as $fecha) {
                    $table .= '<td>';
                    if(isset($this->cacheReservasDisponibilidad[$fecha->format('d-m')][$habitacion->getId()])) {
                        $reservas = $this->cacheReservasDisponibilidad[$fecha->format('d-m')][$habitacion->getId()];
                        foreach($reservas as $reserva) {
                            $table .= '<a href="' . $this->edit_url($reserva) . '" class="btn ' . $this->getCssClass($reserva) . '">' . $reserva->getIncialesCliente() .'</a>';
                        }
                        if($reserva->getFechaOut() == $fecha->format('d-m-Y') && count($reservas) == 1) {
                            $table .= '<a href="' . $this->new_url($habitacion, $fecha) . '" style="width: 100%;" class="btn disponible">&nbsp;</a>';
                        }
                    } else {
                        $table .= '<a href="' . $this->new_url($habitacion, $fecha) . '" style="width: 100%;" class="btn disponible">&nbsp;</a>';
                    }

                    $table .= '</td>'."\n";
                }
                $table .= '            </tr>';
            }
        }
        $table .= '        </tbody>
    </table>';
        return $table;
    }

    private function getCssClass(reserva $reserva) {
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
        return $cssClass;
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

    private function replace_values(\PhpOffice\PhpWord\TemplateProcessor &$document) {
        /** @var direccion_cliente[] $direcciones */
        $direcciones = $this->reserva->getCliente()->get_direcciones();
        if(!isset($direcciones[0]) || !is_a($direcciones[0], 'direccion_cliente')) {
            $direcciones[0] = new direccion_cliente();
            $direcciones[0]->direccion = '';
            $direcciones[0]->ciudad = '';
            $direcciones[0]->codpostal = '';
            $direcciones[0]->provincia = '';
        }

        $document->setValue('codigoReserva', htmlspecialchars($this->reserva->getId()));
        $document->setValue('fechaReserva', htmlspecialchars($this->reserva->getCreateDate(true)));
        $document->setValue('nombreReserva', htmlspecialchars($this->reserva->getCliente()->nombre));
        $document->setValue('documento', htmlspecialchars($this->reserva->getCliente()->cifnif));
        $document->setValue('categoría', htmlspecialchars($this->reserva->getGrupoCliente()->nombre));
        $document->setValue('montoTarifa', htmlspecialchars(number_format($this->reserva->getTarifa()->getMonto(), FS_NF0, FS_NF1, FS_NF2)));
        $document->setValue('email', htmlspecialchars($this->reserva->getCliente()->email));
        $document->setValue('telefono', htmlspecialchars($this->reserva->getCliente()->telefono1));
        $document->setValue('telefono2', htmlspecialchars($this->reserva->getCliente()->telefono2));

        $document->setValue('domicilio', htmlspecialchars($direcciones[0]->direccion));
        $document->setValue('localidad', htmlspecialchars($direcciones[0]->ciudad));
        $document->setValue('codigoPostal', htmlspecialchars($direcciones[0]->codpostal));
        $document->setValue('provincia', htmlspecialchars($direcciones[0]->provincia));

        $document->setValue('fechaIn', htmlspecialchars($this->reserva->getFechaIn()));
        $document->setValue('fechaOut', htmlspecialchars($this->reserva->getFechaOut()));
        $document->setValue('cantNoches', htmlspecialchars($this->reserva->getCantidadDias()));
        $document->setValue('habitaciones', htmlspecialchars(implode(', ', $this->reserva->getNumerosHabitaciones())));
        $document->setValue('adultos', htmlspecialchars($this->reserva->getCantidadAdultos()));
        $document->setValue('menores', htmlspecialchars($this->reserva->getCantidadMenores()));
        $document->setValue('sinCargo', htmlspecialchars($this->reserva->getCantidadBebes()));
        $document->setValue('total', htmlspecialchars(number_format($this->reserva->getTotal(), FS_NF0, FS_NF1, FS_NF2)));
        $document->setValue('totalPagos', htmlspecialchars(number_format($this->reserva->getMontoSeniado(), FS_NF0, FS_NF1, FS_NF2)));
        $document->setValue('totalDeposito', htmlspecialchars(number_format($this->reserva->getTotal() * 0.30, FS_NF0, FS_NF1, FS_NF2)));

        $document->setValue('fechaExpiracion', htmlspecialchars($this->reserva->getExpireDate()));

        try {
            $document->cloneRow('nombrePasajero', $this->reserva->getCantPasajeros());
            foreach($this->reserva->getCantPasajeros(true) as $pasId) {
                $pasajero = $this->reserva->getPasajero($pasId);
                $document->setValue('nombrePasajero#'.($pasId+1), htmlspecialchars($pasajero->getNombreCompleto()));
                $document->setValue('fechaInPasajero#'.($pasId+1), htmlspecialchars($pasajero->getFechaIn()));
                $document->setValue('fechaOutPasajero#'.($pasId+1), htmlspecialchars($pasajero->getFechaOut()));
                $document->setValue('fechaNacPasajero#'.($pasId+1), htmlspecialchars($pasajero->getFechaNacimiento()));
                $document->setValue('dniPasajero#'.($pasId+1), htmlspecialchars($pasajero->getDocumento()));
                $document->setValue('totalPasajero#'.($pasId+1), htmlspecialchars(number_format($pasajero->getTotal(), FS_NF0, FS_NF1, FS_NF2)));
            }

        } catch (PhpOffice\PhpWord\Exception\Exception $e) {
            //Do nothing
        }


        try {
            $pagos = $this->reserva->getPagos();
            if($pagos) {
                $document->cloneRow('numeroRecibo', count($pagos));
                foreach($pagos as $num => $pago) {
                    $document->setValue('numeroRecibo#'.($num+1), htmlspecialchars($pago->idrecibo));
                    $document->setValue('fechaRecibo#'.($num+1), htmlspecialchars($pago->fecha));
                    $document->setValue('montoRecibo#'.($num+1), htmlspecialchars(number_format($pago->importe, FS_NF0, FS_NF1, FS_NF2)));
                }
            } else {
                $document->setValue('numeroRecibo', '');
                $document->setValue('fechaRecibo','');
                $document->setValue('montoRecibo','');
            }
        } catch (PhpOffice\PhpWord\Exception\Exception $e) {
            //Do nothing
        }

        //Que hago con lo de la factura!
    }

}