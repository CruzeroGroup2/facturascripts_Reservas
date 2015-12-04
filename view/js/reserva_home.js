/**
 * Created by ggarcia on 04/08/2015.
 */

/**
 *
 * @param date
 * @param days
 * @returns {Date}
 */
function addDays(date, days) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

$(document).ready(function() {
    var fecha_desde = $('#fecha_desde').datepicker({
        format: 'dd-mm-yyyy'
    }).on('changeDate', function(ev) {
        fecha_hasta.setValue(addDays(ev.date, 15));
        fecha_desde.hide();
        fecha_hasta.show();
    }).data('datepicker');
    var fecha_hasta = $('#fecha_hasta').datepicker({
        format: 'dd-mm-yyyy',
        onRender: function (date) {
            return (date.valueOf() <= fecha_desde.date.valueOf() || date.valueOf() >= addDays(fecha_desde.date, 15).valueOf()) ? 'disabled' : '';
        }
    }).on('changeDate', function(ev) {
        fecha_hasta.hide();
    }).data('datepicker');
    $('#botonBuscar').on('click', function() {
        //var banco= idBancoPorCliente.;
        var fechaIn = $('#fecha_desde').val();
        var fechaOut = $('#fecha_desde').val();
        if(fechaIn === "" || fechaOut === "") {
            alert('Ingrese un rango de Fechas');
            return false;
        } else if(fechaIn > fechaOut) {
            alert('La Fecha Desde es mayor a la Fecha Hasta, por favor modifique las Fechas');
            return false;
        } else {
            url = "gridreserva" + "/" + fechaIn + "/" + fechaOut;
            alert(url);
            $(location).attr('href', url);
        }
    });
});