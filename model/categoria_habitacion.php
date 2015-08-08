<?php


require_once 'base/fs_model.php';

class categoria_habitacion extends fs_model {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $nombre;

    function __construct($data = array()) {
        parent::__construct('categoria_habitacion', 'plugins/reservas/');
        $this->setValues($data);
    }

    public function setValues($data = array()) {
        $this->setId($data);
        $this->nombre = (isset($data['nombre'])) ? $data['nombre'] : null;
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
     * @return categoria_habitacion
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if (is_int($id)) {
            $this->id = $id;
        }

        if (is_array($id)) {
            if (isset($id['idcategoria'])) {
                $this->id = $id['idcategoria'];
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
    public function getNombre() {
        return $this->nombre;
    }

    /**
     * @param string $nombre
     *
     * @return categoria_habitacion
     */
    public function setNombre($nombre) {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Esta función es llamada al crear una tabla.
     * Permite insertar valores en la tabla.
     */
    protected function install() {
        $sql = <<<SQL
INSERT INTO
  `categoria_habitacion`(`id`,`nombre`)
VALUES
    (1,'Casa'),
    (2,'Chalet'),
    (3,'Habitacion'),
    (4,'Departamento');
SQL;

        return $sql;
    }

    public static function get($id) {
        $categoria = new self();

        return $categoria->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool|categoria_habitacion
     */
    public function fetch($id) {
        $categoria = $this->cache->get('reserva_categoria_habitacion_'.$id);
        if($id && !$categoria) {
            $categoria = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set('reserva_categoria_habitacion_'.$id, $categoria);
        }
        if ($categoria) {
            return new categoria_habitacion($categoria[0]);
        } else {
            return false;
        }
    }

    /**
     * @return bool|categoria_habitacion
     */
    public function fetchAll() {
        $categorialist = $this->cache->get_array('m_categoria_habitacion_all');
        if (!$categorialist) {
            $categorias = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY nombre ASC;");
            if ($categorias) {
                foreach ($categorias as $c) {
                    $categorialist[] = new categoria_habitacion($c);
                }
            }
            $this->cache->set('m_categoria_habitacion_all', $categorialist);
        }

        return $categorialist;
    }

    public function exists() {
        if (is_null($this->id)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$this->id . ";");
        }
    }

    public function test() {
        $status = false;
        $this->id = (int)$this->id;
        $this->nombre = $this->no_html($this->nombre);

        if (strlen($this->nombre) < 1 OR strlen($this->nombre) > 50) {
            $this->new_error_msg("Nombre de Categoria de Habitacion no válido.");
        } else {
            $status = true;
        }

        return $status;
    }

    public function save() {
        if ($this->test()) {
            $this->clean_cache();
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET nombre = " . $this->var2str($this->nombre) . "
               WHERE id = " . (int)$this->id . ";";
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (nombre)
               VALUES (" . $this->var2str($this->nombre) . ");";
            }

            return $this->db->exec($sql);
        } else {
            return false;
        }
    }

    public function delete() {
        $this->clean_cache();

        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE id = " . (int)$this->id . ";");
    }

    private function clean_cache() {
        $this->cache->delete('m_categoria_habitacion_all');
    }

    public function search($query, $offset = 0) {
        $categorialist = array();
        $query = strtolower($this->no_html($query));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "id LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "lower(nombre) LIKE '%" . $buscar . "%'";
        }
        $consulta .= " ORDER BY nombre ASC";

        $categorias = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($categorias) {
            foreach ($categorias as $c) {
                $categorialist[] = new categoria_habitacion($c);
            }
        }

        return $categorialist;
    }

}