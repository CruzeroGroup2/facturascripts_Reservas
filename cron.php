<?php

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 19/08/2015
 * Time: 10:09 PM
 */
require_model('reserva.php');
require_model('estado_reserva.php');

class reservas_cron {

    private $db;

    public function __construct(&$db) {
        $this->db = $db;

        $reserva = new reserva();

        $reservas = $reserva->findByEstado(estado_reserva::get(estado_reserva::SINSENA));

    }

}

new reservas_cron($db);