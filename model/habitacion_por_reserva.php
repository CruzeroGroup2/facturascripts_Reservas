<?php

require_once 'base/fs_model.php';
require_model('habitacion.php');
require_model('reserva.php');

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 15/07/2015
 * Time: 09:39 PM
 */
class habitacion_por_reserva extends fs_model {

    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var int
     */
    protected $idhabitacion = null;

    /**
     * @var habitacion
     */
    protected $habitacion = null;

    /**
     * @var int
     */
    protected $idreserva = null;

    function __construct($data = array()) {
        parent::__construct('habitacion_por_reserva','plugins/reservas/');

        $this->setValues($data);
    }

    /**
     * @param array $data
     */
    public function setValues($data = array()) {
        $this->setId($data);
        $this->idhabitacion = (isset($data['idhabitacion'])) ? $data['idhabitacion'] : null;
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
            if (isset($id['idhabitacionporreserva'])) {
                $this->id = $id['idhabitacionporreserva'];
            }

            if (isset($id['id'])) {
                $this->id = $id['id'];
            }
        }

        return $this;
    }

    /**
     * @return habitacion
     */
    public function getHabitacion() {
        if(!$this->habitacion) {
            $this->habitacion = $this->get_habitacion($this->idhabitacion);
        }
        return $this->habitacion;
    }

    /**
     * @param habitacion $habitacion
     *
     * @return habitacion_por_reserva
     */
    public function setHabitacion(habitacion $habitacion) {
        $this->habitacion = $habitacion;
        $this->idhabitacion = $habitacion->getId();

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
        $habitacion_por_reserva = $this->cache->get('reserva_habitacion_por_reserva_'.$id);
        if($id && !$habitacion_por_reserva) {
            $habitacion_por_reserva = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set('reserva_habitacion_por_reserva_'.$id, $habitacion_por_reserva);
        }
        if ($habitacion_por_reserva) {
            return new habitacion_por_reserva($habitacion_por_reserva[0]);
        } else {
            return false;
        }
    }

    /**
     * @return bool|array
     */
    public function fetchAll() {
        $habporreslist = $this->cache->get_array('m_habitacion_por_reserva_all');
        if (!$habporreslist) {
            $habsporres = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY id ASC;");
            if ($habsporres) {
                foreach ($habsporres as $habsporre) {
                    $habporreslist[] = new habitacion_por_reserva($habsporre);
                }
            }
            $this->cache->set('m_habitacion_por_reserva_all', $habporreslist);
        }

        return $habporreslist;
    }

    /**
     * @param $idreserva
     *
     * @return array
     */
    public function fetchAllByReserva($idreserva) {
        $habporreslist = $this->cache->get_array('reserva_habitacion_por_reserva_'.$idreserva);
        if(!$idreserva || !$habporreslist) {
            $habsporres = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idreserva = " . (int)$idreserva . " ORDER BY id ASC;");
            if ($habsporres) {
                foreach ($habsporres as $habsporre) {
                    $habporreslist[] = new habitacion_por_reserva($habsporre);
                }
                $this->cache->set('reserva_habitacion_por_reserva_'.$idreserva, $habporreslist);
            }
        }
        return $habporreslist;
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
        $this->idhabitacion = intval($this->no_html($this->idhabitacion));
        $this->idreserva = intval($this->no_html($this->idreserva));

        if (!is_a($this->getHabitacion(), 'habitacion') && !$this->getHabitacion()->exists()) {
            $this->new_error_msg("habitacion no válida.");
        } else {
            $status = true;
        }

        return $status;
    }

    protected function insert() {
        $sql = "INSERT INTO " . $this->table_name . " (idhabitacion,idreserva) VALUES (" .
               $this->idhabitacion . ",". $this->idreserva .");";

        $ret = $this->db->exec( $sql );
        return $ret;
    }

    protected function update() {
        $sql = "UPDATE " . $this->table_name . " SET idhabitacion = " . $this->idhabitacion .
               ", idreserva = " . $this->idreserva . " WHERE id = " . (int)$this->id . ";";

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
        $this->cache->delete('m_habitacion_por_reserva_all');
    }

    private function get_habitacion($idhabitacion) {
        if($idhabitacion) {
            return habitacion::get($idhabitacion);
        }
    }

    public function __toString() {
        return
            $this->idhabitacion . ':' .
            $this->idreserva . ':' .
            $this->id;
    }



}