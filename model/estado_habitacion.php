<?php

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 26/07/2015
 * Time: 02:23 AM
 */

require_once 'base/fs_model.php';


class estado_habitacion extends fs_model {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $descripcion;

    const CACHE_KEY_ALL = 'reserva_estado_habitacion_all';
    const CACHE_KEY_SINGLE = 'reserva_estado_habitacion_{id}';

    const DISPONIBLE = 'disponible';
    const NO_DISPONIBLE = 'no_disponible';

    function __construct($data = array()) {
        parent::__construct('estado_habitacion', 'plugins/reservas/');

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
     * @return estado_habitacion
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
     * @return estado_habitacion
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
  ('Disponible'),
  ('No Disponible');
SQL;

        return $sql;
    }

    /**
     * @param $id
     *
     * @return bool|estado_habitacion
     */
    public static function get($id) {
        if($id) {
            $estado = new self();

            return $estado->fetch($id);
        }
    }

    /**
     * @param $id
     *
     * @return bool|estado_habitacion
     */
    public function fetch($id) {
        $estado = $this->cache->get(str_replace('{id}',$id,self::CACHE_KEY_SINGLE));
        if($id && !$estado) {
            $estado = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set(str_replace('{id}',$id,self::CACHE_KEY_SINGLE), $estado);
        }
        if ($estado) {
            return new estado_habitacion($estado[0]);
        } else {
            return false;
        }
    }

    /**
     * @return bool|array
     */
    public function fetchAll() {
        $estadolist = $this->cache->get_array(self::CACHE_KEY_ALL);
        if (!$estadolist) {
            $estados = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY descripcion ASC;");
            if ($estados) {
                foreach ($estados as $estado) {
                    $estadolist[] = new estado_habitacion($estado);
                }
            }
            $this->cache->set(self::CACHE_KEY_ALL, $estadolist);
        }

        return $estadolist;
    }

    /**
     * @return bool|type
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
        $this->cache->delete(str_replace('{id}',$this->getId(),self::CACHE_KEY_SINGLE));
        $this->cache->delete(self::CACHE_KEY_ALL);
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

    public function __toString() {
        return str_replace(" ","_", strtolower($this->getDescripcion()));
    }

}