<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'base/fs_model.php';

/**
 * El cliente. Puede tener una o varias direcciones y subcuentas asociadas.
 */
class tipo_cliente extends fs_model {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $nombre;

    /**
     * @param array $data
     */
    public function __construct($data = array()) {
        parent::__construct('tipo_cliente', 'plugins/reservas/');

        $this->setValues($data);
    }

    /**
     * @param array $data
     */
    public function setValues($data = array()) {
        $this->setId($data);
        $this->nombre = (isset($data['nombre'])) ? $data['nombre'] : null;
    }

    /**
     * @param int|array $id
     *
     * @return tipo_cliente
     */
    public function setId($id) {
        // This is an ugly thing use an Hydrator insted
        if (is_int($id)) {
            $this->id = $id;
        }

        if (is_array($id)) {
            if (isset($id['idtipocliente'])) {
                $this->id = $id['idtipocliente'];
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
     * @param string $nombre
     *
     * @return tipo_cliente
     */
    public function setNombre($nombre = '') {
        $this->nombre = $nombre;
        return $this;
    }

    /**
     * @return string
     */
    public function getNombre() {
        return $this->nombre;
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
        $tipoclientlist = $this->cache->get_array('m_tipo_cliente_all');
        if (!$tipoclientlist) {
            $tipoclientes = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY nombre ASC;");
            if ($tipoclientes) {
                foreach ($tipoclientes as $c) {
                    $tipoclientlist[] = new tipo_cliente($c);
                }
            }
            $this->cache->set('m_tipo_cliente_all', $tipoclientlist);
        }

        return $tipoclientlist;
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
        $this->nombre = $this->no_html($this->nombre);

        if (strlen($this->nombre) < 1 OR strlen($this->nombre) > 255) {
            $this->new_error_msg("Nombre de cliente no válido.");
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

}
