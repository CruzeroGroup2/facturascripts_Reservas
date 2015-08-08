/**
 * Created by ggarcia on 04/08/2015.
 */
$(document).ready(function() {
    $('#botonBuscar').on('click', function() {
        //var banco= idBancoPorCliente.;
        var fechaIn= $('#fechaDesde').val();
        var fechaOut=$('#fechaHasta').val();
        if(fechaIn === "" || fechaOut === "") {
            alert('Ingrese un rango de Fechas');
            return false;
        } else if(fechaIn > fechaOut) {
            alert('La Fecha Hasta es mayor a la Fecha Desde, por favor modifique las Fechas');
            return false;
        } else {
            url = "gridreserva" + "/" + fechaIn + "/" + fechaOut;
            alert(url);
            $(location).attr('href', url);
        }
    });
});