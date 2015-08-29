<?php

require_model('reserva.php');
require_model('cliente.php');
require_model('direccion_cliente.php');
require_model('pais.php');
require_model('agente.php');
require_model('almacen.php');
require_model('serie.php');
require_model('divisa.php');
require_model('factura_cliente.php');
require_model('forma_pago.php');

require_once 'reserva_controller.php';

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 12/08/2015
 * Time: 09:36 PM
 */
class reserva_pagos extends reserva_controller {

    /**
     * @var reserva
     */
    protected $reserva;

    /**
     * @var cliente
     */
    protected $cliente;

    /**
     * @var direccion_cliente
     */
    protected $direccion;

    /**
     * @var pais
     */
    protected $pais;

    /**
     * @var agente
     */
    protected $agente;

    /**
     * @var almacen
     */
    protected $almacen;

    /**
     * @var serie
     */
    protected $serie;

    /**
     * @var divisa
     */
    protected $divisa;

    /**
     * @var factura_cliente
     */
    protected $factura;

    /**
     * @var forma_pago
     */
    protected $forma_pago;

    public function __construct() {
        parent::__construct(__CLASS__, "Pagos", "Reserva", false, false);
    }

    protected function process() {
        $action = (string) isset($_GET['action']) ? $_GET['action'] : 'list';
        switch($action) {
            case 'pago':
                $this->pagoAction();
                break;
        }
    }

    public function pago_url(reserva $reserva) {
        $this->page->extra_url = '&action=pago&id=' . (int) $reserva->getId();

        return $this->url();
    }


    private function pagoAction() {
        $this->page->extra_url = '&action=pago';
        $id = (int) isset($_GET['id']) ? $_GET['id'] : 0;
        $this->reserva = reserva::get($id);
        $this->reserva->calcularTotales();
        $this->cliente = $this->reserva->getCliente();
        $this->direccion = new direccion_cliente();
        $this->pais = new pais();
        $this->almacen = new almacen();
        $this->serie = new serie();
        $this->divisa = new divisa();
        $this->factura = new factura_cliente();
        $this->forma_pago = new forma_pago();
        foreach($this->cliente->get_direcciones() as $dir) {
            if($dir->domfacturacion) {
                $this->direccion = $dir;
                break;
            }
        }
        //Agente
        $this->agente = $this->user->get_agente();
        $this->template = 'reserva_pago_form';
        //$this->__createFactura();
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_factura'])) {
            $this->__createFactura();
        }
        $this->share_extensions();
    }

    private function __createFactura() {
        $date = new DateTime($_POST['fecha']);
        $fecha = $date->format('Y-m-d');
        $hora = $date->format('H:i:s');
        //Almacen
        $almacen = $this->almacen->get($_POST['almacen']);
        //Ejercicio
        $eje0 = new ejercicio();
        $ejercicio = $eje0->get_by_fecha($fecha);
        //Serie
        $serie = $this->serie->get($_POST['serie']);
        //Forma de pago
        $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
        //Divisa
        $divisa = $this->divisa->get($_POST['divisa']);

        //Albarán
        $this->factura->fecha = $fecha;
        $this->factura->hora = $hora;
        $this->factura->codalmacen = $almacen->codalmacen;
        $this->factura->codejercicio = $ejercicio->codejercicio;
        $this->factura->codserie = $serie->codserie;
        $this->factura->codpago = $forma_pago->codpago;
        $this->factura->coddivisa = $divisa->coddivisa;
        $this->factura->tasaconv = $divisa->tasaconv;
        $this->factura->codagente = $this->agente->codagente;
        $this->factura->numero2 = '';
        $this->factura->observaciones = '';
        $this->factura->irpf = $serie->irpf;
        $this->factura->porcomision = $this->agente->porcomision;
        $this->factura->codcliente = $this->cliente->codcliente;
        $this->factura->cifnif = $this->cliente->cifnif;
        $this->factura->nombrecliente = $this->cliente->razonsocial;
        $this->factura->ciudad = $this->direccion->ciudad;
        $this->factura->codpais = $this->direccion->codpais;
        $this->factura->codpostal = $this->direccion->codpostal;
        $this->factura->direccion = $this->direccion->direccion;
        $this->factura->provincia = $this->direccion->provincia;

        if($this->factura->save()) {
            $art0 = new articulo();
            $linea = new linea_factura_cliente();
            $linea->idfactura = $this->factura->idfactura;
            $linea->descripcion = $_POST[ 'desc_0'];
            $linea->pvpunitario = floatval($this->reserva->getTotal());
            $linea->cantidad = floatval(1);
            $linea->dtopor = floatval($this->reserva->getDescuento());
            $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
            $linea->pvptotal = floatval($this->reserva->getTotalFinal());

            $articulo = $art0->get('Reserva');
            if($articulo) {
                $linea->referencia = $articulo->referencia;
            }

            if($linea->save()) {
                //Calcula totales de la factura con la reserva agregada
                $this->factura->neto += $linea->pvptotal;
                $this->factura->totaliva += ($linea->pvptotal * $linea->iva / 100);
                $this->factura->totalirpf += ($linea->pvptotal * $linea->irpf / 100);
                $this->factura->totalrecargo += ($linea->pvptotal * $linea->recargo / 100);

                /// redondeamos
                $this->factura->neto = round($this->factura->neto, FS_NF0);
                $this->factura->totaliva = round($this->factura->totaliva, FS_NF0);
                $this->factura->totalirpf = round($this->factura->totalirpf, FS_NF0);
                $this->factura->totalrecargo = round($this->factura->totalrecargo, FS_NF0);
                $this->factura->total = $this->factura->neto + $this->factura->totaliva - $this->factura->totalirpf + $this->factura->totalrecargo;
                if($this->factura->save()) {
                    $this->new_message("<a href='" . $this->factura->url() . "'>" . ucfirst(FS_FACTURA) . "</a> guardado correctamente.");
                    if( (string) $this->reserva->getEstado() === estado_reserva::SINSENA) {
                        $this->reserva->setEstado(estado_reserva::get(estado_reserva::SENADO));
                    }
                    $this->reserva->setFacturaCliente($this->factura);
                    if($this->reserva->save()) {
                        $this->new_message($this->reserva->getSuccesMessage());
                    } else {
                        $this->new_error_msg("Error al actualizar la reserva!");
                    }
                    header('Location: '.$this->factura->url());
                }
            } else {
                $this->factura->delete();
                $this->new_error_msg("¡Imposible guardar el ".FS_FACTURA." para la reserva!");
            }
        } else {
            $this->new_error_msg("¡Imposible guardar el ".FS_FACTURA." para la reserva!");
        }
    }

    public function getReserva() {
        return $this->reserva;
    }

    public function getCliente() {
        return $this->cliente;
    }

    public function getPais() {
        return $this->pais;
    }

    public function getDireccion() {
        return $this->direccion;
    }

    public function getAgente() {
        return $this->agente;
    }

    public function getAlmacen() {
        return $this->almacen;
    }

    public function getSerie() {
        return $this->serie;
    }

    public function getDivisa() {
        return $this->divisa;
    }

    public function getFactura() {
        return $this->factura;
    }

    public function getFormaPago() {
        return $this->forma_pago;
    }

    private function share_extensions() {
        $extensiones = array(
            array(
                'name' => 'pago_reserva',
                'page_from' => 'tab_pagos',
                'page_to' => 'reserva_pagos',
                'type' => 'tab',
                'text' => '<span class="glyphicon glyphicon-piggy-bank" aria-hidden="true"></span><span class="hidden-xs">&nbsp; Pagos</span>',
                'params' => '&factura=TRUE'
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