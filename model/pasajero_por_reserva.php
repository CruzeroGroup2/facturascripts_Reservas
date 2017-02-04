<?php

require_once 'plugins/reservas/extras/functions/boolval.php';
require_once 'plugins/reservas/extras/functions/is_date.php';

require_once 'base/fs_model.php';
require_model('reserva.php');

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 15/07/2015
 * Time: 09:39 PM
 */
class pasajero_por_reserva extends fs_model {

    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $nombre_completo = null;

    /**
     * @var string
     */
    protected $tipo_documento = null;

    /**
     * @var string
     */
    protected $documento = null;

    /**
     * @var string
     */
    protected $fecha_nacimiento = null;

    /**
     * @var string
     */
    protected $codgrupo = null;

    /**
     * @var string
     */
    protected $codcliente = null;

    /**
     * @var cliente
     */
    protected $cliente = null;

    /**
     * @var int
     */
    protected $idreserva = null;

    /**
     * @var reserva
     */
    protected $reserva = null;

    /**
     * @var int
     */
    protected $idhabitacion = null;

    /**
     * @var string
     */
    protected $fecha_in = null;

    /**
     * @var string
     */
    protected $fecha_out = null;

    /**
     * @var string
     */
    protected $check_in = null;

    /**
     * @var string
     */
    protected $check_out = null;

    /**
     * @var int
     */
    protected $idtarifa = null;

    /**
     * @var tarifa_reserva
     */
    protected $tarifa = null;

    const DATE_FORMAT = 'd-m-Y';
    const DATE_FORMAT_IN_OUT = 'd-m-Y H:i:s';

    const EDAD_MAX_MENOR = 7;
    const EDAD_MIN_MENOR = 3;

    const CACHE_KEY_ALL = 'reserva_pasajero_por_reserva_all';
    const CACHE_KEY_SINGLE = 'reserva_pasajero_por_reserva_{id}';

    /**
     * @param array $data
     */
    function __construct($data = array()) {
        parent::__construct('pasajero_por_reserva','plugins/reservas/');

        $this->setValues($data);
    }

    /**
     * @param array $data
     *
     * @return pasajero_por_reserva
     */
    public function setValues($data = array()) {
        $this->setId($data);
        $this->nombre_completo = (isset($data['nombre_completo'])) ? $data['nombre_completo'] : null;
        $this->tipo_documento = (isset($data['tipo_documento'])) ? $data['tipo_documento'] : null;
        $this->documento = (isset($data['documento'])) ? $data['documento'] : null;
        if(isset($data['fecha_nacimiento'])) {
            $this->setFechaNacimiento($data['fecha_nacimiento']);
        }
        $this->codgrupo = (isset($data['codgrupo'])) ? $data['codgrupo'] : null;
        $this->codcliente = (isset($data['codcliente'])) ? $data['codcliente'] : null;
        $this->idreserva = (isset($data['idreserva'])) ? $data['idreserva'] : null;
        //
        $this->setFechaIn($data);
        $this->setFechaOut($data);
        //Tarifa
        $this->idtarifa = (isset($data['idtarifa'])) ? $data['idtarifa'] : null;

        //Datos correspondientes al checkin
        $this->idhabitacion = (isset($data['idhabitacion'])) ? $data['idhabitacion'] : null;
        $this->check_in = (isset($data['check_in'])) ? $data['check_in'] : null;
        $this->check_out = (isset($data['check_out'])) ? $data['check_out'] : null;

        if($this->nombre_completo &&
           $this->documento &&
           $this->codgrupo &&
           !$this->codcliente) {
            $cliente = new cliente();
            $tmpcli = $cliente->get_by_cifnif($this->documento);
            if(!$tmpcli) {
                $cliente->codcliente = $cliente->get_new_codigo();
                $cliente->nombre = $this->nombre_completo;
                $cliente->razonsocial = $this->nombre_completo;
                $cliente->cifnif = $this->documento;
                $cliente->codgrupo = $this->codgrupo;
                if($cliente->save()) {
                    $this->codcliente = $cliente->codcliente;
                } else {
                    $this->new_error_msg("Error al agregar al pasajero " . $cliente->nombre . " como futuro cliente");
                }
            } else {
                $this->codcliente = $tmpcli->codcliente;
                $this->codgrupo = $tmpcli->codgrupo;
                $this->nombre_completo = $tmpcli->nombre;
            }
        }

        return $this;
    }

    public static function getCountPassagerosCheckInPorRes($idreserva) {
        $obj = new self();
        return $obj->fecthCheckInCountByReserva($idreserva);
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int|array $id
     *
     * @return pasajero_por_reserva
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if (is_int($id)) {
            $this->id = $id;
        }

        if (is_array($id)) {
            if (isset($id['idpasajeroporreserva'])) {
                $this->id = $id['idpasajeroporreserva'];
            }

            if (isset($id['id'])) {
                $this->id = $id['id'];
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getNombreCompleto() {
        return $this->nombre_completo;
    }

    /**
     * @param string $nombre_completo
     *
     * @return pasajero_por_reserva
     */
    public function setNombreCompleto($nombre_completo) {
        $this->nombre_completo = $nombre_completo;

        return $this;
    }

    /**
     * @return string
     */
    public function getTipoDocumento() {
        return $this->tipo_documento;
    }

    /**
     * @param string $tipo_documento
     *
     * @return pasajero_por_reserva
     */
    public function setTipoDocumento($tipo_documento) {
        $this->tipo_documento = $tipo_documento;

        return $this;
    }

    /**
     * @return string
     */
    public function getDocumento() {
        return $this->documento;
    }

    /**
     * @param string $documento
     *
     * @return pasajero_por_reserva
     */
    public function setDocumento($documento) {
        $this->documento = $documento;

        return $this;
    }

    /**
     * @return string
     */
    public function getFechaNacimiento() {
        $fecha = new DateTime($this->fecha_nacimiento);
        return $fecha->format(self::DATE_FORMAT);
    }

    /**
     * @param string $fecha_nacimiento
     *
     * @return pasajero_por_reserva
     */
    public function setFechaNacimiento($fecha_nacimiento) {
        $date = new DateTime($fecha_nacimiento);
        $this->fecha_nacimiento = $date->format(self::DATE_FORMAT);

        return $this;
    }

    /**
     * @return string
     */
    public function getCodCliente() {
        return $this->codcliente;
    }

    /**
     * @param string $codcliente
     *
     * @return pasajero_por_reserva
     */
    public function setCodCliente($codcliente = '') {
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
     * @param string $codgrupo
     *
     * @return habitacion_por_reserva
     */
    public function setCodGrupo($codgrupo = '') {
        $this->codgrupo = $codgrupo;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodGrupo() {
        return $this->codgrupo;
    }

    /**
     * @return bool|grupo_clientes
     */
    public function getTipoPasajero() {
        $grupo = new grupo_clientes();

        return $grupo->get($this->codgrupo);
    }

    /**
     * @return int
     */
    public function getIdReserva() {
        return $this->idreserva;
    }

    /**
     * @param int $idreserva
     *
     * @return habitacion_por_reserva
     */
    public function setIdReserva($idreserva = 0) {
        $this->idreserva = $idreserva;

        return $this;
    }

    /**
     * @return reserva
     */
    public function getReserva() {
        if((!$this->reserva && $this->idreserva) || !is_a($this->reserva, 'reserva')) {
            $this->reserva = reserva::get($this->idreserva);
        }
        return $this->reserva;
    }

    /**
     * @return bool
     */
    public function esAdulto() {
        return $this->getEdad() > self::EDAD_MAX_MENOR;
    }

    /**
     * @return bool
     */
    public function esMenor() {
        $edad = $this->getEdad();
        return (
            $edad >= self::EDAD_MIN_MENOR &&
            $edad < self::EDAD_MAX_MENOR
        );
    }

    /**
     * @return bool
     */
    public function esBebe() {
        return $this->getEdad() < self::EDAD_MIN_MENOR;
    }

    /**
     * @return int
     */
    public function getEdad() {
        return date_diff(date_create($this->getFechaNacimiento()), date_create('today'))->y;
    }

    /**
     * @return string
     */
    public function getEdadCateg() {
        if($this->esAdulto() && !$this->esMenor()) {
            return 'adulto';
        } elseif (!$this->esAdulto() && $this->esMenor()) {
            return 'menor_7';
        } else {
            return 'menor_3';
        }
    }

    /**
     * @return int
     */
    public function getIdHabitacion() {
        return $this->idhabitacion;
    }

    /**
     * @param int $idhabitacion
     *
     * @return pasajero_por_reserva
     */
    public function setIdHabitacion($idhabitacion) {
        $this->idhabitacion = $idhabitacion;

        return $this;
    }

    /**
     * @return string
     */
    public function getFechaIn() {
        $fecha = new DateTime($this->fecha_in);
        return $fecha->format(self::DATE_FORMAT_IN_OUT);
    }

    /**
     * @param string|array $value
     *
     * @return pasajero_por_reserva
     */
    public function setFechaIn($value) {
        if(is_array($value) && isset($value['fecha_in']) && is_date($value['fecha_in'])) {
            $this->fecha_in = $value['fecha_in'];
        } elseif(is_date($value)) {
            $this->fecha_in = $value;
        } elseif($this->idreserva) {
            $this->fecha_in = $this->getReserva()->getFechaIn(true);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getCantidadDias() {
        $date1 = new DateTime(str_replace(array('12:00:00','12:00'), '', $this->getFechaIn()));
        $date2 = new DateTime(str_replace(array('10:00:00','10:00'), '', $this->getFechaOut()));

        $daysDiff = $date2->diff($date1)->format("%a");

        return (int) $daysDiff;
    }

    /**
     * @return string
     */
    public function getFechaOut() {
        $fecha = new DateTime($this->fecha_out);
        return $fecha->format(self::DATE_FORMAT_IN_OUT);
    }

    /**
     * @param string|array $value
     *
     * @return pasajero_por_reserva
     */
    public function setFechaOut($value) {
        if(is_array($value) && isset($value['fecha_out']) && is_date($value['fecha_out'])) {
            $this->fecha_out = $value['fecha_out'];
        } elseif(is_date($value)) {
            $this->fecha_out = $value;
        } elseif($this->idreserva) {
            $this->fecha_out = $this->getReserva()->getFechaOut(true);
        }

        return $this;
    }

    public function asAdult() {
        $ts = strtotime('-18 years');
        $this->setFechaNacimiento("@$ts");
    }

    public function asMenor() {
        $ts = strtotime('-' . (self::EDAD_MIN_MENOR+1) . ' years');
        $this->setFechaNacimiento("@$ts");
    }

    public function asBebe() {
        $ts = strtotime('-1  years');
        $this->setFechaNacimiento("@$ts");
    }

    /**
     * @return boolean
     */
    public function isCheckIn() {
        return $this->check_in;
    }

    /**
     * @param boolean $check_in
     *
     * @return pasajero_por_reserva
     */
    public function setCheckIn($check_in) {
        $this->check_in = $check_in;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCheckOut() {
        return $this->check_out;
    }

    /**
     * @param boolean $check_out
     *
     * @return pasajero_por_reserva
     */
    public function setCheckOut($check_out) {
        $this->check_out = $check_out;

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
     * @return pasajero_por_reserva
     */
    public function setIdTarifa($idtarifa) {
        $this->idtarifa = $idtarifa;

        return $this;
    }

    /**
     * @return tarifa_reserva
     */
    public function getTarifa() {
        if(!$this->tarifa) {
            $this->tarifa = $this->get_tarifa($this->idtarifa);
        }

        if($this->tarifa->getCodGrupoCliente() != $this->codgrupo && !$this->isCheckIn()) {
            $res = $this->getReserva();
            $this->tarifa = (new tarifa_reserva())->fetchByCategoriaYTipoPasajero($res->getCategoriaHabitacion(), $this->getCodGrupo());
        }
        return $this->tarifa;
    }

    /**
     * @param tarifa_reserva $tarifa
     *
     * @return pasajero_por_reserva
     */
    public function setTarifa($tarifa) {
        $this->tarifa = $tarifa;
        $this->idtarifa = $tarifa->getId();
        $this->codgrupo = $tarifa->getCodGrupoCliente();

        return $this;
    }

    /**
     * @return float
     */
    public function getTotal() {
        $tarifa = $this->getTarifa();
        if(!$tarifa && $this->idreserva) {
            $res = $this->getReserva();
            $tarifa = (new tarifa_reserva())->fetchByCategoriaYTipoPasajero($res->getCategoriaHabitacion(), $this->getCodGrupo());
        }

        if($this->esAdulto() || $this->esMenor()) {
            $monto = $tarifa->getMonto();
        } else {
            $monto = 0.0;
        }

        //Agregamos el total del pasajero al monto
        return $monto * $this->getCantidadDias();
    }

    /**
     * @return string
     */
    protected function install() {
        $habitacion = new habitacion();
        $reserva = new reserva();
        return '';
    }

    /**
     * @param int $idreserva
     *
     * @return pasajero_por_reserva[]
     */
    public static function getByReserva($idreserva) {
        if(intval($idreserva) > 0) {
            $pasajero_por_reserva = new self();
            return $pasajero_por_reserva->fetchAllByReserva($idreserva);
        } else {
            return array();
        }
    }

    /**
     * @param $id
     *
     * @return bool|pasajero_por_reserva
     */
    public static function get($id) {
        $pasajero_por_reserva = new self();

        return $pasajero_por_reserva->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool|pasajero_por_reserva
     */
    public function fetch($id) {
        $pasajero_por_reserva = $this->cache->get(str_replace('{id}',$id,self::CACHE_KEY_SINGLE));
        if($id && !$pasajero_por_reserva) {
            $pasajero_por_reserva = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set(str_replace('{id}',$id,self::CACHE_KEY_SINGLE), $pasajero_por_reserva);
        }
        if ($pasajero_por_reserva) {
            return new pasajero_por_reserva($pasajero_por_reserva[0]);
        } else {
            return false;
        }
    }

    /**
     * @return bool|array
     */
    public function fetchAll() {
        $pasporreslist = $this->cache->get_array(self::CACHE_KEY_ALL);
        if (!$pasporreslist) {
            $passporres = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY id ASC;");
            if ($passporres) {
                foreach ($passporres as $passporre) {
                    $pasporreslist[] = new pasajero_por_reserva($passporre);
                }
            }
            $this->cache->set(self::CACHE_KEY_ALL, $pasporreslist);
        }

        return $pasporreslist;
    }

    /**
     * @param $idreserva
     *
     * @return array
     */
    public function fetchAllByReserva($idreserva) {
        $pasporreslist = array();
        $passporres = $this->cache->get_array(str_replace('{id}','r'.$idreserva,self::CACHE_KEY_SINGLE));
        if( ((int) $idreserva) > 0 && !$passporres) {
            $passporres = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idreserva = " . (int)$idreserva . " ORDER BY fecha_nacimiento ASC;");
            $this->cache->set(str_replace('{id}','r'.$idreserva,self::CACHE_KEY_SINGLE), $pasporreslist);
        }
        if ($passporres) {
            foreach ($passporres as $passporre) {
                $pasporreslist[] = new pasajero_por_reserva($passporre);
            }
        }
        return $pasporreslist;
    }

    public function fecthCountByReserva($reservaid) {
        $cant = $this->db->select('SELECT count(id) as cant_pasajeros FROM '. $this->table_name .' WHERE idreserva = '.(int) $reservaid);

        return $cant[0]['cant_pasajeros'];
    }

    public function fecthCheckInCountByReserva($reservaid) {
        $cant = $this->db->select('SELECT count(id) as cant_pasajeros FROM '. $this->table_name .' WHERE idreserva = '.(int) $reservaid.' AND check_in is not null');

        return $cant[0]['cant_pasajeros'];
    }

    public function fetchCantCheckInByFecha($fecha) {
        $fecha = new DateTime($fecha);
        $cant = $this->db->select('SELECT COUNT(id) as cant_pasajeros FROM '. $this->table_name .' WHERE
    check_in >= '. $this->var2str($fecha->format('d-m-Y')) .' AND check_out IS NULL');

        return $cant[0]['cant_pasajeros'];
    }

    public function fetchCantPassByFechaAndCateg($fecha = null, $categoria = '%', $confirmed = false) {
        $sql = 'SELECT
  COUNT(*) as cant_pasajeros
FROM pasajero_por_reserva
  JOIN reserva ON (pasajero_por_reserva.idreserva = reserva.id)
  JOIN estado_reserva ON (reserva.idestado = estado_reserva.id)
WHERE
  codgrupo = "'.$categoria.'"
  AND check_out IS NULL
  AND (
    ("' . $fecha . '" BETWEEN pasajero_por_reserva.fecha_in AND pasajero_por_reserva.fecha_out) OR
    ("' . $fecha . '" BETWEEN reserva.fecha_in AND reserva.fecha_out)
  )';
        if($confirmed) {
            $sql .= "\n" . '  AND check_in IS NOT null';
        }

        

        $cant = $this->db->select($sql);
        return $cant[0]['cant_pasajeros'];
    }


    /**
     * @return bool|array
     */
    public function exists() {
        if (is_null($this->id)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$this->id . ";");
        }
    }

    /**
     * @return bool
     */
    public function test() {
        $status = true;
        $this->id = (int)$this->id;
        $this->nombre_completo = $this->no_html($this->nombre_completo);
        $this->tipo_documento = $this->no_html($this->tipo_documento);
        $this->documento = $this->no_html($this->documento);
        $this->fecha_nacimiento = $this->no_html($this->fecha_nacimiento);
        $this->codgrupo = $this->no_html($this->codgrupo);
        $this->codcliente = $this->no_html($this->codcliente);

        if(strlen($this->nombre_completo) < 1 || strlen($this->nombre_completo) > 250) {
	        $status = false;
            $this->new_error_msg( "Nombre de pasajero no válido." );
        }

        if($this->tipo_documento != 'DNI' && $this->tipo_documento != 'AFILIADO') {
	        $status = false;
            $this->new_error_msg("Tipo de documento no valido");
        }

        if(strlen($this->documento) < 1 || strlen($this->documento) > 10) {
	        $status = false;
            $this->new_error_msg("Documento no válido");
        }

        if(!$this->getTipoPasajero() || !$this->getTipoPasajero()->exists()) {
	        $status = false;
            $this->new_error_msg("Tipo de Pasajero Inválido");
        }

        /*$now = strtotime('today');
        if (strtotime($this->fecha_nacimiento) >= $now) {
	        $status = false;
            $this->new_error_msg("Fecha de nacimiento no válida");
        }*/

        return $status;
    }

    protected function insert() {
        $sql = 'INSERT ' . $this->table_name .
               ' SET '.
               'nombre_completo = ' . $this->var2str($this->nombre_completo) . ',' .
               'tipo_documento = ' . $this->var2str($this->tipo_documento) . ',' .
               'documento = ' . $this->var2str($this->documento) . ',' .
               'fecha_nacimiento = ' . $this->var2str($this->fecha_nacimiento) . ',' .
               'codgrupo = ' . $this->var2str($this->codgrupo) . ',' .
               'idtarifa = ' . $this->var2str($this->getIdTarifa()) . ',' .
               'codcliente = ' . $this->var2str($this->codcliente) . ',' .
               'idreserva = ' . $this->idreserva;
        if($this->fecha_in) {
            $sql .= ', fecha_in = ' . $this->var2str($this->fecha_in);
        }

        if($this->fecha_out) {
            $sql .= ', fecha_out = ' . $this->var2str($this->fecha_out);
        }

        if($this->check_in) {
            $sql .= ', check_in = ' . $this->var2str($this->check_in);
        }

        if($this->check_out) {
            $sql .= ', check_out = ' . $this->var2str($this->check_out);
        }

        if($this->idhabitacion) {
            $sql .= ', idhabitacion = ' . $this->var2str($this->idhabitacion);

        }
        $sql .= ';';
        $ret = $this->db->exec($sql);
        return $ret;
    }

    protected function update() {
        $sql = 'UPDATE ' . $this->table_name .
               ' SET '.
               'nombre_completo = ' . $this->var2str($this->nombre_completo) . ',' .
               'tipo_documento = ' . $this->var2str($this->tipo_documento) . ',' .
               'documento = ' . $this->var2str($this->documento) . ',' .
               'fecha_nacimiento = ' . $this->var2str($this->fecha_nacimiento) . ',' .
               'codgrupo = ' . $this->var2str($this->codgrupo) . ',' .
               'idtarifa = ' . $this->var2str($this->getIdTarifa()) . ',' .
               'codcliente = ' . $this->var2str($this->codcliente) . ',' .
               'idreserva = ' . $this->idreserva;
        if($this->fecha_in) {
            $sql .= ', fecha_in = ' . $this->var2str($this->fecha_in);
        }

        if($this->fecha_out) {
            $sql .= ', fecha_out = ' . $this->var2str($this->fecha_out);
        }

        if($this->check_in) {
            $sql .= ', check_in = ' . $this->var2str($this->check_in);
        }

        if($this->check_out) {
            $sql .= ', check_out = ' . $this->var2str($this->check_out);
        }

        if($this->idhabitacion) {
            $sql .= ', idhabitacion = ' . $this->var2str($this->idhabitacion);

        }
        $sql .= ' WHERE id = ' . (int)$this->id . ';';
        $ret = $this->db->exec($sql);
        return $ret;
    }

    /**
     * @return bool
     */
    public function save() {
        $ret = false;
        if ($this->test()) {
            $this->clean_cache();
            if ( $this->exists() ) {
                $ret = $this->update();
            } else {
                $ret = $this->insert();
                $this->setId( intval( $this->db->lastval() ) );
            }

        }
        return $ret;
    }

    /**
     * @return bool
     */
    public function delete() {
        $this->clean_cache();

        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE id = " . (int)$this->id . ";");
    }

    /**
     *
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
     * @param string $string
     * @param reserva $reserva
     *
     * @return pasajero_por_reserva
     */
    public static function parse($string, reserva $reserva) {
        $datos_pasajero = explode('#', $string);

        if(isset($datos_pasajero[3]) && in_array($datos_pasajero[3], array('menor_3', 'menor_7', 'adulto'))) {
            switch(strtolower($datos_pasajero[3])) {
                case 'menor_3':
                    $datos_pasajero[3] = "@" . strtotime('-' . (pasajero_por_reserva::EDAD_MIN_MENOR-1) . ' years');
                    break;
                case 'menor_7':
                    $datos_pasajero[3] = "@" . strtotime('-' . (pasajero_por_reserva::EDAD_MAX_MENOR-1) . ' years');
                    break;
                case 'adulto':
                    $datos_pasajero[3] = "@" . strtotime('-18 years');
                    break;
            }
        }

        //Si no está el idreserva
        if(!isset($datos_pasajero[7])) {
            $datos_pasajero[7] = $reserva->getId();
        }
        //Si no está el idpasajeroporreserva
        if(!isset($datos_pasajero[8])) {
            $datos_pasajero[8] = 0;
        }

        //Si no está el codcliente
        if(!isset($datos_pasajero[9])) {
            $datos_pasajero[9] = 0;
        }

        $pasj = new pasajero_por_reserva(array(
                                             'nombre_completo' => $datos_pasajero[0],
                                             'tipo_documento' => $datos_pasajero[1],
                                             'documento' => $datos_pasajero[2],
                                             'fecha_nacimiento' => $datos_pasajero[3],
                                             'fecha_in' => $datos_pasajero[4],
                                             'fecha_out' => $datos_pasajero[5],
                                             'codgrupo' => $datos_pasajero[6],
                                             'idreserva' => $datos_pasajero[7],
                                             'id' => $datos_pasajero[8],
                                             'codcliente' => $datos_pasajero[9]
                                         ));

        $pasj->setTarifa($reserva->getTarifa());

        return $pasj;


    }

    public function __toString() {
        return implode("#", array(
            $this->nombre_completo,
            $this->tipo_documento,
            $this->documento,
            $this->getFechaNacimiento(),
            $this->getFechaIn(),
            $this->getFechaOut(),
            $this->codgrupo,
            $this->idreserva,
            $this->id,
            $this->codcliente
        ));
    }

}