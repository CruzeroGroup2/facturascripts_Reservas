<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 15/07/2015
 * Time: 09:13 PM
 */


require_once 'base/fs_model.php';


class cliente_por_reserva extends fs_model {

    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var int
     */
    protected $idhuesped = null;

    /**
     * @var int
     */
    protected $idreserva = null;

    public function __construct($data = array()) {
        parent::__construct('cliente_por_reserva','plugins/reservas/');
        $this->setId($data);
        $this->idhuesped = (isset($data['idhuesped'])) ? $data['idhuesped'] : null;
        $this->idreserva = (isset($data['idreserva'])) ? $data['idreserva'] : null;
    }

    protected function install() {
    }

    public function get($filter = array()) {
    }

    public function exists() {
    }

    public function save() {
    }

    public function delete() {
    }


}