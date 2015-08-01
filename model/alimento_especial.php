<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 15/07/2015
 * Time: 09:13 PM
 */

require_once 'base/fs_model.php';


class alimento_especial extends fs_model {

    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $descripcion = null;

    /**
     * @var int
     */
    protected $idhuesped = null;

    /**
     * @var huesped
     */
    protected $huesped_cliente = null;

    public function __construct($data) {
        parent::__construct('alimento_especial','plugins/reservas/');
        $this->setId($data);
        $this->descripcion = (isset($data['descripcion'])) ? $data['descripcion'] : null;
        $this->idhuesped = (isset($data['idhuesped'])) ? $data['idhuesped'] : null;
        $this->huesped_cliente = $this->get_huesped($this->idhuesped);
    }

    /**
     * Esta función es llamada al crear una tabla.
     * Permite insertar valores en la tabla.
     */
    protected function install() {
    }

    public function get($filter = array()) {

    }
    /**
     * Esta función devuelve TRUE si los datos del objeto se encuentran
     * en la base de datos.
     */
    public function exists() {
    }

    /**
     * Esta función sirve tanto para insertar como para actualizar
     * los datos del objeto en la base de datos.
     */
    public function save() {
    }

    /**
     * Esta función sirve para eliminar los datos del objeto de la base de datos
     */
    public function delete() {
    }


}