<?php

require_model('reserva.php');
require_model('cliente.php');
require_model('direccion_cliente.php');
require_model('pais.php');
require_model('agente.php');
require_model('almacen.php');
require_model('serie.php');
require_model('divisa.php');
require_model('albaran_cliente.php');
require_model('forma_pago.php');

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 12/08/2015
 * Time: 09:36 PM
 */
class reserva_pagos extends fs_controller {

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
     * @var albaran_cliente
     */
    protected $albaran;

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
        $this->albaran = new albaran_cliente();
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
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_albaran'])) {
            $this->__createAlbaran();
        }
        $this->share_extensions();
    }

    private function __createAlbaran() {
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
        $this->albaran->fecha = $fecha;
        $this->albaran->hora = $hora;
        $this->albaran->codalmacen = $almacen->codalmacen;
        $this->albaran->codejercicio = $ejercicio->codejercicio;
        $this->albaran->codserie = $serie->codserie;
        $this->albaran->codpago = $forma_pago->codpago;
        $this->albaran->coddivisa = $divisa->coddivisa;
        $this->albaran->tasaconv = $divisa->tasaconv;
        $this->albaran->codagente = $this->agente->codagente;
        $this->albaran->numero2 = '';
        $this->albaran->observaciones = '';
        $this->albaran->irpf = $serie->irpf;
        $this->albaran->porcomision = $this->agente->porcomision;
        $this->albaran->codcliente = $this->cliente->codcliente;
        $this->albaran->cifnif = $this->cliente->cifnif;
        $this->albaran->nombrecliente = $this->cliente->razonsocial;
        $this->albaran->ciudad = $this->direccion->ciudad;
        $this->albaran->codpais = $this->direccion->codpais;
        $this->albaran->codpostal = $this->direccion->codpostal;
        $this->albaran->direccion = $this->direccion->direccion;
        $this->albaran->provincia = $this->direccion->provincia;

        if($this->albaran->save()) {
            $art0 = new articulo();
            $linea = new linea_albaran_cliente();
            $linea->idalbaran = $this->albaran->idalbaran;
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
                //Calcula totales del albaran con la reserva agregada
                $this->albaran->neto += $linea->pvptotal;
                $this->albaran->totaliva += ($linea->pvptotal * $linea->iva / 100);
                $this->albaran->totalirpf += ($linea->pvptotal * $linea->irpf / 100);
                $this->albaran->totalrecargo += ($linea->pvptotal * $linea->recargo / 100);

                /// redondeamos
                $this->albaran->neto = round($this->albaran->neto, FS_NF0);
                $this->albaran->totaliva = round($this->albaran->totaliva, FS_NF0);
                $this->albaran->totalirpf = round($this->albaran->totalirpf, FS_NF0);
                $this->albaran->totalrecargo = round($this->albaran->totalrecargo, FS_NF0);
                $this->albaran->total = $this->albaran->neto + $this->albaran->totaliva - $this->albaran->totalirpf + $this->albaran->totalrecargo;
                if($this->albaran->save()) {
                    $this->new_message("<a href='" . $this->albaran->url() . "'>" . ucfirst(FS_ALBARAN) . "</a> guardado correctamente.");
                    $this->reserva->setAlbaranCliente($this->albaran);
                    if($this->reserva->save()) {
                        $this->new_message($this->reserva->getSuccesMessage());
                    } else {
                        $this->new_error_msg("Error al actualizar la reserva!");
                    }
                    header('Location: '.$this->albaran->url());
                }
            } else {
                $this->albaran->delete();
                $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN." para la reserva!");
            }
        } else {
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN." para la reserva!");
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

    public function getAlbaran() {
        return $this->albaran;
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
                'params' => '&albaran=TRUE'
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