/**
 * Created by ggarcia on 22/08/2015.
 */

var recalcular = function() {
    var rows = $('#lineas tr'), total = 0;

    rows.each(function(index, element) {
        var precio = $(element).find('input[name="pvp_'+index+'"]');
        if(precio.length) {
            total += parseFloat(precio.val().replace('$',''));
        }
    });

    $('#total').val(total);
}

$(document).ready(function() {
    $('#cancelacion').on('change', recalcular);
});