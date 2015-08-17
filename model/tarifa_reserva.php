<?php

require_once 'base/fs_model.php';

require_model('categoria_habitacion.php');
require_model('grupo_clientes.php');


class tarifa_reserva extends fs_model {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $monto;

    /**
     * @var string
     */
    protected $fecha_inicio;

    /**
     * @var string
     */
    protected $fecha_fin;

    /**
     * @var categoria_habitacion
     */
    protected $categoria_habitacion;

    /**
     * @var int
     */
    protected $idcategoria;

    /**
     * @var grupo_clientes
     */
    protected $grupo_clientes;

    /**
     * @var int
     */
    protected $codgrupo;

    /**
     * @param array $data
     */
    public function __construct($data = array()) {
        parent::__construct('tarifa', 'plugins/reservas/');
        $this->setValues($data);
    }

    /**
     * @param array $data
     */
    public function setValues($data = array()) {
        $this->setId($data);
        $this->monto = (isset($data['monto'])) ? floatval($data['monto']) : null;
        $this->fecha_inicio = (isset($data['fecha_inicio'])) ? $data['fecha_inicio'] : date("Y-m-d H:i:s");
        $this->fecha_fin = (isset($data['fecha_fin'])) ? $data['fecha_fin'] : null;
        $this->idcategoria = (isset($data['idcategoria'])) ? $data['idcategoria'] : null;
        $this->codgrupo = (isset($data['codgrupo'])) ? $data['codgrupo'] : null;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     * @return tarifa_reserva
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if(is_int($id)) {
            $this->id = $id;
            return $this;
        }

        if(is_array($id)) {
            if(isset($id['idTarifa'])) {
                $this->id = $id['idTarifa'];
                return $this;
            }

            if(isset($id['id'])) {
                $this->id = $id['id'];
                return $this;
            }
        }
    }

    /**
     * @return float
     */
    public function getMonto() {
        return $this->monto;
    }

    /**
     * @param int $monto
     * @return tarifa_reserva
     */
    public function setMonto($monto) {
        $this->monto = floatval($monto);
        return $this;
    }

    /**
     * @return string
     */
    public function getFechaInicio() {
        return $this->fecha_inicio;
    }

    /**
     * @param string $fecha_inicio
     * @return tarifa_reserva
     */
    public function setFechaInicio($fecha_inicio) {
        $this->fecha_inicio = $fecha_inicio;
        return $this;
    }

    /**
     * @return string
     */
    public function getFechaFin() {
        return $this->fecha_fin;
    }

    /**
     * @param string $fecha_fin
     * @return tarifa_reserva
     */
    public function setFechaFin($fecha_fin) {
        $this->fecha_fin = $fecha_fin;
        return $this;
    }

    /**
     * @return categoria_habitacion
     */
    public function getCategoriaHabitacion() {
        if(!$this->categoria_habitacion) {
            $this->categoria_habitacion = $this->get_categoria($this->idcategoria);
        }
        return $this->categoria_habitacion;
    }

    /**
     * @param categoria_habitacion $categoria_habitacion
     * @return tarifa_reserva
     */
    public function setCategoriaHabitacion($categoria_habitacion) {
        $this->categoria_habitacion = $categoria_habitacion;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdCategoriaHabitacion() {
        return $this->idcategoria;
    }

    /**
     * @param int $idCategoria
     * @return tarifa_reserva
     */
    public function setIdCategoriaHabitacion($idCategoria) {
        $this->idcategoria = $idCategoria;
        return $this;
    }

    /**
     * @return grupo_clientes
     */
    public function getGrupoCliente() {
        if(!$this->grupo_clientes) {
            $this->grupo_clientes = $this->get_grupo_cliente($this->codgrupo);
        }
        return $this->grupo_clientes;
    }

    /**
     * @param grupo_clientes $grupo_clientes
     * @return tarifa_reserva
     */
    public function setGrupoCliente(grupo_clientes $grupo_clientes) {
        $this->grupo_clientes = $grupo_clientes;
        $this->codgrupo = $grupo_clientes->codgrupo;
        return $this;
    }

    /**
     * @return string
     */
    public function getCodGrupoCliente() {
        return $this->codgrupo;
    }

    /**
     * @param string  $codgrupo
     * @return tarifa_reserva
     */
    public function setCodGrupoCliente($codgrupo) {
        $this->codgrupo = $codgrupo;
        return $this;
    }

    /**
     * Esta función es llamada al crear una tabla.
     * Permite insertar valores en la tabla.
     */
    protected function install() {
        $categoria_habitacion = new categoria_habitacion();
        $grupos_cliente = new grupo_clientes();

        return '';
    }

    /**
     * @param $id
     *
     * @return bool|tarifa_reserva
     */
    public static function get($id) {
        $tarifa = new self;
        return $tarifa->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool|tarifa_reserva
     */
    public function fetch($id) {
        $tarifa = $this->cache->get('reserva_tarifa_reserva_'.$id);
        if($id && !$tarifa) {
            $tarifa = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ". (int) $id.";");
            $this->cache->set('reserva_tarifa_reserva_'.$id, $tarifa);
        }
        if($tarifa) {
            return new self($tarifa[0]);
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function fetchAll() {
        $tarifalist = $this->cache->get_array('m_tarifa_all');
        if(!$tarifalist) {
            $tarifas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fecha_fin is NULL");
            if($tarifas) {
                foreach ($tarifas as $tarifa) {
                    $tarifalist[] = new tarifa_reserva($tarifa);
                }
            }
            $this->cache->set('m_tarifa_all', $tarifalist);
        }
        return $tarifalist;
    }

    /**
     * @param int $idcategoria
     * @param string $codgrupo
     *
     * @return bool|tarifa_reserva
     */
    public function fetchByCategoriaYTipoPasajero($idcategoria = 0, $codgrupo = '') {
        $tarifa = $this->db->select("SELECT * FROM ".$this->table_name.
                                     " WHERE
                                         idcategoria = $idcategoria AND
                                         codgrupo = " . $this->var2str($codgrupo) ." AND
                                         fecha_fin is NULL");
        if($tarifa) {
            return new self($tarifa[0]);
        } else {
            trigger_error("Tarifa no encontrada para la cegoria id = '".$idcategoria."' y tipo cliente = '$codgrupo'", E_USER_ERROR);
            return false;
        }
    }

    public function fetchByCategoria($idcategoria = 0) {
        $tarifalist = array();
        $tarifas = $this->db->select("SELECT * FROM ".$this->table_name.
                                     " WHERE
                                         idcategoria = $idcategoria AND
                                         fecha_fin is NULL");
        if($tarifas) {
            foreach ($tarifas as $tarifa) {
                $tarifalist[] = new tarifa_reserva($tarifa);
            }
        }
        return $tarifalist;
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

        if ($this->codgrupo == 0) {
            $this->new_error_msg("Grupo de Cliente no válido.");
        }

        if ($this->idcategoria == 0) {
            $this->new_error_msg("Categoría habitacion no válida.");
        }

        if(!is_float($this->monto) || $this->monto < 0) {
            $this->new_error_msg("Monto de tarifa no válido!");
        }

        if(!$this->get_errors()) {
            $status = true;
        }


        return $status;
    }

    protected function insert() {
        $sql = 'INSERT '. $this->table_name .
               ' SET ' .
                    'monto = '. $this->getMonto() . ',' .
                    'fecha_inicio = ' . $this->var2str($this->getFechaInicio()) . ',' .
                    'fecha_fin = ' . $this->var2str($this->getFechaFin()) .',' .
                    'idcategoria = ' . $this->intval($this->getIdCategoriaHabitacion()) . ',' .
                    'codgrupo = ' . $this->intval($this->getCodGrupoCliente()) .
               ';';
        return $this->db->exec($sql);
    }

    protected function update() {
        $sql = 'UPDATE'. $this->table_name .
               ' SET ' .
                    'monto = '. $this->getMonto() . ',' .
                    'fecha_inicio = ' . $this->var2str($this->getFechaInicio()) . ',' .
                    'fecha_fin = ' . $this->var2str($this->getFechaFin()) .',' .
                    'idcategoria = ' . $this->intval($this->getIdCategoriaHabitacion()) . ',' .
                    'codgrupo = ' . $this->intval($this->getCodGrupoCliente()) .
               'WHERE id = ' . $this->getId() . ';';
        return $this->db->exec($sql);
    }

    /**
     * Esta función sirve tanto para insertar como para actualizar
     * los datos del objeto en la base de datos.
     */
    public function save() {
        if ($this->test()) {
            $this->clean_cache();
            // We don't update old tarifas! instead we add a new one!
            if ($this->exists()) {
                $tarifaOld = clone $this;
                $tarifaOld->setFechaFin(date('Y-m-d H:i:s'));
                $this->id = null;
                return $tarifaOld->update() && $this->insert();
            } else {
                return $this->insert();
            }
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
     * @return void
     */
    private function clean_cache() {
        $this->cache->delete('m_tarifa_all');
    }

    /**
     * @param $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0) {
        $tarifalist = array();
        $query = strtolower($this->no_html($query));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "id LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "lower(nombre) LIKE '%" . $buscar . "%'";
        }
        $consulta .= " ORDER BY nombre ASC";

        $tarifas = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($tarifas) {
            foreach ($tarifas as $tarifa) {
                $tarifalist[] = new tarifa($tarifa);
            }
        }

        return $tarifalist;
    }


    /**
     * @param $id
     *
     * @return categoria_habitacion
     */
    public function get_categoria($id) {
        if($id) {
            return categoria_habitacion::get($id);
        }
    }

    /**
     * @param $codgrupo
     *
     * @return bool|grupo_clientes
     */
    private function get_grupo_cliente($codgrupo) {
        $grupo_cliente = new grupo_clientes();

        return $grupo_cliente->get($codgrupo);
    }

    public function __toArray() {
        return array(
            'idtarifa' => $this->getId(),
            'monto' => $this->getMonto(),
            'idcategoria' => $this->getIdCategoriaHabitacion(),
            'codgrupo' => $this->getCodGrupoCliente()
        );
    }


}