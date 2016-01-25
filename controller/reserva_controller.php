<?php

/**
 * Created by IntelliJ IDEA.
 * User: ggarcia
 * Date: 19/08/2015
 * Time: 07:22 AM
 */
class reserva_controller extends fs_controller {

    const DATE_FORMAT = 'd-m-Y';
    const DATE_FORMAT_FULL = 'd-m-Y H:i:s';
    protected $fecha = null;

    /**
     * @return string
     */
    public function getFecha($full_date = false) {
        if(!$this->fecha) {
            $this->fecha = new DateTime();
        } elseif(is_string($this->fecha)) {
            $this->fecha = new DateTime($this->fecha);
        }

        $format = self::DATE_FORMAT;
        if($full_date) {
            $format = self::DATE_FORMAT_FULL;
        }

        return $this->fecha->format($format);
    }



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