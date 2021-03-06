<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 15/07/2015
 * Time: 09:13 PM
 */

require_once 'base/fs_model.php';

require_model('pabellon.php');
require_model('categoria_habitacion.php');
require_model('estado_habitacion.php');
require_model('habitacion_por_reserva.php');

class habitacion extends fs_model {

    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var int $numero
     */
    protected $numero;

    /**
     * @var pabellon $pabellon
     */
    protected $pabellon;

    /**
     * @var int $idpabellon
     */
    protected $idpabellon;

    /**
     * @var int
     */
    protected $id_tipohabitacion;

    /**
     * @var tipohabitacion
     */
    protected $tipohabitacion;

    /**
     * @var int $plaza_maxima
     */
    protected $plaza_maxima;

    /**
     * @var categoria_habitacion $categoria
     */
    protected $categoria;

    /**
     * @var int $idcategoria
     */
    protected $idcategoria;

    /**
     * @var estado_habitacion $estado
     */
    protected $estado;

    /**
     * @var int $idestado
     */
    protected $idestado;

    const DISPONIBLE = 1;
    const RESERVADA = 2;
    const RESERVADA_SENIA = 3;
    const OCUPADA = 4;
    const NO_DISPONIBLE = 5;

    const CACHE_KEY_ALL = 'reserva_habitacion_all';
    const CACHE_KEY_SINGLE = 'reserva_habitacion_{id}';

    function __construct($data = array()) {
        parent::__construct('habitacion','plugins/reservas/');
        $this->setValues($data);
    }

    public function setValues($data = array()) {
        $this->setId($data);
        $this->numero = (isset($data['numero'])) ? $data['numero'] : null;
        $this->idpabellon = (isset($data['idpabellon'])) ? $data['idpabellon'] : null;
        $this->id_tipohabitacion = (isset($data['id_tipohabitacion'])) ? $data['id_tipohabitacion'] : null;
        $this->tipohabitacion = $this->get_tipohabitacion($this->id_tipohabitacion);
        $this->plaza_maxima = (isset($data['plaza_maxima'])) ? $data['plaza_maxima'] : null;
        $this->idcategoria = (isset($data['idcategoria'])) ? $data['idcategoria'] : null;
        $this->idestado = (isset($data['idestado'])) ? $data['idestado'] : null;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     * @return habitacion
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if(is_int($id)) {
            $this->id = $id;
            return $this;
        }

        if(is_array($id)) {
            if(isset($id['idHabitacion'])) {
                $this->id = $id['idHabitacion'];
                return $this;
            }

            if(isset($id['id'])) {
                $this->id = $id['id'];
                return $this;
            }
        }
    }

    /**
     * @return int
     */
    public function getNumero() {
        return $this->numero;
    }

    /**
     * @param int $numero
     * @return habitacion
     */
    public function setNumero($numero) {
        $this->numero = $numero;
        return $this;
    }

    /**
     * @return pabellon
     */
    public function getPabellon() {
        if(!$this->pabellon) {
            $this->pabellon = $this->get_pabellon($this->idpabellon);
        }
        return $this->pabellon;
    }

    /**
     * @param Pabellon $pabellon
     * @return habitacion
     */
    public function setPabellon($pabellon) {
        $this->pabellon = $pabellon;
        $this->idpabellon = $pabellon->getId();
        return $this;
    }

    /**
     * @return int
     */
    public function getIdPabellon() {
        return $this->idpabellon;
    }

    /**
     * @param int $idPabellon
     */
    public function setIdPabellon($idPabellon) {
        $this->idpabellon = $idPabellon;
    }

    /**
     * @param $idTipoHabitacion
     *
     * @return habitacion
     */
    public function setIdTipoHabiacion($idTipoHabitacion) {
        $this->id_tipohabitacion = $idTipoHabitacion;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdTipoHabiacion() {
        return $this->id_tipohabitacion;
    }

    /**
     * @return int
     */
    public function getPlazaMaxima() {
        return $this->plaza_maxima;
    }

    /**
     * @param int $plazaMaxima
     * @return habitacion
     */
    public function setPlazaMaxima($plazaMaxima) {
        $this->plaza_maxima = $plazaMaxima;
        return $this;
    }

    /**
     * @return categoria_habitacion
     */
    public function getCategoria() {
        if(!$this->categoria) {
            $this->categoria = $this->get_categoria($this->idcategoria);
        }
        return $this->categoria;
    }

    /**
     * @param categoria_habitacion $categoria
     * @return habitacion
     */
    public function setCategoria(categoria_habitacion $categoria) {
        $this->categoria = $categoria;
        $this->idcategoria = $categoria->getId();
        return $this;
    }

    /**
     * @return int
     */
    public function getIdCategoria() {
        return $this->idcategoria;
    }

    /**
     * @param int $idCategoria
     */
    public function setIdCategoria($idCategoria) {
        $this->idcategoria = $idCategoria;
    }

    /**
     * @return estado_habitacion
     */
    public function getEstado() {
        if(!$this->estado) {
            $this->estado = $this->get_estado($this->idestado);
        }
        return $this->estado;
    }

    /**
     * @param estado_habitacion $estado
     *
     * @return habitacion
     */
    public function setEstado(estado_habitacion $estado) {
        $this->estado = $estado;
        $this->idestado = $estado->getId();
        return $this;
    }

    /**
     * @param $idEstado
     *
     * @return habitacion
     */
    public function setIdEstado($idEstado) {
        $this->idestado = $idEstado;
        return $this;
    }

    /**
     * return int
     */
    public function getIdEstado() {
        return $this->idestado;
    }

    public function isAvailable($arrival, $departure) {
        $sql = "SELECT count(habitacion_por_reserva.idreserva) as cantidad_reservas FROM reserva
LEFT JOIN `habitacion_por_reserva` ON (reserva.id = habitacion_por_reserva.idreserva)
WHERE (
  (fecha_in BETWEEN " . $this->var2str($arrival . ' 12:00:00') . " AND " . $this->var2str($departure . ' 10:00:00') . ") OR
  (fecha_out BETWEEN " . $this->var2str($arrival . ' 12:00:00') . " AND " . $this->var2str($departure . ' 10:00:00') . ")
)
AND reserva.idestado NOT IN (6,7) -- Except for canceled reservs
AND habitacion_por_reserva.idhabitacion = " . $this->getId();
        $result = $this->db->select($sql);
        return ! $result[0]['cantidad_reservas'];
    }

    protected function install() {
        $pabellon = new pabellon();
        $categoria_habitacion = new categoria_habitacion();
        $estado = new estado_habitacion();
        //$habporres = new habitacion_por_reserva();
        return '';
    }

    public static function get($id) {
        $habitacion = new self();

        return $habitacion->fetch($id);
    }

    public static function getByNumero($numero) {
        $habitacion = new self();

        return $habitacion->fetchByNumero($numero);
    }

    /**
     * @param $id
     *
     * @return bool|habitacion
     */
    public function fetch($id) {
        $habitacion = $this->cache->get(str_replace('{id}',$id,self::CACHE_KEY_SINGLE));
        if($id && !$habitacion) {
            $habitacion = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set(str_replace('{id}',$id,self::CACHE_KEY_SINGLE), $habitacion);
        }
        if ($habitacion) {
            return new habitacion($habitacion[0]);
        } else {
            return false;
        }
    }

    /**
     * @param $numero
     *
     * @return bool|habitacion
     */
    public function fetchByNumero($numero) {
        $habitacion = $this->cache->get(str_replace('{id}',$numero,self::CACHE_KEY_SINGLE));
        if($numero && !$habitacion) {
            $habitacion = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE numero = " . (int)$numero. ";");
            $this->cache->set(str_replace('{id}',$numero,self::CACHE_KEY_SINGLE), $habitacion);
        }
        if ($habitacion) {
            return new habitacion($habitacion[0]);
        } else {
            return false;
        }
    }

    /**
     * @return habitacion[]
     */
    public function fetchAll() {
        $habitacionlist = $this->cache->get_array(self::CACHE_KEY_ALL);
        if (!$habitacionlist) {
            $habitaciones = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY numero ASC;");
            if ($habitaciones) {
                foreach ($habitaciones as $habitacion) {
                    $habitacionlist[] = new habitacion($habitacion);
                }
            }
            $this->cache->set(self::CACHE_KEY_ALL, $habitacionlist);
        }

        return $habitacionlist;
    }

    /**
     * @param int $idpabellon
     *
     * @return habitacion[]
     */
    public function fetchByPabellon($idpabellon = 0) {
        $habitacionlist = array();
        if (!$habitacionlist) {
            $habitaciones = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idpabellon = $idpabellon ORDER BY numero ASC;");
            if ($habitaciones) {
                foreach ($habitaciones as $habitacion) {
                    $habitacionlist[] = new habitacion($habitacion);
                }
            }
        }

        return $habitacionlist;
    }

    public function fetchByPabellonAndResDate($idpabellon = 0, $fecha = '') {
        $sql = "SELECT
	habitacion.*
FROM habitacion
	JOIN habitacion_por_reserva ON (habitacion_por_reserva.idhabitacion = habitacion.id)
	JOIN reserva ON (habitacion_por_reserva.idreserva = reserva.id)
WHERE
   idpabellon = $idpabellon
   AND " . $this->var2str($fecha) ." BETWEEN fecha_in AND fecha_out
ORDER BY numero ASC";
        $habitacionlist = array();
        if (!$habitacionlist) {
            $habitaciones = $this->db->select($sql);
            if ($habitaciones) {
                foreach ($habitaciones as $habitacion) {
                    $habitacionlist[] = new habitacion($habitacion);
                }
            }
        }

        return $habitacionlist;
    }

    /**
     * @param string $arrival
     * @param string $departure
     * @param int $idpabellon
     * @param string $number
     *
     * @return habitacion[]
     */
    public function fetchAvailableByPabellonAndParcialNumber($arrival, $departure, $idpabellon = 0, $number = '') {
        $habitacionlist = array();
        if (!$habitacionlist) {
            $sql = "SELECT * FROM " . $this->table_name . " WHERE
    idpabellon = $idpabellon AND
    numero like '$number%' AND
    id NOT IN (
      SELECT habitacion_por_reserva.idhabitacion
      FROM habitacion_por_reserva
        LEFT JOIN reserva ON (reserva.id = habitacion_por_reserva.idreserva)
      WHERE (
        (" . $this->var2str($arrival . ' 12:00:00') . " BETWEEN fecha_in AND fecha_out) OR
        (" . $this->var2str($departure . ' 12:00:00') . " BETWEEN fecha_in AND fecha_out)
      )
      AND reserva.idestado NOT IN (6,7) -- Except for canceled reservs
    )
ORDER BY numero ASC;";
            $habitaciones = $this->db->select($sql);
            if ($habitaciones) {
                foreach ($habitaciones as $habitacion) {
                    $habitacionlist[] = new habitacion($habitacion);
                }
            }
        }

        return $habitacionlist;
    }

    public function fetchCountPlazasDisponiblesByFecha($date) {
        $cant = $this->db->select('SELECT SUM(plaza_maxima) as plazas_disponibles  FROM ' . $this->table_name . ' WHERE idestado = 1');

        return $cant[0]['plazas_disponibles'];
    }

    /**
     * @param int $minGuestPorHab
     * @param string $arrival
     * @param string $departure
     * @param int $categoria
     *
     * @return array
     */
    public function findByAmount($minGuestPorHab = null, $arrival = null, $departure = null, $categoria = null) {
        //TODO: Agregar Ordenar por piso
        $sql = "SELECT
  count(id) AS cantidadHabitaciones,
  plaza_maxima,
  idpabellon,
  idcategoria,
  GROUP_CONCAT(habitacion.id) AS idsHabitacion
FROM habitacion
WHERE id NOT IN (
  SELECT habitacion_por_reserva.idhabitacion
  FROM habitacion_por_reserva
    LEFT JOIN reserva ON (reserva.id = habitacion_por_reserva.idreserva)
  WHERE (
    (fecha_in BETWEEN " . $this->var2str($arrival . ' 12:00:00') . " AND " . $this->var2str($departure . ' 10:00:00') . ") OR
    (fecha_out BETWEEN " . $this->var2str($arrival . ' 12:00:00') . " AND " . $this->var2str($departure . ' 10:00:00') . ")
  )
  AND reserva.idestado NOT IN (6,7) -- Except for canceled reservs
)";

        if(is_int($minGuestPorHab) || $minGuestPorHab > 0) {
            $sql .= ' AND plaza_maxima >= ' . $minGuestPorHab . "\n";
        }

        if(is_int($categoria) && $categoria != 0) {
            $sql .= ' AND habitacion.idcategoria = '.$categoria . "\n";
        }

        $sql .= 'GROUP BY plaza_maxima, idpabellon, idcategoria
ORDER BY plaza_maxima ASC;';
        //echo '<pre>'.$sql.'</pre>';
        return $this->db->select($sql);
    }

    public function exists() {
        if (is_null($this->id)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$this->id . ";");
        }
    }

    public function test() {
        $status = true;
        $this->id = (int)$this->id;

        if (!is_numeric($this->numero)) {
        	$status = false;
            $this->new_error_msg("Numero de habitacion no válido.");
        }

        if (!is_numeric($this->plaza_maxima)) {
        	$status = false;
            $this->new_error_msg("Plazas Maximas no válido.");
        }

        if (!is_numeric($this->idpabellon) && $this->getPabellon()->exists()) {
        	$status = false;
            $this->new_error_msg("Pabellon habitacion no válida.");
        }

        if (!is_numeric($this->idcategoria) && $this->getCategoria()->exists()) {
        	$status = false;
            $this->new_error_msg("Categoría habitacion no válida.");
        }

        return $status;
    }

    public function save() {
        if ($this->test()) {
            $this->clean_cache();
            if ($this->exists()) {
                $sql = 'UPDATE '. $this->table_name .
                       ' SET ' .
                           'numero = '. $this->getNumero() . ',' .
                           'idpabellon = ' . $this->getIdPabellon() . ',' .
                           //'id_tipohabitacion = ' . $this->getIdTipoHabiacion() .',' .
                           'plaza_maxima = ' . $this->intval($this->getPlazaMaxima()) . ',' .
                           'idcategoria = ' . $this->intval($this->getIdCategoria()) . ',' .
                           'idestado = ' . $this->intval($this->getIdEstado()) .
                       ' WHERE id = ' . $this->getId() . ';';
            } else {
                $sql = 'INSERT '. $this->table_name .
                       ' SET ' .
                           'numero = '. $this->getNumero() . ',' .
                           'idpabellon = ' . $this->getIdPabellon() . ',' .
                           //'id_tipohabitacion = ' . $this->getIdTipoHabiacion() .',' .
                           'plaza_maxima = ' . $this->intval($this->getPlazaMaxima()) . ',' .
                           'idcategoria = ' . $this->intval($this->getIdCategoria()) . ',' .
                           'idestado = ' . $this->intval($this->getIdEstado()) .
                       ';';
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
        $this->cache->delete(str_replace('{id}',$this->getId(),self::CACHE_KEY_SINGLE));
        $this->cache->delete(self::CACHE_KEY_ALL);
    }

    /**
     * @param $query
     * @param int $offset
     *
     * @return habitacion[]
     */
    public function search($query, $offset = 0) {
        $habitacionlist = array();
        $query = strtolower($this->no_html($query));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "id LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "lower(nombre) LIKE '%" . $buscar . "%'";
        }
        $consulta .= " ORDER BY nombre ASC";

        $habitaciones = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($habitaciones) {
            foreach ($habitaciones as $habitacion) {
                $habitacionlist[] = new habitacion($habitacion);
            }
        }

        return $habitacionlist;
    }

    /**
     * @param $idpabellon
     *
     * @return bool|pabellon
     */
    private function get_pabellon($idpabellon) {
        if($idpabellon) {
            return pabellon::get($idpabellon);
        }
    }

    /**
     * @param $id_tipohabitacion
     */
    private function get_tipohabitacion($id_tipohabitacion) {
    }

    /**
     * @param $idcategoria
     *
     * @return bool|categoria_habitacion
     */
    private function get_categoria($idcategoria) {
        if($idcategoria) {
            return categoria_habitacion::get($idcategoria);
        }
    }

    /**
     * @param $idestado
     *
     * @return bool|estado_habitacion
     */
    private function get_estado($idestado) {
        if($idestado) {
            return estado_habitacion::get($idestado);
        }
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string) $this->numero;
    }

}