<?php
/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 05/01/2015
 * Time: 11:11 PM
 */

require_once 'base/fs_model.php';

class huesped extends fs_model {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $apellido;
    /**
     * @var string
     */
    protected $nombre;

    /**
     * @var string
     */
    protected $fecha_nac;

    /**
     * @var string
     */
    protected $tipo_documento;

    /**
     * @var string
     */
    protected $documento;

    /**
     * @var string
     */
    protected $domicilio;

    /**
     * @var string
     */
    protected $telefono_fijo;

    /**
     * @var string
     */
    protected $telefono_celular;

    /**
     * @var int
     */
    protected $idbanco_por_cliente;

    /**
     * @var int
     */
    protected $titular;

    /**
     * @var string
     */
    protected $mail;

    /**
     * @var int
     */
    protected $idalimentoespecial;

    /**
     * @var int
     */
    protected $idtarjetaporcliente;

    /**
     * @var int
     */
    protected $idpadronafiliado;

    /**
     * @var habitacion
     */
    protected $habitacion;

    const EDAD_MENOR = 5;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     * @return huesped
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getNombre() {
        return $this->nombre;
    }

    /**
     * @param string $nombreHuesped
     * @return huesped
     */
    public function setNombre($nombreHuesped) {
        $this->nombre = $nombreHuesped;
        return $this;
    }

    /**
     * @return string
     */
    public function getFechaNac() {
        return $this->fecha_nac;
    }

    /**
     * @param string $fechaNacHuesped
     * @return huesped
     */
    public function setFechaNac($fechaNacHuesped) {
        $this->fecha_nac = $fechaNacHuesped;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentoHuesped() {
        return $this->documento;
    }

    /**
     * @param string $documentoHuesped
     * @return huesped
     */
    public function setDocumentoHuesped($documentoHuesped) {
        $this->documento = $documentoHuesped;
        return $this;
    }

    /**
     * @return bool
     */
    public function esAdulto() {
        return $this->getEdad() >= self::EDAD_MENOR;
    }

    /**
     * @return bool
     */
    public function esMenor() {
        return $this->getEdad() < self::EDAD_MENOR;
    }

    /**
     * @return int
     */
    public function getEdad() {
        return date_diff(date_create($this->getFechaNac()), date_create('today'))->y;
    }

    function __construct($data = array()) {
        parent::__construct('huesped','plugins/reservas/');
        $this->setId($data);
        $this->apellido = (isset($data['apellido'])) ? $data['apellido'] : null;
        $this->nombre = (isset($data['nombre'])) ? $data['nombre'] : null;
        $this->fecha_nac = (isset($data['fecha_nac'])) ? $data['fecha_nac'] : null;
        $this->tipo_documento = (isset($data['tipo_documento'])) ? $data['tipo_documento'] : null;
        $this->documento = (isset($data['documento'])) ? $data['documento'] : null;
        $this->domicilio = (isset($data['domicilio'])) ? $data['domicilio'] : null;
        $this->telefono_fijo = (isset($data['telefono_fijo'])) ? $data['telefono_fijo'] : null;
        $this->telefono_celular = (isset($data['telefono_celular'])) ? $data['telefono_celular'] : null;
        $this->idbanco_por_cliente = (isset($data['idbanco_por_cliente'])) ? $data['idbanco_por_cliente'] : null;
        $this->titular = (isset($data['titular'])) ? $data['titular'] : null;
        $this->mail = (isset($data['mail'])) ? $data['mail'] : null;
        $this->idalimentoespecial = (isset($data['idalimentoespecial'])) ? $data['idalimentoespecial'] : null;
        $this->idtarjetaporcliente = (isset($data['idtarjetaporcliente'])) ? $data['idtarjetaporcliente'] : null;
        $this->idpadronafiliado = (isset($data['idpadronafiliado'])) ? $data['idpadronafiliado'] : null;
    }

    protected function install() {
        // TODO: Implement install() method.
    }

    public function get($filter = array()) {

    }
    /**
     * Esta función devuelve TRUE si los datos del objeto se encuentran
     * en la base de datos.
     */
    public function exists() {
        // TODO: Implement exists() method.
    }

    /**
     * Esta función sirve tanto para insertar como para actualizar
     * los datos del objeto en la base de datos.
     */
    public function save() {
        // TODO: Implement save() method.
    }

    /**
     * Esta función sirve para eliminar los datos del objeto de la base de datos
     */
    public function delete() {
        // TODO: Implement delete() method.
    }


}