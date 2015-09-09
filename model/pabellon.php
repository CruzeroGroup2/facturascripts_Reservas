<?php

require_once 'base/fs_model.php';

require_model('habitacion.php');


class pabellon extends fs_model {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $descripcion;

    const CACHE_KEY_ALL = 'reserva_pabellon_all';
    const CACHE_KEY_SINGLE = 'reserva_pabellon_{id}';

    function __construct($data = array()) {
        parent::__construct('pabellon', 'plugins/reservas/');

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
     * @return pabellon
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if (is_int($id)) {
            $this->id = $id;
        }

        if (is_array($id)) {
            if (isset($id['idPabellon'])) {
                $this->id = $id['idPabellon'];
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
     * @return pabellon
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
  `$this->table_name`(`nombre`)
VALUES
  ('Central'),
  ('Sarmiento'),
  ('Californiano'),
  ('San Martin'),
  ('Anexo Ctral.'),
  ('Tala'),
  ('Yapeyu'),
  ('Piquillin'),
  ('Pilmaiquen'),
  ('Dominador'),
  ('Chalet Vip (5)'),
  ('Monsanto'),
  ('Centralito');
SQL;
        return $sql;
    }

    /**
     * @param $id
     *
     * @return bool|pabellon
     */
    public static function get($id) {
        $pabellon = new self();

        return $pabellon->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool|pabellon
     */
    public function fetch($id) {
        $pabellon = $this->cache->get(str_replace('{id}',$id,self::CACHE_KEY_SINGLE));
        if($id && !$pabellon) {
            $pabellon = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set(str_replace('{id}',$id,self::CACHE_KEY_SINGLE), $pabellon);
        }
        if ($pabellon) {
            return new pabellon($pabellon[0]);
        } else {
            return false;
        }
    }

    /**
     * @return pabellon[]
     */
    public function fetchAll() {
        $pabellonlist = $this->cache->get_array(self::CACHE_KEY_ALL);
        if (!$pabellonlist) {
            $pabellones = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY descripcion ASC;");
            if ($pabellones) {
                foreach ($pabellones as $pabellon) {
                    $pabellonlist[] = new pabellon($pabellon);
                }
            }
            $this->cache->set(self::CACHE_KEY_ALL, $pabellonlist);
        }

        return $pabellonlist;
    }

    /**
     * @param int $id
     *
     * @return habitacion[]
     */
    public function fetchHabitacionesByPabellon($id = 0) {
        $idpabellon = $id != 0 ? $id : $this->getId();

        $habitacion = new habitacion();
        return $habitacion->fetchByPabellon($idpabellon);
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
        $pabellonlist = array();
        $query = strtolower($this->no_html($query));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "id LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "lower(nomdbre) LIKE '%" . $buscar . "%'";
        }
        $consulta .= " ORDER BY nombre ASC";

        $pabellones = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($pabellones) {
            foreach ($pabellones as $pabellon) {
                $pabellonlist[] = new pabellon($pabellon);
            }
        }

        return $pabellonlist;
    }


}
