<?php

require_once 'base/fs_model.php';

require_model( 'cliente.php' );
require_model( 'habitacion.php' );
require_model( 'tarifa_reserva.php' );
require_model( 'estado_reserva.php' );
require_model( 'grupo_clientes.php' );
require_model( 'habitacion_por_reserva.php' );
require_model( 'pasajero_por_reserva.php' );
require_model( 'pago.php' );


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
    protected $idpago;

    /**
     * @var pago
     */
    public $pago;

    /**
     * @var string
     */
    protected $comentario;

    /**
     * @var array
     */
    public $totales;

    /**
     * @var string[]
     */
    private $remover_pasajeros;

    const DATE_FORMAT = 'Y-m-d';

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
    public function setId( $data ) {
        // This is an ugly thing use an Hydrator insted
        if (is_int($data)) {
            $this->id = $data;
        }

        if (is_array($data)) {
            if (isset($data['idreserva'])) {
                $this->id = $data['idreserva'];
            }

            if (isset($data['id'])) {
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
    public function setCodCliente( $codcliente ) {
        $this->codcliente = $codcliente;

        return $this;
    }

    /**
     * @return cliente
     */
    public function getCliente() {
        return $this->cliente;
    }

    /**
     * @param cliente $cliente
     *
     * @return reserva
     */
    public function setCliente( cliente $cliente ) {
        $this->cliente = $cliente;
        $this->codcliente = $cliente->codcliente;

        return $this;
    }

    /**
     * @param $stringify
     *
     * @return string[]|habitacion_por_reserva[]
     */
    public function getHabitaciones($stringify = false) {
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
    public function setHabitaciones( $habitaciones = array() ) {
        $this->habitaciones = array();
        foreach ( $habitaciones as $habitacion ) {
            $datos_habitacion = explode(':',$habitacion);
            //Si no está el idreserva
            if(!isset($datos_habitacion[1])) {
                $datos_habitacion[1] = $this->getId();
            }
            //Si no está el id
            if(!isset($datos_habitacion[2])) {
                $datos_habitacion[2] = null;
            }
            $this->habitaciones[] = new habitacion_por_reserva( array(
                'idhabitacion' => $datos_habitacion[0],
                'idreserva' => $datos_habitacion[1],
                'id' => $datos_habitacion[2]
            ) );
        }
        return $this;
    }

    /**
     * @param $stringify
     *
     * @return string[]|pasajero_por_reserva[]
     */
    public function getPasajeros($stringify = false) {
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
     * @param array $pasajeros
     *
     * @return reserva
     */
    public function setPasajeros($pasajeros = array() ) {
        $this->pasajeros = array();
        foreach ($pasajeros as $pasajero) {
            $this->pasajeros[] = $this->__parsePasajero($pasajero);
        }
        return $this;
    }

    /**
     * @param $string
     *
     * @return pasajero_por_reserva
     */
    private function __parsePasajero($string) {
        $datos_pasajero = explode(':',$string);
        //Si no está el idreserva
        if(!isset($datos_pasajero[4])) {
            $datos_pasajero[4] = $this->getId();
        }
        //Si no está el idpasajeroporreserva
        if(!isset($datos_pasajero[5])) {
            $datos_pasajero[5] = 0;
        }
        return new pasajero_por_reserva( array(
            'nombre_completo' => $datos_pasajero[0],
            'tipo_documento' => $datos_pasajero[1],
            'documento' => $datos_pasajero[2],
            'fecha_nacimiento' => $datos_pasajero[3],
            'idreserva' => $datos_pasajero[4],
            'id' => $datos_pasajero[5]
        ) );

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
    public function setIdTarifa( $idtarifa ) {
        $this->idtarifa = $idtarifa;

        return $this;
    }

    /**
     * @return tarifa_reserva
     */
    public function getTarifa() {
        return $this->tarifa;
    }

    /**
     * @param tarifa_reserva $tarifa
     *
     * @return tarifa_reserva
     */
    public function setTarifa( tarifa_reserva $tarifa ) {
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
    public function setIdEstado( $idestado ) {
        $this->idestado = $idestado;

        return $this;
    }

    /**
     * @return estado_reserva
     */
    public function getEstado() {
        return $this->estado;
    }

    /**
     * @param estado_reserva $estado
     *
     * @return reserva
     */
    public function setEstado( estado_reserva $estado ) {
        $this->estado = $estado;
        $this->idestado = $estado->getId();

        return $this;
    }

    /**
     * @return string
     */
    public function getCodGrupoCliente() {
        return $this->codgrupo;
    }

    /**
     * @param string $codgrupo
     *
     * @return reserva
     */
    public function setCodGrupoCliente( $codgrupo ) {
        $this->codgrupo = $codgrupo;

        return $this;
    }

    /**
     * @return grupo_clientes
     */
    public function getGrupoCliente() {
        return $this->grupo_clientes;
    }

    /**
     * @param grupo_clientes $grupo_clientes
     *
     * @return reserva
     */
    public function setGrupoCliente( grupo_clientes $grupo_clientes ) {
        $this->grupo_clientes = $grupo_clientes;
        $this->codgrupo = $grupo_clientes->codgrupo;

        return $this;
    }

    /**
     * @return string
     */
    public function getFechaIn() {
        return $this->fecha_in;
    }

    /**
     * @param string $fecha_in
     *
     * @return reserva
     */
    public function setFechaIn( $fecha_in ) {
        $date = new DateTime($fecha_in);
        $this->fecha_in = $date->format(self::DATE_FORMAT);

        return $this;
    }

    /**
     * @return string
     */
    public function getFechaOut() {
        return $this->fecha_out;
    }

    /**
     * @param string $fecha_out
     *
     * @return reserva
     */
    public function setFechaOut( $fecha_out ) {
        $date = new DateTime($fecha_out);
        $this->fecha_out = $date->format(self::DATE_FORMAT);

        return $this;
    }

    /**
     * @return int
     */
    public function getCantidadAdultos() {
        if ( $this->cantidad_adultos === null && is_array( $this->pasajeros ) ) {
            $result = 0;
            /* @var pasajero_por_reserva $huesped */
            foreach ( $this->pasajeros as $huesped ) {
                if ( $huesped->esAdulto() ) {
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
    public function setCantidadAdultos( $cantidad_adultos = 0 ) {
        $this->cantidad_adultos = $cantidad_adultos;

        return $this;
    }

    /**
     * @return int
     */
    public function getCantidadMenores() {
        if ( $this->cantidad_menores === null && is_array( $this->pasajeros ) ) {
            $result = 0;
            /* @var pasajero_por_reserva $huesped */
            foreach ( $this->pasajeros as $huesped ) {
                if ( $huesped->esMenor() ) {
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
    public function setCantidadMenores( $cantidadMenores = 0 ) {
        $this->cantidad_menores = $cantidadMenores;

        return $this;
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
     * @return int
     */
    public function getCategoriaHabitacion() {
        if($this->habitaciones) {
            //Obtengo una de las habitaciones
            $hab = $this->habitaciones[0];
            return $hab->getHabitacion()->getIdCategoria();
        }
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
    public function getIdPago() {
        return $this->idpago;
    }

    /**
     * @param int $idpago
     *
     * @return reserva
     */
    public function setIdPago( $idpago ) {
        $this->idpago = $idpago;

        return $this;
    }

    /**
     * @return pago
     */
    public function getPago() {
        return $this->pago;
    }

    /**
     * @param pago $pago
     *
     * @return reserva
     */
    public function setPago( pago $pago ) {
        $this->pago = $pago;

        return $this;
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
    public function setComentario( $comentario = '' ) {
        $this->comentario = $comentario;

        return $this;
    }

    public function __construct( $data = array() ) {
        parent::__construct( 'reserva', 'plugins/reservas/' );

        $this->setValues( $data );
    }

    public function setValues( $data = array() ) {
        $this->setId( $data );
        //Datos básicos de la reserva
        //Obtener informacion relacionada al cliente
        $this->codcliente = ( isset( $data['codcliente'] ) ) ? $data['codcliente'] : null;
        if ( $this->codcliente ) {
            $this->cliente = $this->get_cliente( $this->codcliente );
            $this->codgrupo = $this->cliente->codgrupo;
            $this->grupo_clientes = $this->get_grupo_clientes( $this->codgrupo );
        } else {
            $this->cliente = new cliente();
            $this->grupo_clientes = new grupo_clientes();
        }
        //Fecha ingreso
        if(isset( $data['fecha_in'] ) ) {
            $this->setFechaIn($data['fecha_in']);
        }
        //Fecha egreso
        if(isset( $data['fecha_out'] )) {
            $this->setFechaOut( $data['fecha_out'] );
        }

        $this->cantidad_adultos = ( isset( $data['cantidad_adultos'] ) ) ? $data['cantidad_adultos'] : null;
        $this->cantidad_menores = ( isset( $data['cantidad_menores'] ) ) ? $data['cantidad_menores'] : null;
        $this->descuento = ( isset( $data['descuento'] ) ) ? $data['descuento'] : 0;

        $this->remover_pasajeros = ( isset( $data['remover_pasajeros'] ) ) ? $data['remover_pasajeros'] : array();
        //Habitaciones
        if ( isset( $data['idsHabitaciones'] ) && $data['idsHabitaciones']) {
            $this->setHabitaciones( explode( ',', $data['idsHabitaciones'] ) );
        } else {
            $this->habitaciones = $this->get_habitaciones( $this->getId() );
        }

        //Tarifa
        $this->idtarifa = ( isset( $data['idtarifa'] ) ) ? $data['idtarifa'] : null;
        //Media pension
        $this->media_pension = ( isset( $data['media_pension'] ) && $data['media_pension'] ) ? true : false;
        if ( $this->idtarifa ) {
            $this->setTarifa($this->get_tarifa( $this->idtarifa ));
        } elseif($this->cliente->codcliente) {
            $this->tarifa = new tarifa_reserva();
            //Si el tipo de convenio es Invitado pero tiene media pensión
            // usar tarifa de afiliado
            if($this->getGrupoCliente()->nombre == 'Invitado' &&
               $this->media_pension) {
                // OJO CON ESTO ES UNA NEGRADA PERO NO HAY OTRA
                // LA CATEGORÍA ACTIVO SIMEPRE DEBE SER LA 1
                $this->setTarifa($this->tarifa->fetchByCategoriaYTipoPasajero(
                    $this->getCategoriaHabitacion(),
                    $this->grupo_clientes->get('1')
                ));
            } else {
                if(isset( $data['idcategoria'])) {
                    $categoria_habitacion =$data['idcategoria'];
                } else {
                    $categoria_habitacion = $this->getCategoriaHabitacion();
                }
                $this->setTarifa($this->tarifa->fetchByCategoriaYTipoPasajero(
                    $categoria_habitacion,
                    $this->getCodGrupoCliente()
                ));
            }
        }
/*        //Si la reserva está incompleta la marcamos como sin seña
        if($this->reserva->getEstado()->getDescripcion() == estado_reserva::INCOMPLETA) {
            $this->reserva->setEstado(estado_reserva::get(estado_reserva::SINSENA));
        }*/

        $this->idestado = ( isset( $data['idestado'] ) ) ? (int) $data['idestado'] : null;
        $this->estado = $this->get_estado( $this->idestado );
        $this->idpago = ( isset( $data['idpago'] ) ) ? $data['idpago'] : null;
        if ( $this->idpago ) {
            $this->pago = $this->get_pago( $this->idpago );
        }
        if(isset($data['pasajeros'])) {
            $this->setPasajeros($data['pasajeros']);
        } else {
            $this->pasajeros = $this->get_pasajeros($this->getId());
        }
        $this->comentario = ( isset( $data['comentario'] ) ) ? $data['comentario'] : null;
    }

    public function getStep() {
        if(!$this->getFechaOut()) {
            return '1';
        }

        if(!$this->habitaciones) {
            return '2';
        }

        if($this->habitaciones && $this->cliente) {
            return '3';
        }

    }

    public function getSuccesMessage() {
        if($this->getStep() > 2) {
            return "Reserva actualizada correctamente!";
        } else {
            return "Reserva agregada correctamente!";
        }
    }

    protected function install() {
        $cliente = new cliente();
        $habitacion = new habitacion();
        $tarifa = new tarifa_reserva();
        $estado = new estado_reserva();
        $habporres = new habitacion_por_reserva();
        $pago = new pago();

        return '';
    }

    /**
     * @param $id
     *
     * @return bool|reserva
     */
    public static function get( $id ) {
        $reserva = new self();

        return $reserva->fetch( $id );
    }

    /**
     * @param $id
     *
     * @return bool|reserva
     */
    public function fetch( $id ) {
        $reserva = $this->cache->get('reserva_reserva_'.$id);
        if($id && !$reserva) {
            $reserva = $this->db->select( "SELECT * FROM " . $this->table_name . " WHERE id = " . (int) $id . ";" );
            $this->cache->set('reserva_reserva_'.$id, $reserva);
        }
        if ( $reserva ) {
            return new reserva( $reserva[0] );
        } else {
            return false;
        }
    }

    /**
     * @return reserva[]
     */
    public function fetchAll() {
        $reservalist = array();
        $reservas = $this->cache->get('m_reserva_all');
        if (!$reservas) {
            $reservas = $this->db->select( "SELECT * FROM " . $this->table_name . " ORDER BY fecha_in ASC;" );
            $this->cache->set( 'm_reserva_all', $reservas);
        }
        foreach ( $reservas as $reserva ) {
            $reservalist[] = new reserva( $reserva );
        }

        return $reservalist;
    }

    /**
     * @param $idhabitacion
     * @param $fecha
     *
     * @return reserva[]
     */
    public function findByHabitacionYFecha($idhabitacion, $fecha, $limit = 1) {
        $sql = "SELECT
$this->table_name.*
FROM " . $this->table_name . "
    JOIN habitacion_por_reserva ON (reserva.id = habitacion_por_reserva.idreserva)
WHERE idhabitacion = $idhabitacion AND (fecha_in <= '$fecha' AND fecha_out >= '$fecha')
LIMIT $limit";
        $reservalist = array();
        $reservas = $this->db->select($sql);
        if ( $reservas ) {
            foreach ( $reservas as $reserva ) {
                $reservalist[] = new reserva( $reserva );
            }
        }
        return $reservalist;
    }

    /**
     * @return bool|array
     */
    public function exists() {
        if ( is_null( $this->id ) ) {
            return false;
        } else {
            return $this->db->select( "SELECT * FROM " . $this->table_name . " WHERE id = " . (int) $this->id . ";" );
        }
    }

    /**
     * @return bool
     */
    public function test() {
        $status = false;
        $this->id = (int) $this->id;

        if ( $this->codgrupo == 0 ) {
            $this->new_error_msg( "Grupo de Cliente no válido." );
        }

        if($this->getCantidadDias() < 1) {
            $this->new_error_msg( "Fecha de reserva no válida." );
        }

        if (!$this->get_errors()) {
            $status = true;
        }


        return $status;
    }

    protected function insert() {
        $sql = 'INSERT ' . $this->table_name .
               ' SET ' .
               'codcliente = ' . $this->var2str( $this->getCodCliente() ) . ',' .
                'idtarifa = ' . $this->intval( $this->getIdTarifa() ) . ',' .
                'idestado = ' . $this->intval( $this->getEstado()->getId() ) . ',' .
                'fecha_in = ' . $this->var2str( $this->getFechaIn() ) . ',' .
                'fecha_out = ' . $this->var2str( $this->getFechaOut() ) . ',' .
                'cantidad_adultos = ' . $this->intval( $this->getCantidadAdultos() ) . ',' .
                'cantidad_menores = ' . $this->intval( $this->getCantidadMenores() ) . ',' .
                'media_pension = ' . $this->intval( $this->getMediaPension() ) . ',' .
                'descuento = ' . floatval( $this->getDescuento() ) . ',';
        if ( (int) $this->getIdPago() != 0 ) {
            $sql .= 'idpago = ' . $this->intval( $this->getIdPago() ) . ',';
        }
        $sql .= 'comentario = ' . $this->var2str( $this->getComentario() ) .
                ';';

        $ret = $this->db->exec( $sql );
        return $ret;
    }

    protected function update() {
        $sql = 'UPDATE ' . $this->table_name .
               ' SET ' .
               'codcliente = ' . $this->var2str( $this->getCodCliente() ) . ',' .
               'idtarifa = ' . $this->intval( $this->getIdTarifa() ) . ',' .
               'idestado = ' . $this->intval( $this->getEstado()->getId() ) . ',' .
               'fecha_in = ' . $this->var2str( $this->getFechaIn() ) . ',' .
               'fecha_out = ' . $this->var2str( $this->getFechaOut() ) . ',' .
               'cantidad_adultos = ' . $this->intval( $this->getCantidadAdultos() ) . ',' .
               'cantidad_menores = ' . $this->intval( $this->getCantidadMenores() ) . ',' .
               'media_pension = ' . $this->intval( $this->getMediaPension() ) . ',' .
               'descuento = ' . floatval( $this->getDescuento() ) . ',';
        if ( (int) $this->getIdPago() != 0 ) {
            $sql .= 'idpago = ' . $this->intval( $this->getIdPago() ) . ',';
        }
        $sql .= 'comentario = ' . $this->var2str( $this->getComentario() ) .
                ' WHERE id = ' . $this->getId() . ';';

        $ret = $this->db->exec( $sql );
        return $ret;
    }

    /**
     * @return bool
     */
    public function save() {
        $ret = false;
        if ( $this->test() ) {
            $this->clean_cache();
            if ( $this->exists() ) {
                $ret = $this->update();
                $this->saveHabitaciones();
                $this->savePasajeros();
            } else {
                $ret = $this->insert();
                $this->setId( intval( $this->db->lastval() ) );
                $this->saveHabitaciones();
                $this->savePasajeros();

            };
        }

        return $ret;
    }

    /**
     * @return bool
     */
    public function delete() {
        $this->clean_cache();

        return $this->db->exec( "DELETE FROM " . $this->table_name . " WHERE id = " . (int) $this->id . ";" );
    }

    /**
     * @return void
     */
    private function clean_cache() {
        $this->cache->delete( 'm_reserva_all' );
    }

    /**
     * @param $query
     * @param int $offset
     *
     * @return array
     */
    public function search( $query, $offset = 0 ) {
        $reservalist = array();
        $query = strtolower( $this->no_html( $query ) );

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if ( is_numeric( $query ) ) {
            $consulta .= "id LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace( ' ', '%', $query );
            $consulta .= "lower(nomdbre) LIKE '%" . $buscar . "%'";
        }
        $consulta .= " ORDER BY nombre ASC";

        $reservas = $this->db->select_limit( $consulta, FS_ITEM_LIMIT, $offset );
        if ( $reservas ) {
            foreach ( $reservas as $reserva ) {
                $reservalist[] = new reserva( $reserva );
            }
        }

        return $reservalist;
    }

    /**
     * @param $codcliente
     *
     * @return bool|cliente
     */
    private function get_cliente( $codcliente ) {
        $cliente = new cliente();

        return $cliente->get( $codcliente );
    }

    /**
     * @param int $idreserva
     *
     * @return array
     */
    private function get_habitaciones( $idreserva ) {
        return habitacion_por_reserva::getByReserva( $idreserva );
    }

    /**
     * @param $idreserva
     *
     * @return array
     */
    private function get_pasajeros($idreserva) {
        return pasajero_por_reserva::getByReserva( $idreserva );
    }

    /**
     * @param $idtarifa
     *
     * @return bool|tarifa_reserva
     */
    private function get_tarifa( $idtarifa ) {
        if($idtarifa) {
            return tarifa_reserva::get( $idtarifa );
        }
    }

    /**
     * @param $idestado
     *
     * @return bool|estado_reserva
     */
    private function get_estado( $idestado ) {
        $estado = estado_reserva::get( $idestado );
        if ( $estado->getId() != $idestado ) {
            $this->idestado = $estado->getId();
        }

        return $estado;
    }

    private function get_grupo_clientes( $codgrupo ) {
        $grupo_cliente = new grupo_clientes();

        return $grupo_cliente->get( $codgrupo );
    }

    public function getCheckIn() {
        return null;
    }

    /**
     * @param $idpago
     *
     * @return null
     */
    private function get_pago( $idpago ) {
        return null;
    }

    public function calcularTotales() {
        if($this->getTarifa()) {
            $monto = $this->getTarifa()->getMonto();
            $cantAdultos = $this->getCantidadAdultos();
            $cantMenores = $this->getCantidadMenores();
            $cantDias = $this->getCantidadDias();
            $cantPasajeros = (int) ($cantAdultos+$cantMenores);

            //Regla de negocio:
            //Si la reserva es para una sola persona tarifa+60%
            if($cantPasajeros === 1) {
                $monto += $monto*0.6;
            }

            $totalPorDia = $monto*$cantAdultos;

            // Si hay menores multiplica la cantidad de menores
            // por el 60% del valor de la tarifa
            if($cantMenores > 0) {
                $totalPorDia += ($cantMenores*$monto*0.6);
            }

            $total = $totalPorDia*$cantDias;
            $descuento = (is_numeric($this->descuento) &&  $this->descuento > 0) ? $total*(1/$this->descuento) : 0;
            $this->totales = array(
                'monto' => $monto,
                'pordia' => $totalPorDia,
                'total' => $total,
                'decuento' => $this->descuento,
                'final' => ($total - $descuento)
            );
        }
    }

    public function getTotal() {
        return $this->totales['total'];
    }

    public function getTotalFinal() {
        return $this->totales['final'];
    }

    private function getCantidadDias() {
        $date1 = new DateTime($this->getFechaIn());
        $date2 = new DateTime($this->getFechaOut());

        return $date2->diff($date1)->format("%a");
    }

    private function saveHabitaciones() {
        if($this->habitaciones) {
            foreach($this->habitaciones as $habitaciones) {
                $habitaciones->save();
            }
        }
    }

    private function savePasajeros() {
        if($this->pasajeros) {
            foreach($this->pasajeros as $pasajero) {
                $pasajero->save();
            }
        }
        if($this->remover_pasajeros) {
            foreach($this->remover_pasajeros as $pasajero) {
                $objPasa = $this->__parsePasajero($pasajero);
                $objPasa->delete();
            }
        }
    }

}