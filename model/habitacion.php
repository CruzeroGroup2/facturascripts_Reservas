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

    function __construct($data = array()) {
        parent::__construct('habitacion','plugins/reservas/');
        $this->setValues($data);
    }

    public function setValues($data = array()) {
        $this->setId($data);
        $this->numero = (isset($data['numero'])) ? $data['numero'] : null;
        $this->idpabellon = (isset($data['idpabellon'])) ? $data['idpabellon'] : null;
        $this->pabellon = $this->get_pabellon($this->idpabellon);
        $this->id_tipohabitacion = (isset($data['id_tipohabitacion'])) ? $data['id_tipohabitacion'] : null;
        $this->tipohabitacion = $this->get_tipohabitacion($this->id_tipohabitacion);
        $this->plaza_maxima = (isset($data['plaza_maxima'])) ? $data['plaza_maxima'] : null;
        $this->idcategoria = (isset($data['idcategoria'])) ? $data['idcategoria'] : null;
        $this->categoria = $this->get_categoria($this->idcategoria);
        $this->idestado = (isset($data['idestado'])) ? $data['idestado'] : null;
        $this->estado = $this->get_estado($this->idestado);
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

    protected function install() {
        $pabellon = new pabellon();
        $categoria_habitacion = new categoria_habitacion();
        $estado = new estado_habitacion();
        $habporres = new habitacion_por_reserva();
        return '';
    }

    public static function get($id) {
        $habitacion = new self();

        return $habitacion->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool|habitacion
     */
    public function fetch($id) {
        $habitacion = $this->cache->get('reserva_habitacion_'.$id);
        if($id && !$habitacion) {
            $habitacion = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
            $this->cache->set('reserva_habitacion_'.$id, $habitacion);
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
        $habitacionlist = $this->cache->get_array('m_habitacion_all');
        if (!$habitacionlist) {
            $habitaciones = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY numero ASC;");
            if ($habitaciones) {
                foreach ($habitaciones as $habitacion) {
                    $habitacionlist[] = new habitacion($habitacion);
                }
            }
            $this->cache->set('m_habitacion_all', $habitacionlist);
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

    /**
     * @param null $minGuestPorHab
     * @param null $arrival
     * @param null $departure
     * @param null $categoria
     *
     * @return array
     */
    public function findByAmount($minGuestPorHab = null, $arrival = null, $departure = null, $categoria = null) {
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
  WHERE fecha_in >= '$arrival' AND fecha_out <= '$departure'
)";

        if(is_int($minGuestPorHab) || $minGuestPorHab > 0) {
            $sql .= ' AND plaza_maxima >= '.$minGuestPorHab;
        }

        if(is_int($categoria) && $categoria != 0) {
            $sql .= ' AND idcategoria >= '.$categoria;
        }

        $sql .= '
GROUP BY plaza_maxima, idpabellon, idcategoria
ORDER BY plaza_maxima ASC;';
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
        $status = false;
        $this->id = (int)$this->id;

        if (!is_numeric($this->numero)) {
            $this->new_error_msg("Numero de habitacion no válido.");
        }

        if (!is_numeric($this->plaza_maxima)) {
            $this->new_error_msg("Plazas Maximas no válido.");
        }

        if (!is_numeric($this->idpabellon) && $this->getPabellon()->exists()) {
            $this->new_error_msg("Pabellon habitacion no válida.");
        }

        if (!is_numeric($this->idcategoria) && $this->getCategoria()->exists()) {
            $this->new_error_msg("Categoría habitacion no válida.");
        }

        if(!$this->get_errors()) {
            $status = true;
        }


        return $status;
    }

    public function save() {
        if ($this->test()) {
            $this->clean_cache();
            if ($this->exists()) {
                $sql = 'UPDATE'. $this->table_name .
                       ' SET ' .
                           'numero = '. $this->getNumero() . ',' .
                           'idpabellon = ' . $this->getIdPabellon() . ',' .
                           //'id_tipohabitacion = ' . $this->getIdTipoHabiacion() .',' .
                           'plaza_maxima = ' . $this->intval($this->getPlazaMaxima()) . ',' .
                           'idcategoria = ' . $this->intval($this->getIdCategoria()) . ',' .
                           'idestado = ' . $this->intval($this->getIdEstado()) .
                       'WHERE id = ' . $this->getId() . ';';
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
        $this->cache->delete('m_tipo_cliente_all');
    }


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

    private function get_pabellon($idpabellon) {
        if($idpabellon) {
            return pabellon::get($idpabellon);
        }
    }

    private function get_tipohabitacion($id_tipohabitacion) {
    }

    private function get_categoria($idcategoria) {
        if($idcategoria) {
            return categoria_habitacion::get($idcategoria);
        }
    }

    private function get_estado($idestado) {
        if($idestado) {
            return estado_habitacion::get($idestado);
        }
    }

}