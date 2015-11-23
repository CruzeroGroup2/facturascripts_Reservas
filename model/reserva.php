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

        $this->setPasajeros($data);

        $this->descuento = (isset($data['descuento'])) ? $data['descuento'] : 0;

        //Habitaciones
        if(isset($data['idsHabitaciones']) && $data['idsHabitaciones']) {
            $this->setHabitaciones(explode(',', $data['idsHabitaciones']));
        }
        $this->remover_habitaciones = (isset($data['remover_habitaciones'])) ? $data['remover_habitaciones'] : array();

        //Tarifa
        $this->idtarifa = (isset($data['idtarifa'])) ? $data['idtarifa'] : null;
        //Media pension
        $this->media_pension = (isset($data['media_pension']) && $data['media_pension']) ? true : false;

        if(isset($data['idcategoria'])) {
            $this->idcategoriahabitacion = $data['idcategoria'];
            $this->setTarifa($tarifa->fetchByCategoriaYTipoPasajero(
                $this->getCategoriaHabitacion(),
                $this->getCodGrupoCliente()
            ));
        }

        if($this->getGrupoCliente() && $this->getGrupoCliente()->nombre == 'Invitado' && $this->media_pension ) {
            // OJO CON ESTO ES UNA NEGRADA PERO NO HAY OTRA FS NO ME DEJA BUSCAR GRUPO CLIENTES POR NOMBRE POR LO QUE
            // LA CATEGORÍA ACTIVO SIMEPRE DEBE SER LA 1
            $this->setTarifa($tarifa->fetchByCategoriaYTipoPasajero(
                $this->getCategoriaHabitacion(),
                $this->grupo_clientes->get('1')->codgrupo
            ));
        }

        //Obtener el estado de la reserva
        $this->idestado = (isset($data['idestado'])) ? (int) $data['idestado'] : null;

        //Factura de la reserva
        $this->idfactura = (isset($data['idfactura'])) ? $data['idfactura'] : null;

        //
        $this->comentario = (isset($data['comentario'])) ? $data['comentario'] : null;
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
        if(!$this->habitaciones) {
            $this->getHabitaciones();
        }

        if($this->habitaciones) {
            //Obtengo una de las habitaciones
            $hab = $this->habitaciones[0];
            $this->idcategoriahabitacion = $hab->getHabitacion()->getIdCategoria();
        } else {
            //trigger_error("No hay habitaciones para la reserva $this->id", E_USER_WARNING);
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
            $this->calcularTotales();
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
        $this->calcularTotales();

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
        $cantidad = $this->cantidad_adultos + $this->cantidad_menores;
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
            $this->pasajeros = $this->get_pasajeros($this->getId());
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

        //If the information is in the request use that
        if(isset($data['pasajeros'])) {
            $this->pasajeros = array();
            foreach($data['pasajeros'] as $pasajero) {
                if(is_a($pasajero, 'pasajero_por_reserva')) {
                    $this->pasajeros[] = $pasajero;
                } else {
                    $this->pasajeros[] = $this->__parsePasajero($pasajero);
                }
            }
        }

        //If not, then obtain it from the DB
        if(!$this->pasajeros) {
            $this->getPasajeros();
        }


        $this->cantidad_adultos = (isset($data['cantidad_adultos'])) ? (int) $data['cantidad_adultos'] : null;
        if($this->cantidad_adultos) {
            foreach(range(0, ($this->cantidad_adultos-1)) as $i) {
                if(!isset($this->pasajeros[$i])) {
                    $tmpPasajero = new pasajero_por_reserva();
                    $tmpPasajero->asAdult();
                    $tmpPasajero->setCodGrupo($this->getCodGrupoCliente());
                    $this->pasajeros[] = clone $tmpPasajero;
                    unset($tmpPasajero);
                }
            }
        }

        $this->cantidad_menores = (isset($data['cantidad_menores'])) ? (int) $data['cantidad_menores'] : null;
        if($this->cantidad_menores) {
            foreach(range(0, ($this->cantidad_menores-1)) as $i) {
                if(!isset($this->pasajeros[$this->cantidad_adultos+$i])) {
                    $tmpPasajero = new pasajero_por_reserva();
                    $tmpPasajero->asMenor();
                    $tmpPasajero->setCodGrupo($this->getCodGrupoCliente());
                    $this->pasajeros[] = clone $tmpPasajero;
                    unset($tmpPasajero);
                }
            }
        }

        $this->remover_pasajeros = (isset($data['remover_pasajeros'])) ? $data['remover_pasajeros'] : array();

        return $this;
    }

    /**
     * @param $string
     *
     * @return pasajero_por_reserva
     */
    private function __parsePasajero($string) {
        $datos_pasajero = explode(':', $string);

        if(isset($datos_pasajero[3]) && in_array($datos_pasajero[3], array('menor_2', 'menor_6', 'adulto'))) {
            switch(strtolower($datos_pasajero[3])) {
                case 'menor_2':
                    $datos_pasajero[3] = "@" . strtotime('-' . (pasajero_por_reserva::EDAD_MIN_MENOR-1) . ' years');
                    break;
                case 'menor_6':
                    $datos_pasajero[3] = "@" . strtotime('-' . (pasajero_por_reserva::EDAD_MAX_MENOR-1) . ' years');
                    break;
                case 'adulto':
                    $datos_pasajero[3] = "@" . strtotime('-18 years');
                    break;
            }
        }

        //Si no está el idreserva
        if(!isset($datos_pasajero[5])) {
            $datos_pasajero[5] = $this->getId();
        }
        //Si no está el idpasajeroporreserva
        if(!isset($datos_pasajero[6])) {
            $datos_pasajero[6] = 0;
        }

        //Si no está el codcliente
        if(!isset($datos_pasajero[7])) {
            $datos_pasajero[7] = 0;
        }

        $pasj = new pasajero_por_reserva(array(
            'nombre_completo' => $datos_pasajero[0],
            'tipo_documento' => $datos_pasajero[1],
            'documento' => $datos_pasajero[2],
            'fecha_nacimiento' => $datos_pasajero[3],
            'codgrupo' => $datos_pasajero[4],
            'idreserva' => $datos_pasajero[5],
            'id' => $datos_pasajero[6],
            'codcliente' => $datos_pasajero[7]
        ));

        return $pasj;

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

        return (isset($this->pasajeros[ $index ])) ? $this->pasajeros[ $index ] : $tmp;
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
        if(!$this->pagos && $this->idfactura) {
            $pago = new recibo_cliente();
            $this->pagos = $pago->all_from_factura($this->idfactura);
        }
        return $this->pagos;
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
        if(($estado == estado_reserva::PAGO || $estado == estado_reserva::SENADO) ||
           ($estado == estado_reserva::CHECKIN  && $this->getCantPasajeros() != $this->getCheckInPasajeros())
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
    JOIN habitacion_por_reserva ON (reserva.id = habitacion_por_reserva.idreserva)
    JOIN estado_reserva ON (reserva.idestado = estado_reserva.id)
WHERE ";
        if(isset($filter['codcliente'])) {
            $sql .= "\n" . ' codcliente = ' . $this->var2str($filter['codcliente']);
        }

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
    public function findByHabitacionYFecha($idhabitacion, $fecha, $limit = 1) {
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
LIMIT $limit";
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

        if(!$this->get_errors()) {
            $status = true;
        }


        return $status;
    }

    protected function insert() {
        $sql = 'INSERT ' . $this->table_name .
               ' SET ' .
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
                $this->saveHabitaciones();
                $this->savePasajeros();
            } else {
                $this->insert();
                $this->setId(intval($this->db->lastval()));
                $this->saveHabitaciones();
                $this->savePasajeros();
            };
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

    public function calcularTotales() {
        $tarifa = new tarifa_reserva();
        $cantAdultos = $this->getCantidadAdultos();
        $cantMenores = $this->getCantidadMenores();
        $cantPasajeros = (int) ($cantAdultos + $cantMenores);
        $cantDias = $this->getCantidadDias();
        $pasajeros = $this->getPasajeros();
        $total = 0.0;
        if($pasajeros) {
            foreach($pasajeros as $i => $pasajero) {
                $monto = $tarifa->fetchByCategoriaYTipoPasajero(
                    $this->getCategoriaHabitacion(),
                    $pasajero->getCodGrupo())->getMonto();
                //Regla de negocio:
                //Si la reserva es para una sola persona tarifa+60%
                if($cantPasajeros === 1) {
                    $monto += $monto * 0.6;
                }

                // Si es menor 60% del valor de la tarifa
                if($pasajero->esMenor()) {
                    $monto = $monto * 0.6;
                }

                //Agregamos el total del pasajero al monto
                $total += $monto * $cantDias;
            }
        }

        $descuento = (is_numeric($this->descuento) && $this->descuento > 0) ? $total * (1 / $this->descuento) : 0;
        $this->totales = array(
            'monto' => $this->getTarifa()->getMonto(),
            'total' => $total,
            'decuento' => $this->descuento,
            'final' => ($total - $descuento)
        );
    }

    public function getTotal() {
        return $this->totales['total'];
    }

    public function getTotalFinal() {
        return $this->totales['final'];
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
                if($pasajero->getNombreCompleto() && $pasajero->getDocumento() && $pasajero->getCodGrupo() && !$pasajero->save()) {
                    $this->new_error_msg("Error al agregar el pasajero ". $pasajero->getNombreCompleto());
                }
            }
        }
        if($this->remover_pasajeros) {
            foreach($this->remover_pasajeros as $pasajero) {
                $objPasa = $this->__parsePasajero($pasajero);
                if(!$objPasa->delete()) {
                    $this->new_error_msg("Error al remove el pasajero ". $objPasa->getNombreCompleto());
                }
            }
        }
    }

    private function getCheckInPasajeros() {
        $pasajeros = new pasajero_por_reserva();

        return $pasajeros->fecthCheckInCountByReserva($this->id);
    }

    private function addPasajeroAdulto() {

    }

}