<?php

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 26/07/2015
 * Time: 02:23 AM
 */

require_once 'base/fs_model.php';


class estado_reserva extends fs_model {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $descripcion;

    const INCOMPLETA = 'Incompleta';
    const SINSENA = 'Sin Señar';
    const SENADO =  'Señado';
    const PAGO =  'Pago';
    const CHECKIN =  'Checked-In';
    const CANCELADA =  'Cancelada';

    function __construct($data = array()) {
        parent::__construct('estado_reserva', 'plugins/reservas/');

        $this->setValues($data);
    }

    /**
     * @param array $data
     */
    public function setValues($data = array()) {
        $this->setId($data);
        $this->descripcion = (isset($data['descripcion'])) ? $data['descripcion'] : null;
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
     * @return estado_reserva
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if (is_int($id)) {
            $this->id = $id;
        }

        if (is_array($id)) {
            if (isset($id['idestado'])) {
                $this->id = $id['idestado'];
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
    public function getDescripcion() {
        return $this->descripcion;
    }

    /**
     * @param string $descripcion
     *
     * @return estado_reserva
     */
    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;

        return $this;
    }

    /**
     * @return string
     */
    protected function install() {
        $sql = <<<SQL
INSERT INTO
  `$this->table_name`(`descripcion`)
VALUES
  ('Incompleta'),
  ('Sin Señar'),
  ('Señado'),
  ('Pago'),
  ('Checked-In'),
  ('Cancelada');
SQL;

        return $sql;
    }

    /**
     * @param $id
     *
     * @return bool|estado_reserva
     */
    public static function get($id) {
        $estado = new self();

        if((int) $id == 0 || is_string($id)) {
            return $estado->fetchByDesc($id);
        } else {
            return $estado->fetch($id);
        }
    }

    /**
     * @param $id
     *
     * @return bool|estado_reserva
     */
    public function fetch($id) {
        $estado = $this->cache->get('reserva_estado_reserva_'.$id);
        if($id && !$estado) {
            $estado = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set('reserva_estado_reserva_'.$id, $estado);
        }
        if ($estado) {
            return new estado_reserva($estado[0]);
        } else {
            return false;
        }
    }

    public function fetchByDesc($descripcion = '') {
        if(empty($descripcion)) {
            $descripcion = self::INCOMPLETA;
        }
        $sql = "SELECT * FROM " . $this->table_name . " WHERE descripcion = " . $this->var2str($descripcion);
        $estado = $this->db->select($sql);
        if ($estado) {
            return new estado_reserva($estado[0]);
        } else {
            return false;
        }
    }

    /**
     * @return bool|array
     */
    public function fetchAll() {
        $estadolist = $this->cache->get_array('m_estado_reserva_all');
        if (!$estadolist) {
            $estados = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY descripcion ASC;");
            if ($estados) {
                foreach ($estados as $estado) {
                    $estadolist[] = new estado_reserva($estado);
                }
            }
            $this->cache->set('m_estado_reserva_all', $estadolist);
        }

        return $estadolist;
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
        $this->descripcion = $this->no_html($this->descripcion);

        if (strlen($this->descripcion) < 1 OR strlen($this->descripcion) > 50) {
            $this->new_error_msg("Descripcion no válida.");
        } else {
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
                $sql = "UPDATE " . $this->table_name . " SET descripcion = " . $this->var2str($this->descripcion) . "
               WHERE id = " . (int)$this->id . ";";
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (descripcion)
               VALUES (" . $this->var2str($this->descripcion) . ");";
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
        $this->cache->delete('m_estado_reserva_all');
    }

    /**
     * @param $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0) {
        $estadolist = array();
        $query = strtolower($this->no_html($query));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "id LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "lower(nomdbre) LIKE '%" . $buscar . "%'";
        }
        $consulta .= " ORDER BY nombre ASC";

        $estados = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($estados) {
            foreach ($estados as $estado) {
                $estadolist[] = new estado_habitacion($estado);
            }
        }

        return $estadolist;
    }


}