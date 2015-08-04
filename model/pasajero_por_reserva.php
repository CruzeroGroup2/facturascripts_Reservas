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

    const EDAD_MAX_MENOR = 5;

    const EDAD_MIN_MENOR = 2;

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
        $this->fecha_nacimiento = (isset($data['fecha_nacimiento'])) ? $data['fecha_nacimiento'] : null;
        $this->idreserva = (isset($data['idreserva'])) ? $data['idreserva'] : null;
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
     * @return pabellon
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
        $this->fecha_nacimiento = $fecha_nacimiento;

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
     * @return array
     */
    public static function getByReserva($idreserva) {
        if(intval($idreserva) > 0) {
            $habitacion_por_reserva = new self();

            return $habitacion_por_reserva->fetchAllByReserva($idreserva);
        } else {
            return array();
        }
    }

    /**
     * @param $id
     *
     * @return bool|pabellon
     */
    public function fetch($id) {
        $pasajero_por_reserva = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
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
        $pasporreslist = $this->cache->get_array('m_pasajeros_por_reserva_all');
        if (!$pasporreslist) {
            $passporres = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY id ASC;");
            if ($passporres) {
                foreach ($passporres as $passporre) {
                    $pasporreslist[] = new pasajero_por_reserva($passporre);
                }
            }
            $this->cache->set('m_pasajeros_por_reserva_all', $pasporreslist);
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
        if(intval($idreserva) > 0) {
            $passporres = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idreserva = " . (int)$idreserva . " ORDER BY id ASC;");
            if ($passporres) {
                foreach ($passporres as $passporre) {
                    $pasporreslist[] = new pasajero_por_reserva($passporre);
                }
            }
        }
        return $pasporreslist;
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

    /**
     * @return bool
     */
    public function save() {
        if ($this->test()) {
            $this->clean_cache();
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name .
                       " SET ".
                           "nombre_completo = " . $this->var2str($this->nombre_completo).",".
                           "tipo_documento = " . $this->var2str($this->tipo_documento).",".
                           "documento = " . $this->var2str($this->documento) .",".
                           "fecha_nacimiento = " . $this->var2str($this->fecha_nacimiento) .",".
                           "idreserva = " . $this->idreserva .
                       " WHERE id = " . (int)$this->id . ";";
            } else {
                $sql = "INSERT INTO " . $this->table_name .
                       " (nombre_completo,tipo_documento,documento,fecha_nacimiento,idreserva) VALUES (" .
                       $this->var2str($this->nombre_completo) .",".
                       $this->var2str($this->tipo_documento) .",".
                       $this->var2str($this->documento) .",".
                       $this->var2str($this->fecha_nacimiento) .",".
                       $this->idreserva .");";
            }

            return $this->db->exec($sql);
        } else {
            return false;
        }
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
        $this->cache->delete('m_pasajeros_por_reserva_all');
    }

    private function get_habitacion($idhabitacion) {
        return habitacion::get($idhabitacion);
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