<?php

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
     * @var int
     */
    protected $idreserva = null;

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

    const DATE_FORMAT = 'Y-m-d';

    const EDAD_MAX_MENOR = 5;
    const EDAD_MIN_MENOR = 2;

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
     */
    public function setValues($data = array()) {
        $this->setId($data);
        $this->nombre_completo = (isset($data['nombre_completo'])) ? $data['nombre_completo'] : null;
        $this->tipo_documento = (isset($data['tipo_documento'])) ? $data['tipo_documento'] : null;
        $this->documento = (isset($data['documento'])) ? $data['documento'] : null;
        if(isset($data['fecha_nacimiento'])) {
            $this->setFechaNacimiento($data['fecha_nacimiento']);
        }
        $this->idreserva = (isset($data['idreserva'])) ? $data['idreserva'] : null;

        //Datos correspondientes al checkin
        $this->idhabitacion = (isset($data['idhabitacion'])) ? $data['idhabitacion'] : null;
        $this->fecha_in = (isset($data['fecha_in'])) ? $data['fecha_in'] : null;
        $this->fecha_out = (isset($data['fecha_out'])) ? $data['fecha_out'] : null;
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
    public function setNombreCompleto( $nombre_completo ) {
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
    public function setTipoDocumento( $tipo_documento ) {
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
    public function setDocumento( $documento ) {
        $this->documento = $documento;

        return $this;
    }

    /**
     * @return string
     */
    public function getFechaNacimiento() {
        return $this->fecha_nacimiento;
    }

    /**
     * @param string $fecha_nacimiento
     *
     * @return pasajero_por_reserva
     */
    public function setFechaNacimiento( $fecha_nacimiento ) {
        $date = new DateTime($fecha_nacimiento);
        $this->fecha_nacimiento = $date->format(self::DATE_FORMAT);

        return $this;
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
     * @return bool
     */
    public function esAdulto() {
        return $this->getEdad() > self::EDAD_MAX_MENOR;
    }

    /**
     * @return bool
     */
    public function esMenor() {
        return (
            $this->getEdad() >= self::EDAD_MIN_MENOR &&
            $this->getEdad() <= self::EDAD_MAX_MENOR
        );
    }

    /**
     * @return int
     */
    public function getEdad() {
        return date_diff(date_create($this->getFechaNacimiento()), date_create('today'))->y;
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
        return $this->fecha_in;
    }

    /**
     * @param string $fecha_in
     *
     * @return pasajero_por_reserva
     */
    public function setFechaIn($fecha_in) {
        $this->fecha_in = $fecha_in;

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
     * @return pasajero_por_reserva
     */
    public function setFechaOut($fecha_out) {
        $this->fecha_out = $fecha_out;

        return $this;
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
            $passporres = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idreserva = " . (int)$idreserva . " ORDER BY id ASC;");
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
        $cant = $this->db->select('SELECT count(id) as cant_pasajeros FROM '. $this->table_name .' WHERE idreserva = '.(int) $reservaid.' AND fecha_in is not null');

        return $cant[0]['cant_pasajeros'];
    }

    public function fetchCantCheckInByFecha($fecha) {
        $fecha = new DateTime($fecha);
        $cant = $this->db->select('SELECT COUNT(id) as cant_pasajeros FROM '. $this->table_name .' WHERE fecha_in >= '. $this->var2str($fecha->format('Y-m-d')) .' AND fecha_out IS NULL');

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
        $status = false;
        $this->id = (int)$this->id;
        $this->nombre_completo = $this->no_html($this->nombre_completo);
        $this->tipo_documento = $this->no_html($this->tipo_documento);
        $this->documento = $this->no_html($this->documento);
        $this->fecha_nacimiento = $this->no_html($this->fecha_nacimiento);

        if(strlen($this->nombre_completo) < 1 || strlen($this->nombre_completo) > 250) {
            $this->new_error_msg( "Nombre de pasajero no válido." );
        }

        if($this->tipo_documento != 'DNI' && $this->tipo_documento != 'AFILIADO') {
            $this->new_error_msg("Tipo de documento no valido");
        }

        if(strlen($this->documento) < 1 || strlen($this->documento) > 10) {
            $this->new_error_msg("Documento no válido");
        }

        $now = strtotime('today');
        if (strtotime($this->fecha_nacimiento) >= $now) {
            $this->new_error_msg("Fecha de nacimiento no válida");
        }

        if (!$this->get_errors()) {
            $status = true;
        }

        return $status;
    }

    protected function insert() {
        $sql = 'INSERT ' . $this->table_name .
               ' SET '.
               'nombre_completo = ' . $this->var2str($this->nombre_completo) . ',' .
               'tipo_documento = ' . $this->var2str($this->tipo_documento) . ',' .
               'documento = ' . $this->var2str($this->documento) . ',' .
               'fecha_nacimiento = ' . $this->var2str($this->fecha_nacimiento) . ',' .
               'idreserva = ' . $this->idreserva;
        if($this->fecha_in) {
            $sql .= ', fecha_in = ' . $this->var2str($this->fecha_in);
        }

        if($this->fecha_out) {
            $sql .= ', fecha_out = ' . $this->var2str($this->fecha_out);
        }

        if($this->idhabitacion) {
            $sql .= ', idhabitacion = ' . $this->var2str($this->idhabitacion);

        }
        $sql .= ';';
        $ret = $this->db->exec( $sql );
        return $ret;
    }

    protected function update() {
        $sql = 'UPDATE ' . $this->table_name .
               ' SET '.
               'nombre_completo = ' . $this->var2str($this->nombre_completo) . ',' .
               'tipo_documento = ' . $this->var2str($this->tipo_documento) . ',' .
               'documento = ' . $this->var2str($this->documento) . ',' .
               'fecha_nacimiento = ' . $this->var2str($this->fecha_nacimiento) . ',' .
               'idreserva = ' . $this->idreserva;
        if($this->fecha_in) {
            $sql .= ', fecha_in = ' . $this->var2str($this->fecha_in);
        }

        if($this->fecha_out) {
            $sql .= ', fecha_out = ' . $this->var2str($this->fecha_out);
        }

        if($this->idhabitacion) {
            $sql .= ', idhabitacion = ' . $this->var2str($this->idhabitacion);

        }
        $sql .= ' WHERE id = ' . (int)$this->id . ';';
        $ret = $this->db->exec( $sql );
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

    public function __toString() {
        return
            $this->nombre_completo . ':' .
            $this->tipo_documento . ':' .
            $this->documento . ':' .
            $this->fecha_nacimiento . ':'.
            $this->idreserva . ':' .
            $this->id;
    }

}