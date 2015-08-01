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
     * @var array
     */
    protected $habitaciones;

    /**
     * @var array
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
     * @return array
     */
    public function getHabitaciones($stringify = false) {
        $result = array();
        if($stringify) {
            foreach($this->habitaciones as $habitacion) {
                $result[] = $habitacion->getHabitacion()->getId();
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
            $this->habitaciones[] = new habitacion_por_reserva( array(
                'idhabitacion' => $habitacion,
                'idreserva' => $this->getId()
            ) );
        }
        return $this;
    }

    /**
     * @param $stringify
     *
     * @return array
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
            $datos_pasajero = explode(':',$pasajero);
            //Si no está el idreserva
            if(!isset($datos_pasajero[4])) {
                $datos_pasajero[4] = $this->getId();
            }
            //Si no está el idpasajeroporreserva
            if(!isset($datos_pasajero[5])) {
                $datos_pasajero[5] = 0;
            }
            $this->pasajeros[] = new pasajero_por_reserva( array(
                'nombre_completo' => $datos_pasajero[0],
                'tipo_documento' => $datos_pasajero[1],
                'documento' => $datos_pasajero[2],
                'fecha_nacimiento' => $datos_pasajero[3],
                'idreserva' => $datos_pasajero[4],
                'id' => $datos_pasajero[5]
            ) );
        }
        return $this;
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
        if ( $this->cantidad_adultos === null && is_array( $this->huespedes ) ) {
            $result = 0;
            /* @var Huesped $huesped */
            foreach ( $this->huespedes as $huesped ) {
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
        if ( $this->cantidad_menores === null && is_array( $this->huespedes ) ) {
            $result = 0;
            /* @var Huesped $huesped */
            foreach ( $this->huespedes as $huesped ) {
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
        $this->codcliente = ( isset( $data['codcliente'] ) ) ? $data['codcliente'] : null;
        if ( $this->codcliente ) {
            $this->cliente = $this->get_cliente( $this->codcliente );
            $this->codgrupo = $this->cliente->codgrupo;
            $this->grupo_clientes = $this->get_grupo_clientes( $this->codgrupo );
        }
        ( isset( $data['fecha_in'] ) ) ? $this->setFechaIn($data['fecha_in']) : null;
        ( isset( $data['fecha_out'] ) ) ? $this->setFechaOut($data['fecha_out']) : null;
        $this->cantidad_adultos = ( isset( $data['cantidad_adultos'] ) ) ? $data['cantidad_adultos'] : null;
        $this->cantidad_menores = ( isset( $data['cantidad_menores'] ) ) ? $data['cantidad_menores'] : null;
        $this->descuento = ( isset( $data['descuento'] ) ) ? $data['descuento'] : 0;
        $this->idtarifa = ( isset( $data['idtarifa'] ) ) ? $data['idtarifa'] : null;
        if ( $this->idtarifa ) {
            $this->tarifa = $this->get_tarifa( $this->idtarifa );
        }

        if ( isset( $data['idsHabitaciones'] ) ) {
            $this->setHabitaciones( explode( ',', $data['idsHabitaciones'] ) );
        } else {
            $this->habitaciones = $this->get_habitaciones( $this->getId() );
        }
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
        $this->calcularTotales();
    }

    protected function install() {
        $cliente = new cliente();
        $habitacion = new habitacion();
        $tarifa = new tarifa_reserva();
        $estado = new estado_reserva();
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
        $reserva = $this->db->select( "SELECT * FROM " . $this->table_name . " WHERE id = " . (int) $id . ";" );
        if ( $reserva ) {
            return new reserva( $reserva[0] );
        } else {
            return false;
        }
    }

    /**
     * @return bool|array
     */
    public function fetchAll() {
        $reservalist = $this->cache->get_array( 'm_reserva_all' );
        if ( ! $reservalist ) {
            $reservas = $this->db->select( "SELECT * FROM " . $this->table_name . " ORDER BY fecha_in ASC;" );
            if ( $reservas ) {
                foreach ( $reservas as $reserva ) {
                    $reservalist[] = new reserva( $reserva );
                }
            }
            $this->cache->set( 'm_reserva_all', $reservalist );
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

        if ( empty( $this->get_errors() ) ) {
            $status = true;
        }


        return $status;
    }

    protected function insert() {
        $sql = 'INSERT ' . $this->table_name .
               ' SET ' .
               'codcliente = ' . $this->var2str( $this->getCodCliente() ) . ',';
        if ( (int) $this->getIdTarifa() != 0 ) {
            $sql .= 'idtarifa = ' . $this->intval( $this->getIdTarifa() ) . ',';
        }
        $sql .= 'idestado = ' . $this->intval( $this->getEstado()->getId() ) . ',' .
                'codgrupo = ' . $this->var2str( $this->getCodGrupoCliente() ) . ',' .
                'fecha_in = ' . $this->var2str( $this->getFechaIn() ) . ',' .
                'fecha_out = ' . $this->var2str( $this->getFechaOut() ) . ',' .
                'cantidad_adultos = ' . $this->intval( $this->getCantidadAdultos() ) . ',' .
                'cantidad_menores = ' . $this->intval( $this->getCantidadMenores() ) . ',' .
                'descuento = ' . floatval( $this->getDescuento() ) . ',';
        if ( (int) $this->getIdPago() != 0 ) {
            $sql .= 'idpago = ' . $this->intval( $this->getIdPago() ) . ',';
        }
        $sql .= 'comentario = ' . $this->var2str( $this->getComentario() ) .
                ';';

        $ret = $this->db->exec( $sql );
        $this->saveHabitaciones();
        $this->savePasajeros();

        return $ret;
    }

    protected function update() {
        $sql = 'UPDATE ' . $this->table_name .
               ' SET ' .
               'codcliente = ' . $this->var2str( $this->getCodCliente() ) . ',';
        if ( (int) $this->getIdTarifa() != 0 ) {
            $sql .= 'idtarifa = ' . $this->intval( $this->getIdTarifa() ) . ',';
        }
        $sql .= 'idestado = ' . $this->intval( $this->getEstado()->getId() ) . ',' .
                'codgrupo = ' . $this->var2str( $this->getCodGrupoCliente() ) . ',' .
                'fecha_in = ' . $this->var2str( $this->getFechaIn() ) . ',' .
                'fecha_out = ' . $this->var2str( $this->getFechaOut() ) . ',' .
                'cantidad_adultos = ' . $this->intval( $this->getCantidadAdultos() ) . ',' .
                'cantidad_menores = ' . $this->intval( $this->getCantidadMenores() ) . ',' .
                'descuento = ' . floatval( $this->getDescuento() ) . ',';
        if ( (int) $this->getIdPago() != 0 ) {
            $sql .= 'idpago = ' . $this->intval( $this->getIdPago() ) . ',';
        }
        $sql .= 'comentario = ' . $this->var2str( $this->getComentario() ) .
                ' WHERE id = ' . $this->getId() . ';';

        $ret = $this->db->exec( $sql );
        $this->saveHabitaciones();
        $this->savePasajeros();
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
            } else {
                $ret = $this->insert();
                $this->setId( intval( $this->db->lastval() ) );
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
     * @param $idtafira
     *
     * @return bool|tarifa_reserva
     */
    private function get_tarifa( $idtafira ) {
        return tarifa_reserva::get( $idtafira );
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
            $cantPasajeros = $this->getCantidadAdultos() + $this->getCantidadMenores();
            $cantDias = $this->getCantidadDias();
            $totalPorDia = $monto*$cantDias;
            $total = $totalPorDia*$cantPasajeros;
            $descuento = (is_numeric($this->descuento) &&  $this->descuento > 0) ? $total*(1/$this->descuento) : 0;
            $this->totales = array(
                'monto' => $monto,
                'pordia' => $totalPorDia,
                'total' => $total,
                'decuento' => $this->descuento,
                'final' => $total - $descuento
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
            /** @var habitacion_por_reserva $habitaciones */
            foreach($this->habitaciones as $habitaciones) {
                $habitaciones->save();
            }
        }
    }

    private function savePasajeros() {
        if($this->pasajeros) {
            /** @var pasajero_por_reserva $pasajero */
            foreach($this->pasajeros as $pasajero) {
                $pasajero->save();
            }
        }
    }

}