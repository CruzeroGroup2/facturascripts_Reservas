<?php

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 19/08/2015
 * Time: 07:22 AM
 */
class reserva_controller extends fs_controller {

    protected function log($msg = FALSE, $tipo = 'error', $alerta = FALSE) {
        $fslog = new fs_log();
        $fslog->tipo = $tipo;
        $fslog->detalle = $msg;
        $fslog->ip = $_SERVER['REMOTE_ADDR'];
        $fslog->alerta = $alerta;

        if($this->user) {
            $fslog->usuario = $this->user->nick;
        }

        $fslog->save();
    }

}