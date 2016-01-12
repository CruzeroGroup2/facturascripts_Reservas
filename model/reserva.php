<?php

require_once 'base/fs_model.php';

require_model('cliente.php');
require_model('habitacion.php');
require_model('tarifa_reserva.php');
require_model('estado_reserva.php');
require_model('grupo_clientes.php');
require_model('habitacion_por_reserva.php');
require_model('pasajero_por_reserva.php');
require_model('factura_cliente.php');
require_model('recibo_cliente.php');


class reserva extends fs_model {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $codcliente;

    /**
     * @var cliente
     */
    protected $cliente;

    /**
     * @var habitacion_por_reserva[]
     */
    protected $habitaciones;

    /**
     * @var int
     */
    protected $idcategoriahabitacion;

    /**
     * @var pasajero_por_reserva[]
     */
    protected $pasajeros;

    /**
     * @var int
     */
    protected $idtarifa;

    /**
     * @var tarifa_reserva
     */
    protected $tarifa;

    /**
     * @var int
     */
    protected $idestado;

    /**
     * @var estado_reserva
     */
    protected $estado;

    /**
     * @var string
     */
    protected $codgrupo;

    /**
     * @var grupo_clientes
     */
    protected $grupo_clientes;

    /**
     * @var string
     */
    protected $fecha_in;

    /**
     * @var string
     */
    protected $fecha_out;

    /**
     * @var int
     */
    protected $cantidad_adultos;

    /**
     * @var int
     */
    protected $cantidad_menores;

    /**
     * @var bool
     */
    protected $media_pension = false;

    /**
     * @var float
     */
    protected $descuento;

    /**
     * @var int
     */
    protected $idfactura;

    /**
     * @var factura_cliente
     */
    protected $factura_cliente;

    /**
     * @var recibo_cliente[]
     */
    protected $pagos;

    /**
     * @var string
     */
    protected $comentario;

    /**
     * @var string
     */
    protected $create_date;

    /**
     * @var string
     */
    protected $update_date;

    /** @var int */
    protected $codagente;

    /**
     * @var string
     */
    protected $cancel_date;

    /**
     * @var array
     */
    public $totales;

    /**
     * @var array
     */
    protected $refundPercentages;

    /**
     * @var string[]
     */
    private $remover_pasajeros;

    /**
     * @var string[]
     */
    private $remover_habitaciones;

    private $edit = false;

    const DATE_FORMAT = 'd-m-Y';
    const DATE_FORMAT_FULL = 'd-m-Y H:i:s';

    // Fecha de cancelacion <= 7 días no refund
    const CANCEL_NO_REFUND = -7;
    // Fecha de cancelacion >=8 && <= 15 30% de refund - gastos de envio
    const CANCEL_PART_REFUND = -15;
    // Fecha de cancelacion >= 16 días 100% de refund menos gastos de envios
    const CANCEL_FULL_REFUND = -16;

    const CACHE_KEY_ALL = 'reserva_reserva_all';
    const CACHE_KEY_SINGLE = 'reserva_reserva_{id}';

    public function __construct($data = array()) {
        parent::__construct('reserva', 'plugins/reservas/');

        $this->refundPercentages = array(
            reserva::CANCEL_FULL_REFUND => 100,
            reserva::CANCEL_PART_REFUND => 30,
            reserva::CANCEL_NO_REFUND => 0
        );

        $this->setValues($data);
    }

    /**
     * @param array $data
     *
     * @return reserva
     */
    public function setValues($data = array()) {
        $tarifa = new tarifa_reserva();
        $this->setId($data);
        //Datos básicos de la reserva
        //Obtener informacion relacionada al cliente
        $this->codcliente = (isset($data['codcliente'])) ? $data['codcliente'] : null;

        //Fecha ingreso
        if(isset($data['fecha_in'])) {
            $this->setFechaIn($data['fecha_in']);
        }
        //Fecha egreso
        if(isset($data['fecha_out'])) {
            $this->setFechaOut($data['fecha_out']);
        }

        $this->remover_habitaciones = (isset($data['remover_habitaciones'])) ? $data['remover_habitaciones'] : array();
        $this->removeHabitaciones();
        //Habitaciones
        if(isset($data['idsHabitaciones']) && $data['idsHabitaciones']) {
            $this->setHabitaciones(explode(',', $data['idsHabitaciones']));
        }

        $this->cantidad_adultos = (isset($data['cantidad_adultos'])) ? (int) $data['cantidad_adultos'] : null;

        $this->cantidad_menores = (isset($data['cantidad_menores'])) ? (int) $data['cantidad_menores'] : null;

        if(isset($data['create_date']) && !$this->create_date) {
            $this->setEdit();
            $this->create_date = $data['create_date'];
        } elseif (!isset($data['create_date']) && !$this->create_date) {
            $this->create_date = date('Y-m-d H:i:s');;
        }

        if(isset($data['update_date'])) {
            $this->update_date = $data['update_date'];
        }

        $this->cancel_date = (isset($data['cancel_date'])) ? $data['cancel_date'] : null;

        //CodAgente
        $this->codagente = (isset($data['codagente'])) ? (int) $data['codagente'] : null;

        //Tarifa
        $this->idtarifa = (isset($data['idtarifa'])) ? $data['idtarifa'] : null;
        //Media pension
        $this->media_pension = (isset($data['media_pension']) && $data['media_pension']) ? true : false;

        $this->remover_pasajeros = (isset($data['remover_pasajeros'])) ? $data['remover_pasajeros'] : array();
        $this->removePasajeros();
        if(isset($data['pasajeros'])) {
            $this->setPasajeros($data['pasajeros']);
        } elseif(isset($data['pasajero'])) {
            $this->setPasajeros($data['pasajero']);
        } else {
            //$this->getPasajeros();
        }

        $this->descuento = (isset($data['descuento'])) ? $data['descuento'] : 0;

        if(isset($data['idcategoria'])) {
            $this->idcategoriahabitacion = $data['idcategoria'];
            $this->setTarifa($tarifa->fetchByCategoriaYTipoPasajero(
                $this->getCategoriaHabitacion(),
                $this->getCodGrupoCliente()
            ));
        }

        //Obtener el estado de la reserva
        $this->idestado = (isset($data['idestado'])) ? (int) $data['idestado'] : null;

        //Factura de la reserva
        $this->idfactura = (isset($data['idfactura'])) ? $data['idfactura'] : null;

        //
        $this->comentario = (isset($data['comentario'])) ? $data['comentario'] : null;

        return $this;
    }

    /**
     * @param bool|true $value
     *
     * @return $this
     */
    public function setEdit($value = true) {
        $this->edit = $value;
        return $this;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param array $data
     *
     * @return reserva
     */
    public function setId($data) {
        // This is an ugly thing use an Hydrator insted
        if(is_int($data)) {
            $this->id = $data;
        }

        if(is_array($data)) {
            if(isset($data['idreserva'])) {
                $this->id = $data['idreserva'];
            }

            if(isset($data['id'])) {
                $this->id = $data['id'];
            }
        }

        if($this->habitaciones) {
            foreach($this->habitaciones as $habitacion) {
                $habitacion->setIdReserva($this->id);
            }
        }

        if($this->pasajeros) {
            foreach($this->pasajeros as $pasajero) {
                $pasajero->setIdReserva($this->id);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCodCliente() {
        return $this->codcliente;
    }

    /**
     * @param $codcliente
     *
     * @return reserva
     */
    public function setCodCliente($codcliente) {
        $this->codcliente = $codcliente;

        return $this;
    }

    /**
     * @return cliente
     */
    public function getCliente() {
        if($this->codcliente && !$this->cliente) {
            $this->cliente = $this->get_cliente($this->codcliente);
            $this->codgrupo = $this->cliente->codgrupo;
        }

        return $this->cliente;
    }

    /**
     * @return string
     */
    public function getIncialesCliente() {
        if(!$this->cliente) {
            $this->getCliente();
        }
        $partes = explode(' ', ucwords($this->cliente->nombre));
        $res = '';
        foreach($partes as $parte) {
            if($parte) {
                $res .= $parte[0] . '.';
            }
        }
        return $res;
    }

    /**
     * @param cliente $cliente
     *
     * @return reserva
     */
    public function setCliente(cliente $cliente) {
        $this->cliente = $cliente;
        $this->codcliente = $cliente->codcliente;
        $this->codgrupo = $cliente->codgrupo;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodGrupoCliente() {
        if(!$this->codgrupo && $this->codcliente) {
            $this->getCliente();
        }

        return $this->codgrupo;
    }

    /**
     * @return grupo_clientes
     */
    public function getGrupoCliente() {
        if($this->getCliente() && !$this->grupo_clientes) {
            $this->grupo_clientes = $this->get_grupo_clientes($this->codgrupo);
        }

        return $this->grupo_clientes;
    }

    /**
     * @param $stringify
     *
     * @return string[]|habitacion_por_reserva[]
     */
    public function getHabitaciones($stringify = false) {
        if(!$this->habitaciones) {
            $this->habitaciones = $this->get_habitaciones($this->getId());
        }

        $result = array();
        if($stringify) {
            foreach($this->habitaciones as $habitacion) {
                $result[] = (string) $habitacion;
            }
        } else {
            $result = $this->habitaciones;
        }

        return $result;
    }

    /**
     * @param array $habitaciones
     *
     * @return reserva
     */
    public function setHabitaciones($habitaciones = array()) {
        $this->habitaciones = array();
        foreach($habitaciones as $habitacion) {
            if(is_a($habitacion, 'habitacion_por_reserva')) {
                $this->habitaciones[] = $habitacion;
            } else {
                $this->habitaciones[] = $this->__parseHabitacion($habitacion);
            }
        }

        return $this;
    }

    /**
     * @param $string
     *
     * @return habitacion_por_reserva
     */
    private function __parseHabitacion($string) {
        $datos_habitacion = explode(':', $string);
        //Si no está el idreserva
        if(!isset($datos_habitacion[1])) {
            $datos_habitacion[1] = $this->getId();
        }
        //Si no está el idhabitacionporreserva
        if(!isset($datos_habitacion[2])) {
            $datos_habitacion[2] = null;
        }
        return new habitacion_por_reserva(array(
            'idhabitacion' => $datos_habitacion[0],
            'idreserva' => $datos_habitacion[1],
            'id' => $datos_habitacion[2]
        ));
    }

    public function getNumerosHabitaciones() {
        if(!$this->habitaciones) {
            $this->habitaciones = $this->get_habitaciones($this->getId());
        }

        $result = array();
        foreach($this->habitaciones as $habitacion) {
            $result[] = $habitacion->getHabitacion()->getNumero();
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getCategoriaHabitacion() {
        if(!$this->idcategoriahabitacion) {
            $this->getHabitaciones();
            if ($this->habitaciones) {
                //Obtengo una de las habitaciones
                $hab = $this->habitaciones[0];
                $this->idcategoriahabitacion = $hab->getHabitacion()->getIdCategoria();
            }
        }

        return $this->idcategoriahabitacion;
    }

    /**
     * @return int
     */
    public function getIdTarifa() {
        return $this->idtarifa;
    }

    /**
     * @param int $idtarifa
     *
     * @return reserva
     */
    public function setIdTarifa($idtarifa) {
        $this->idtarifa = $idtarifa;

        return $this;
    }

    /**
     * @return tarifa_reserva
     */
    public function getTarifa() {
        if($this->idtarifa && !$this->tarifa) {
            $this->tarifa = $this->get_tarifa($this->idtarifa);
            //$this->calcularTotales();
        }

        return $this->tarifa;
    }

    /**
     * @param tarifa_reserva $tarifa
     *
     * @return reserva
     */
    public function setTarifa(tarifa_reserva $tarifa) {
        $this->tarifa = $tarifa;
        $this->idtarifa = $tarifa->getId();
        $this->calcularTotales(true);

        return $this;
    }

    /**
     * @return int
     */
    public function getIdEstado() {
        return $this->idestado;
    }

    /**
     * @param int $idestado
     *
     * @return reserva
     */
    public function setIdEstado($idestado) {
        $this->idestado = $idestado;

        return $this;
    }

    /**
     * @return estado_reserva
     */
    public function getEstado() {
        if(!$this->estado) {
            $this->estado = $this->get_estado($this->idestado);
        }

        return $this->estado;
    }

    /**
     * @param estado_reserva $estado
     *
     * @return reserva
     */
    public function setEstado(estado_reserva $estado) {
        $this->estado = $estado;
        $this->idestado = $estado->getId();

        return $this;
    }

    /**
     * @return string
     */
    public function getFechaIn($full_date = false) {
        $fecha = new DateTime($this->fecha_in);
        $format = self::DATE_FORMAT;
        if($full_date) {
            $format = self::DATE_FORMAT_FULL;
        }

        return $fecha->format($format);
    }

    /**
     * @param string $fecha_in
     *
     * @return reserva
     */
    public function setFechaIn($fecha_in) {
        $date = new DateTime($fecha_in);
        $this->fecha_in = $date->format(self::DATE_FORMAT) . ' 12:00:00';

        return $this;
    }

    /**
     * @return string
     */
    public function getFechaOut($full_date = false) {
        $ret = null;
        if($this->fecha_out) {
            $fecha = new DateTime($this->fecha_out);
            $format = self::DATE_FORMAT;
            if($full_date) {
                $format = self::DATE_FORMAT_FULL;
            }
            $ret = $fecha->format($format);
        }

        return $ret;
    }

    /**
     * @param string $fecha_out
     *
     * @return reserva
     */
    public function setFechaOut($fecha_out) {
        if($fecha_out) {
            $date = new DateTime($fecha_out);
            if($fecha_out == $this->getFechaIn()) {
                $date->modify('+1 day');
            }
            $this->fecha_out = $date->format(self::DATE_FORMAT) . ' 10:00:00';
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCantidadDias() {
        $date1 = new DateTime($this->getFechaIn());
        $date2 = new DateTime($this->getFechaOut());

        return $date2->diff($date1)->format("%a");
    }

    /**
     * @return int
     */
    public function getCantidadAdultos() {
        if($this->cantidad_adultos === null && is_array($this->pasajeros)) {
            $result = 0;
            foreach($this->pasajeros as $huesped) {
                if($huesped->esAdulto()) {
                    $result ++;
                }
            }
            $this->cantidad_adultos = $result;
        }

        return $this->cantidad_adultos;
    }

    /**
     * @param int $cantidad_adultos
     *
     * @return reserva
     */
    public function setCantidadAdultos($cantidad_adultos = 0) {
        $this->cantidad_adultos = $cantidad_adultos;

        return $this;
    }

    /**
     * @return int
     */
    public function getCantidadMenores() {
        if($this->cantidad_menores === null && is_array($this->pasajeros)) {
            $result = 0;
            foreach($this->pasajeros as $huesped) {
                if($huesped->esMenor()) {
                    $result ++;
                }
            }
            $this->cantidad_menores = $result;
        }

        return $this->cantidad_menores;
    }

    /**
     * @param int $cantidadMenores
     *
     * @return reserva
     */
    public function setCantidadMenores($cantidadMenores = 0) {
        $this->cantidad_menores = $cantidadMenores;

        return $this;
    }

    /**
     * @param bool|false $as_array
     *
     * @return int|int[]
     */
    public function getCantPasajeros($as_array = false) {
        if(!$this->pasajeros) {
            $this->getPasajeros();
        }

        $cantidad = $this->cantidad_adultos + $this->cantidad_menores;
        if(!$cantidad) {
            $cantidad = count($this->pasajeros);
        }

        $ret = $cantidad;
        if($as_array) {
            $ret = range(0, $cantidad-1);
        }

        return $ret;
    }

    /**
     * @param $stringify
     *
     * @return string[]|pasajero_por_reserva[]
     */
    public function getPasajeros($stringify = false) {
        if(!$this->pasajeros) {
            $this->setPasajeros($this->get_pasajeros($this->getId()));
        }

        $result = array();
        if($stringify) {
            foreach($this->pasajeros as $pasajero) {
                $result[] = (string) $pasajero;
            }
        } else {
            $result = $this->pasajeros;
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return reserva
     */
    public function setPasajeros($data = array()) {
        //No estaría siendo hábil para solucionar este problema :-|

        //Crear 3 arrays $adultos y $menores y $baibies
        $adultos = array();
        $menores_7 = array();
        $menores_3 = array();

        // If there are passangers in $data
        if($data) {
            foreach($data as $pasajero) {
                if(is_a($pasajero, 'pasajero_por_reserva')) {
                    $tmpPass = $pasajero;
                } else {
                    $tmpPass = $this->__parsePasajero($pasajero);
                }
                /** @var pasajero_por_reserva $tmpPass*/
                switch($tmpPass->getEdadCateg()) {
                    case 'adulto':
                        $adultos[] = $tmpPass;
                        break;
                    case 'menor_7':
                        $menores_7[] = $tmpPass;
                        break;
                    case 'menor_3':
                        $menores_3[] = $tmpPass;
                        break;
                }
            }
        }

        if((!$adultos && $this->cantidad_adultos) || count($adultos) < $this->cantidad_adultos) {
            foreach(range(0, ($this->cantidad_adultos-1)) as $i) {
                if(!isset($adultos[$i])) {
                    $tmpPasajero = new pasajero_por_reserva();
                    $tmpPasajero->asAdult();
                    $tmpPasajero->setCodGrupo($this->getCodGrupoCliente());
                    $tmpPasajero->setFechaIn($this->getFechaIn());
                    $tmpPasajero->setFechaOut($this->getFechaOut());
                    $tmpPasajero->setTarifa($this->getTarifa());
                    $adultos[] = clone $tmpPasajero;
                    unset($tmpPasajero);
                }
            }
        }

        if(count($adultos) != $this->cantidad_adultos) {
            $this->cantidad_adultos = count($adultos);
        }

        if((!$menores_7 && $this->cantidad_menores != 0) || count($menores_7) < $this->cantidad_menores) {
            foreach (range(0, ($this->cantidad_menores - 1)) as $i) {
                if (!isset($menores_7[$this->cantidad_adultos + $i])) {
                    $tmpPasajero = new pasajero_por_reserva();
                    $tmpPasajero->asMenor();
                    $tmpPasajero->setCodGrupo($this->getCodGrupoCliente());
                    $tmpPasajero->setFechaIn($this->getFechaIn());
                    $tmpPasajero->setFechaOut($this->getFechaOut());
                    $tmpPasajero->setTarifa($this->getTarifa());
                    $menores_7[] = clone $tmpPasajero;
                    unset($tmpPasajero);
                }
            }
        }

        if(count($menores_7) != $this->cantidad_menores) {
            $this->cantidad_menores = count($menores_7);
        }

        $this->pasajeros = array_merge($adultos, $menores_7, $menores_3);
        //
        return $this;
    }

    /**
     * @param $string
     *
     * @return pasajero_por_reserva
     */
    private function __parsePasajero($string) {
        return pasajero_por_reserva::parse($string, $this);
    }

    /**
     * @param $index
     *
     * @return null|pasajero_por_reserva
     */
    public function getPasajero($index) {
        if(!$this->pasajeros) {
            $this->getPasajeros();
        }
        $tmp = new pasajero_por_reserva();
        $tmp->setIdReserva($this->getId());

        $pass  = $this->pasajeros[ $index ];
        return ($pass) ? $pass : $tmp;
    }

    /**
     * @return bool
     */
    public function getMediaPension() {
        return $this->media_pension;
    }

    /**
     * @param bool|false $media_pension
     *
     * @return reserva
     */
    public function setMediaPension($media_pension = false) {
        $this->media_pension = $media_pension;

        return $this;
    }

    /**
     * @return float
     */
    public function getDescuento() {
        return $this->descuento;
    }

    /**
     * @param float|int $descuento
     *
     * @return reserva
     */
    public function setDescuento($descuento = 0) {
        $this->descuento = floatval($descuento);

        return $this;
    }

    /**
     * @return int
     */
    public function getIdFactura() {
        return $this->idfactura;
    }

    /**
     * @param int $idfactura
     *
     * @return reserva
     */
    public function setIdFactura($idfactura) {
        $this->idfactura = $idfactura;

        return $this;
    }

    /**
     * @return factura_cliente
     */
    public function getFacturaCliente() {
        if(!$this->factura_cliente && $this->idfactura) {
            $this->factura_cliente = $this->get_factura($this->idfactura);
        }
        return $this->factura_cliente;
    }

    /**
     * @param factura_cliente $factura_cliente
     *
     * @return reserva
     */
    public function setFacturaCliente(factura_cliente $factura_cliente) {
        $this->factura_cliente = $factura_cliente;
        $this->idfactura = $factura_cliente->idfactura;

        return $this;
    }

    public function getPagos() {
        $this->pagos = array();
        if(!$this->pagos && $this->idfactura) {
            $pago = new recibo_cliente();
            $this->pagos = $pago->all_from_factura($this->idfactura);
        }
        return $this->pagos;
    }

    public function getMontoSeniado() {
        $pagos = $this->getPagos();
        $totalSeniado = 0.0;
        foreach($pagos as $pago) {
            $totalSeniado += $pago->importe;
        }

        return $totalSeniado;
    }

    public function getSaldo() {
        $total = $this->getTotal();
        $senia = $this->getMontoSeniado();

        return floatval($total-$senia);
    }

    /**
     * @return string
     */
    public function getComentario() {
        return $this->comentario;
    }

    /**
     * @param string $comentario
     *
     * @return reserva
     */
    public function setComentario($comentario = '') {
        $this->comentario = $comentario;

        return $this;
    }

    /**
     * @param DateTime $cancelDate
     *
     * @return reserva
     */
    public function setCancelDate(DateTime $cancelDate) {
        $this->cancel_date = $cancelDate->format(self::DATE_FORMAT_FULL);

        return $this;
    }

    /**
     * @return string
     */
    public function getCancelDate($full_date = false) {
        $ret = null;
        if($this->cancel_date) {
            $fecha = new DateTime($this->cancel_date);
            $format = self::DATE_FORMAT;
            if($full_date) {
                $format = self::DATE_FORMAT_FULL;
            }
            $ret = $fecha->format($format);
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getCreateDate($full_date = false) {
        $ret = null;
        if($this->create_date) {
            $fecha = new DateTime($this->create_date);
            $format = self::DATE_FORMAT;
            if($full_date) {
                $format = self::DATE_FORMAT_FULL;
            }
            $ret = $fecha->format($format);
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getUpdateDate($full_date = false) {
        $ret = null;
        if($this->update_date) {
            $fecha = new DateTime($this->update_date);
            $format = self::DATE_FORMAT;
            if($full_date) {
                $format = self::DATE_FORMAT_FULL;
            }
            $ret = $fecha->format($format);
        }

        return $ret;
    }

    /**
     * @return int
     */
    public function getCodAgente() {
        return $this->codagente;
    }

    /**
     * @param int $codagente
     *
     * @return reserva
     */
    public function setCodAgente($codagente) {
        $this->codagente = $codagente;

        return $this;
    }

    /**
     * @return string
     */
    public function getExpireDate($full_date = false) {
        $ret = null;
        if($this->create_date) {
            $fecha = new DateTime($this->create_date);
            $fecha->modify("+ 7 days");
            $format = self::DATE_FORMAT;
            if($full_date) {
                $format = self::DATE_FORMAT_FULL;
            }
            $ret = $fecha->format($format);
        }

        return $ret;
    }

    public function getCancelDays() {
        $cancelDate = new DateTime($this->getCancelDate());
        $reservaDate = new DateTime($this->getFechaIn());
        $diasCancel = intval($reservaDate->diff($cancelDate, false)->format('%R%a'));

        return $diasCancel;
    }

    public function getRefundAmount() {
        $refundStatus = $this->getRefundStatus();
        $refundPercentage = $this->refundPercentages[$refundStatus];

        return $this->getTotal() * ($refundPercentage/100);
    }

    public function getRefundStatus() {
        $diasCancel = $this->getCancelDays();
        if($diasCancel >= reserva::CANCEL_NO_REFUND) {
            return reserva::CANCEL_NO_REFUND;
        } elseif ($diasCancel >= reserva::CANCEL_PART_REFUND) {
            return reserva::CANCEL_PART_REFUND;
        } elseif ($diasCancel <= reserva::CANCEL_FULL_REFUND) {
            return reserva::CANCEL_FULL_REFUND;
        } else {
            trigger_error("La cantidad de dias de cancelacion da el valor '$diasCancel' que no está comtenplado", E_USER_ERROR);
        }
    }

    public function getCancelMessage() {
        $refund = $this->getRefundStatus();
        $msg = '';
        switch ($refund) {
            case reserva::CANCEL_NO_REFUND:
                //echo 'NO REFUND!'; //No refund at all!
                $msg = 'La cancelacion de esta reserva no generá ningún tipo de devolucion al cliente';
                break;
            case reserva::CANCEL_PART_REFUND:
                //echo '30% REFUND!';
                $msg = 'La cancelacion de esta reserva generá una nota de credito del 30% del monto menos los gastos de envio al cliente';
                break;
            case reserva::CANCEL_FULL_REFUND:
                //echo '100% REFUND!';
                $msg = 'La cancelacion de esta reserva generá una nota de credito del 100% del monto menos los gastos de envio al cliente';
                break;
        }

        return $msg;

    }

    /**
     * @return int
     */
    public function getStep() {
        if(!$this->getFechaOut()) {
            return 1;
        }

        if(!$this->getHabitaciones()) {
            return 2;
        }

        if($this->getHabitaciones() && $this->getCliente()) {
            return 3;
        }

    }

    public function getSuccesMessage() {
        if($this->getStep() > 2) {
            return "Reserva actualizada correctamente!";
        } else {
            return "Reserva agregada correctamente!";
        }
    }

    public function getCheckIn() {
        $ret = false;
        $estado = (string) $this->getEstado();
        $today = new DateTime();
        $resDate = new DateTime($this->getFechaIn(true));
        if((($estado == estado_reserva::PAGO || $estado == estado_reserva::SENADO) ||
           ($estado == estado_reserva::CHECKIN  && $this->getCantPasajeros() != $this->getCheckInPasajeros())) &&
           $today >= $resDate
        ) {
            $ret = true;
        }

        return $ret;
    }

    public function getCheckOut() {
        return $this->isCheckedIn();
    }

    public function isCheckedIn() {
        return (string) $this->getEstado() == estado_reserva::CHECKIN;
    }

    public function allPassangersCheckedIn() {
        return $this->getCantPasajeros() == pasajero_por_reserva::getCountPassagerosCheckInPorRes($this->getId());
    }

    public function isCanceled() {
        return (string) $this->getEstado() == estado_reserva::CANCELADA;
    }

    public function getMaxPasajeros() {
        $habitaciones = $this->getHabitaciones();
        $cant = 0;
        foreach($habitaciones as $habitacion) {
            $cant += (int) $habitacion->getHabitacion()->getPlazaMaxima();
        }

        return $cant;
    }

    protected function install() {
        $cliente = new cliente();
        $habitacion = new habitacion();
        $tarifa = new tarifa_reserva();
        $estado = new estado_reserva();
        $habporres = new habitacion_por_reserva();
        $facturas = new factura_cliente();
        $pago = new pago_recibo_cliente();

        return '';
    }

    public function url() {
        if(!$this->id) {
            return 'index.php?page=reserva_home';
        } else {
            return 'index.php?page=reserva_home&action=edit&id='.$this->id;
        }
    }

    /**
     * @param $id
     *
     * @return bool|reserva
     */
    public static function get($id) {
        $reserva = new self();

        return $reserva->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool|reserva
     */
    public function fetch($id) {
        $reserva = $this->cache->get(str_replace('{id}',$id,self::CACHE_KEY_SINGLE));
        if($id && !$reserva) {
            $reserva = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int) $id . ";");
            $this->cache->set(str_replace('{id}',$id,self::CACHE_KEY_SINGLE), $reserva);
        }
        if($reserva) {
            return new reserva($reserva[0]);
        } else {
            return false;
        }
    }

    /**
     * @return reserva[]
     */
    public function fetchAll() {
        $reservalist = array();
        $reservas = $this->cache->get(self::CACHE_KEY_ALL);
        if(!$reservas) {
            $reservas = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY fecha_in ASC;");
            $this->cache->set(self::CACHE_KEY_ALL, $reservas);
        }
        foreach($reservas as $reserva) {
            $reservalist[] = new reserva($reserva);
        }

        return $reservalist;
    }

    public function find($filter = array()) {
        $sql = "SELECT
$this->table_name.*
FROM " . $this->table_name . "
    -- JOIN habitacion_por_reserva ON (reserva.id = habitacion_por_reserva.idreserva)
WHERE ";
        if(isset($filter['codcliente'])) {
            $sql .= "\n" . ' codcliente = ' . $this->var2str($filter['codcliente']);
        }

        $sql .= "\n" . ' GROUP BY reserva.id';

        if(isset($filter['order_by'])) {
            $sql .= "\n" . ' ORDER BY ' . $filter['order_by'];
        }

        $reservalist = array();
        $reservas = $this->db->select($sql);
        if($reservas) {
            foreach($reservas as $reserva) {
                $reservalist[] = new reserva($reserva);
            }
        }

        return $reservalist;    }

    /**
     * @param $idhabitacion
     * @param $fecha
     *
     * @return reserva[]
     */
    public function findByHabitacionYFecha($idhabitacion, $fecha, $limit = false) {
        //TODO: Add cache for this query
        $sql = "SELECT
$this->table_name.*
FROM " . $this->table_name . "
    JOIN habitacion_por_reserva ON (reserva.id = habitacion_por_reserva.idreserva)
    JOIN estado_reserva ON (reserva.idestado = estado_reserva.id)
WHERE
    idhabitacion = $idhabitacion AND
    (fecha_in <= '$fecha 12:00' AND fecha_out >= '$fecha 10:00') AND
    estado_reserva.descripcion NOT IN ('" . estado_reserva::CANCELADA . "', '" . estado_reserva::FINALIZADA . "')
";
        if($limit) {
            $sql .= "\nLIMIT $limit";
        }
        $reservalist = array();
        $reservas = $this->db->select($sql);
        if($reservas) {
            foreach($reservas as $reserva) {
                $reservalist[] = new reserva($reserva);
            }
        }

        return $reservalist;
    }

    /**
     * @param $idestado
     * @param $fecha
     *
     * @return array
     */
    public function findByEstadoYFecha($idestado, $fecha) {
        $reservalist = array();
        $reservas = $this->db->select('SELECT * FROM ' . $this->table_name . ' WHERE idestado = ' . $idestado . ' AND fecha_in >= ' . $this->var2str($fecha . ' 12:00:00'));
        if($reservas) {
            foreach($reservas as $reserva) {
                $reservalist[] = new reserva($reserva);
            }
        }

        return $reservalist;
    }

    /**
     * @param $idestado
     *
     * @return array
     */
    public function findByEstado($idestado) {
        return $this->findByEstadoYFecha($idestado, date('Y-m-d'));
    }

    /**
     * @return bool|array
     */
    public function exists() {
        if(is_null($this->id)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int) $this->id . ";");
        }
    }

    /**
     * @return bool
     */
    public function test() {
        $status = false;
        $this->id = (int) $this->id;

        if($this->getCodGrupoCliente() == 0) {
            $this->new_error_msg("Grupo de Cliente no válido.");
        }

        if($this->getCantidadDias() < 1) {
            $this->new_error_msg("Fecha de reserva no válida.");
        }

        $fechaIn = new DateTime($this->getFechaIn(true));
        $fechaHoy = new DateTime();
        if($fechaIn < $fechaHoy) {
            $this->new_error_msg("La fecha de la reserva es menor a la fecha de hoy");
        }

        if(!$this->edit && $this->getCreateDate() != date(self::DATE_FORMAT)) {
            $this->new_error_msg("La fecha de creación es inválida");
        }

        if(!$this->getCodAgente()) {
            $this->new_error_msg("El código de agente es requerido");
        }

        if(!$this->get_errors()) {
            $status = true;
        }


        return $status;
    }

    protected function insert() {
        $sql = 'INSERT ' . $this->table_name .
               ' SET ' .
               'codagente = ' . $this->var2str($this->getCodAgente()) . ',' .
               'create_date = ' . $this->var2str($this->getCreateDate(true)) . ',' .
               'codcliente = ' . $this->var2str($this->getCodCliente()) . ',' .
               'idtarifa = ' . $this->intval($this->getIdTarifa()) . ',' .
               'idestado = ' . $this->intval($this->getEstado()->getId()) . ',' .
               'fecha_in = ' . $this->var2str($this->getFechaIn(true)) . ',' .
               'fecha_out = ' . $this->var2str($this->getFechaOut(true)) . ',' .
               'cantidad_adultos = ' . $this->intval($this->getCantidadAdultos()) . ',' .
               'cantidad_menores = ' . $this->intval($this->getCantidadMenores()) . ',' .
               'media_pension = ' . $this->intval($this->getMediaPension()) . ',' .
               'descuento = ' . floatval($this->getDescuento()) . ',' .
               'comentario = ' . $this->var2str($this->getComentario());
        if($this->idfactura) {
            $sql .= ', idfactura = ' . $this->idfactura;
        }
        $sql .= ';';

        $ret = $this->db->exec($sql);

        return $ret;
    }

    protected function update() {
        $sql = 'UPDATE ' . $this->table_name .
               ' SET ' .
               'codagente = ' . $this->var2str($this->getCodAgente()) . ',' .
               'update_date = ' . $this->var2str(date('Y-m-d H:i:s')) . ',' .
               'codcliente = ' . $this->var2str($this->getCodCliente()) . ',' .
               'idtarifa = ' . $this->intval($this->getIdTarifa()) . ',' .
               'idestado = ' . $this->intval($this->getEstado()->getId()) . ',' .
               'fecha_in = ' . $this->var2str($this->getFechaIn(true)) . ',' .
               'fecha_out = ' . $this->var2str($this->getFechaOut(true)) . ',' .
               'cantidad_adultos = ' . $this->intval($this->getCantidadAdultos()) . ',' .
               'cantidad_menores = ' . $this->intval($this->getCantidadMenores()) . ',' .
               'media_pension = ' . $this->intval($this->getMediaPension()) . ',' .
               'descuento = ' . floatval($this->getDescuento()) . ',' .
               'comentario = ' . $this->var2str($this->getComentario());
        if($this->idfactura) {
            $sql .= ', idfactura = ' . $this->idfactura;
        }

        if($this->cancel_date) {
            $sql .= ', cancel_date = ' . $this->var2str($this->getCancelDate(true));
        }
        $sql .= ' WHERE id = ' . $this->getId() . ';';

        $ret = $this->db->exec($sql);

        return $ret;
    }

    /**
     * @return bool
     */
    public function save() {
        $ret = false;
        $estado = (string) $this->getEstado();

        if($estado == estado_reserva::INCOMPLETA &&
           $this->getStep() == '3'
        ) {
            $this->setEstado(estado_reserva::get(estado_reserva::SINSENA));
        }

        if($this->test()) {
            $this->clean_cache();
            if($this->exists()) {
                $this->update();
            } else {
                $this->insert();
                $this->setId(intval($this->db->lastval()));
            };
            $this->saveHabitaciones();
            $this->savePasajeros();
        }

        if(!$this->get_errors()) {
            $ret = true;
        }

        return $ret;
    }

    /**
     * @return bool
     */
    public function delete() {
        $this->clean_cache();

        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE id = " . (int) $this->id . ";");
    }

    /**
     * @return void
     */
    private function clean_cache() {
        $this->cache->delete(str_replace('{id}',$this->getId(),self::CACHE_KEY_SINGLE));
        $this->cache->delete(self::CACHE_KEY_ALL);
    }

    /**
     * @param $codcliente
     *
     * @return bool|cliente
     */
    private function get_cliente($codcliente) {
        $cliente = new cliente();

        return $cliente->get($codcliente);
    }

    /**
     * @param int $idreserva
     *
     * @return array
     */
    private function get_habitaciones($idreserva) {
        return habitacion_por_reserva::getByReserva($idreserva);
    }

    /**
     * @param $idreserva
     *
     * @return array
     */
    private function get_pasajeros($idreserva) {
        return pasajero_por_reserva::getByReserva($idreserva);
    }

    /**
     * @param $idtarifa
     *
     * @return bool|tarifa_reserva
     */
    private function get_tarifa($idtarifa) {
        if($idtarifa) {
            return tarifa_reserva::get($idtarifa);
        }
    }

    /**
     * @param $idestado
     *
     * @return bool|estado_reserva
     */
    private function get_estado($idestado) {
        $estado = estado_reserva::get($idestado);
        if($estado->getId() != $idestado) {
            $this->idestado = $estado->getId();
        }

        return $estado;
    }

    /**
     * @param $codgrupo
     *
     * @return bool|grupo_clientes
     */
    private function get_grupo_clientes($codgrupo) {
        $grupo_cliente = new grupo_clientes();

        return $grupo_cliente->get($codgrupo);
    }

    /**
     * @param $idfactura
     *
     * @return bool|factura_cliente
     */
    private function get_factura($idfactura) {
        $factura_cliente = new factura_cliente();

        return $factura_cliente->get($idfactura);
    }

    public function calcularTotales($recalc = false) {
        if(!$this->totales || $recalc) {
            $cantAdultos = $this->getCantidadAdultos();
            $cantMenores = $this->getCantidadMenores();
            $cantPasajeros = (int) ($cantAdultos + $cantMenores);
            $pasajeros = $this->getPasajeros();
            $total = 0.0;
            if($pasajeros) {
                foreach($pasajeros as $i => $pasajero) {
                    $total += $pasajero->getTotal();
                }
            }

            if($cantPasajeros == 1) {
                $total += $total*0.6;
            }

            $descuento = (is_numeric($this->descuento) && $this->descuento > 0) ? ($total * ($this->descuento/100)) : 0;
            $this->totales = array(
                'monto' => $this->getTarifa()->getMonto(),
                'total' => $total,
                'montoDescuento' => $descuento,
                'descuento' => $this->descuento,
                'final' => ($total - $descuento)
            );
        }
    }

    public function getTotal() {
        if(!$this->totales) {
            $this->calcularTotales();
        }
        return $this->totales['total'];
    }

    public function getTotalFinal() {
        if(!$this->totales) {
            $this->calcularTotales();
        }
        return $this->totales['final'];
    }

    public function getMontoDescuento() {
        if(!$this->totales) {
            $this->calcularTotales();
        }
        return $this->totales['montoDescuento'];
    }

    private function saveHabitaciones() {
        if($this->habitaciones) {
            /** @var habitacion_por_reserva $habitacion */
            foreach($this->habitaciones as $habitacion) {
                $habitacion->setEdit(true);
                if(!$habitacion->save()) {
                    $this->new_error_msg("Error al agregar la habitacion ". $habitacion->getHabitacion()->getNumero());
                }
            }
        }
        $this->removeHabitaciones();
    }

    private function removeHabitaciones() {
        if($this->remover_habitaciones) {
            foreach($this->remover_habitaciones as $habitacion) {
                $objHab = $this->__parseHabitacion($habitacion);
                if(!$objHab->delete()) {
                    $this->new_error_msg("Error al remover la habitacion ". $objHab->getHabitacion()->getNumero());
                }
            }
        }
    }

    private function savePasajeros() {
        if($this->pasajeros) {
            foreach($this->pasajeros as $pasajero) {
                if($pasajero->getNombreCompleto() && !$pasajero->save()) {
                    $this->new_error_msg("Error al guardar el pasajero ". $pasajero->getNombreCompleto());
                }
            }
        }
        $this->removePasajeros();
    }

    private function removePasajeros() {
        if($this->remover_pasajeros) {
            foreach($this->remover_pasajeros as $pasajero) {
                $objPasa = $this->__parsePasajero($pasajero);
                if(!$objPasa->delete()) {
                    $this->new_error_msg("Error al remove el pasajero ". $objPasa->getNombreCompleto());
                }
            }
        }
    }

    public function toArray() {}

    private function getCheckInPasajeros() {
        $pasajeros = new pasajero_por_reserva();

        return $pasajeros->fecthCheckInCountByReserva($this->id);
    }

}