<?php

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 16/07/2015
 * Time: 07:13 PM
 */

require_once 'base/fs_model.php';


class pago extends fs_model {

    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $fecha_pago = null;

    /**
     * @var int
     */
    protected $idformapago = null;

    /**
     * @var forma_pago
     */
    protected $formadepago = null;

    /**
     * @var float
     */
    protected $monto = null;

    /**
     * @var string
     */
    protected $codcliente = null;

    /**
     * @var cliente
     */
    protected $cliente = null;

    /**
     * @var int
     */
    protected $idreserva = null;

    /**
     * @var reserva
     */
    protected $reserva = null;

    /**
     * @var int
     */
    protected $idcuenta = null;

    /**
     * @var cuenta
     */
    protected $cuenta = null;

    /**
     * @var string
     */
    protected $comprobante = null;

    /**
     * @param array $data
     */
    function __construct($data = array()) {
        parent::__construct('pago','plugins/reservas/');
        $this->setValues($data);
    }

    /**
     * @param array $data
     */
    public function setValues($data = array()) {
        $this->setId($data);
        $this->fecha_pago = (isset($data['fecha_pago'])) ? $data['fecha_pago'] : null;
        $this->idformapago = (isset($data['idformapago'])) ? $data['idformapago'] : null;
        $this->formadepago = (isset($data['formadepago'])) ? $data['formadepago'] : null;
        $this->monto = floatval( (isset($data['monto'])) ? $data['monto'] : null);
        $this->codcliente = (isset($data['codcliente'])) ? $data['codcliente'] : null;
        $this->cliente = $this->get_cliente($this->codcliente);
        $this->idreserva = (isset($data['idreserva'])) ? $data['idreserva'] : null;
        //$this->reserva = $this->get_reserva($this->idreserva);
        $this->idcuenta = (isset($data['idcuenta'])) ? $data['idcuenta'] : null;
        $this->cuenta = $this->get_cuenta($this->idcuenta);
        $this->comprobante = (isset($data['comprobante'])) ? $data['comprobante'] : null;
    }

    /**
     * @param int|array $id
     *
     * @return pago
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if (is_int($id)) {
            $this->id = $id;
        }

        if (is_array($id)) {
            if (isset($id['idpago'])) {
                $this->id = $id['idpago'];
            }

            if (isset($id['id'])) {
                $this->id = $id['id'];
            }
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFechaPago() {
        return $this->fecha_pago;
    }

    /**
     * @param null $fecha_pago
     *
     * @return pago
     */
    public function setFechaPago($fecha_pago) {
        $this->fecha_pago = $fecha_pago;

        return $this;
    }

    /**
     * @return null
     */
    public function getIdFormaPago() {
        return $this->idformapago;
    }

    /**
     * @param null $idformapago
     *
     * @return pago
     */
    public function setIdFormaPago($idformapago) {
        $this->idformapago = $idformapago;

        return $this;
    }

    /**
     * @return null
     */
    public function getFormaDePago() {
        return $this->formadepago;
    }

    /**
     * @param null $formadepago
     *
     * @return pago
     */
    public function setFormaDePago($formadepago) {
        $this->formadepago = $formadepago;

        return $this;
    }

    /**
     * @return float
     */
    public function getMonto() {
        return $this->monto;
    }

    /**
     * @param float $monto
     *
     * @return pago
     */
    public function setMonto($monto) {
        $this->monto = $monto;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodCliente() {
        return $this->codcliente;
    }

    /**
     * @param string $codcliente
     *
     * @return pago
     */
    public function setCodcliente($codcliente) {
        $this->codcliente = $codcliente;

        return $this;
    }

    /**
     * @return null
     */
    public function getCliente() {
        return $this->cliente;
    }

    /**
     * @param cliente $cliente
     *
     * @return pago
     */
    public function setCliente(cliente $cliente) {
        $this->cliente = $cliente;
        $this->codcliente = $cliente->codcliente;

        return $this;
    }

    /**
     * @return null
     */
    public function getIdReserva() {
        return $this->idreserva;
    }

    /**
     * @param null $idreserva
     *
     * @return pago
     */
    public function setIdReserva($idreserva) {
        $this->idreserva = $idreserva;

        return $this;
    }

    /**
     * @return reserva
     */
    public function getReserva() {
        return $this->reserva;
    }

    /**
     * @param reserva $reserva
     *
     * @return pago
     */
    public function setReserva(reserva $reserva) {
        $this->reserva = $reserva;
        $this->idreserva = $reserva->getId();

        return $this;
    }

    /**
     * @return int
     */
    public function getIdCuenta() {
        return $this->idcuenta;
    }

    /**
     * @param int $idcuenta
     *
     * @return pago
     */
    public function setIdCuenta($idcuenta) {
        $this->idcuenta = $idcuenta;

        return $this;
    }

    /**
     * @return cuenta
     */
    public function getCuenta() {
        return $this->cuenta;
    }

    /**
     * @param cuenta $cuenta
     *
     * @return pago
     */
    public function setCuenta(cuenta $cuenta) {
        $this->cuenta = $cuenta;

        return $this;
    }

    /**
     * @return string
     */
    public function getComprobante() {
        return $this->comprobante;
    }

    /**
     * @param string $comprobante
     *
     * @return pago
     */
    public function setComprobante($comprobante) {
        $this->comprobante = $comprobante;

        return $this;
    }


    /**
     * @return string
     */
    protected function install() {
        return '';
    }

    /**
     * @param $id
     *
     * @return bool|tipo_cliente
     */
    public static function get($id) {
        $tipocli = new self();

        return $tipocli->fetch($id);
    }

    /**
     * @param $id
     *
     * @return bool|tipo_cliente
     */
    public function fetch($id) {
        $tipocli = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . (int)$id . ";");
        if ($tipocli) {
            return new tipo_cliente($tipocli[0]);
        } else {
            return false;
        }
    }

    /**
     * @return bool|tipo_cliente
     */
    public function fetchAll() {
        $pagolist = $this->cache->get_array('m_pago_all');
        if (!$pagolist) {
            $pagos = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY fecha_pago ASC;");
            if ($pagos) {
                foreach ($pagos as $pago) {
                    $pagolist[] = new pago($pago);
                }
            }
            $this->cache->set('m_pago_all', $pagolist);
        }

        return $pagolist;
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

        return $status;
    }

    /**
     * @return bool
     */
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
        $this->cache->delete('m_tipo_cliente_all');
    }

    /**
     * @param $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0) {
        $tipoclientlist = array();
        $query = strtolower($this->no_html($query));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "id LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $consulta .= "lower(nombre) LIKE '%" . $buscar . "%'";
        }
        $consulta .= " ORDER BY nombre ASC";

        $tipoclientes = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($tipoclientes) {
            foreach ($tipoclientes as $c) {
                $tipoclientlist[] = new tipo_cliente($c);
            }
        }

        return $tipoclientlist;
    }

    private function get_cliente($codcliente) {
        $cliente = new cliente();
        return $cliente->get($codcliente);
    }

    private function get_reserva($idreserva) {
        return reserva::get($idreserva);
    }

    private function get_cuenta($idcuenta) {
        $cuenta = new cuenta();
        return $cuenta->get($idcuenta);
    }

}