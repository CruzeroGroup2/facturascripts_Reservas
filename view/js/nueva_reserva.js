/**
 * Created by ggarcia on 05/01/2015.
 */

function parseDate(str) {
    var date = str.split('-')
    return new Date(date[0], date[1], date[2]);
}

function daydiff(first, second) {
    return (second-first)/(1000*60*60*24);
}


function calculate_amount() {
    var fechaIn = parseDate($('#fecha_in').val()),
        fechaOut = parseDate($('#fecha_out').val()),
        dias = daydiff(fechaIn, fechaOut),
        catHab = $('#idcategoriahab').val(),
        codgrupo = $('#codgrupo').val(),
        cantHab = $('#idsHabitaciones').val().split(',').length,
        cantPasajeros = parseInt($('#cantidad_adultos').val()) + parseInt($('#cantidad_menores').val()),
        descuento = parseInt($('#descuento').val());
    $.post(
        'index.php?page=reserva_tarifa_habitacion&action=find',
        {idcategoria: catHab, codgrupo: codgrupo}
    ).done(function(data) {
        total = dias*cantPasajeros*parseFloat(data.monto);
        total_final = total - (total*(1/descuento));
        $('#total').val(total);
        $('#total_final').val(total_final);
    });

}

var agregar_pasajero = function(event) {
    var idreservaEle = $('#idreserva'),
        idreserva = idreservaEle.val(),
        nombreEle = $('#nombre_pasajero'),
        nombre = nombreEle.val(),
        tipoDocumentoEle = $('input[name="documento_pasajero_tipo"]:checked'),
        tipoDocumento = tipoDocumentoEle.val(),
        documentoEle = $('#documento_pasajero'),
        documento = documentoEle.val(),
        fechaNacEle = $('#fechanac_pasajero'),
        fechaNac = fechaNacEle.val();
    if(nombre && documento && fechaNac) {
        nombreEle.val('');
        documentoEle.val('');
        tipoDocumentoEle.removeAttr('checked');
        fechaNacEle.val('');
        $('#lista_pasajeros tbody').append('<tr>'+
            '<td>' + nombre + '</td>' +
            '<td>' + documento + '</td>' +
            '<td>' + fechaNac + '</td>'+
            '<td>' +
                '<input type="hidden" name="pasajeros[]" value="'+nombre+':'+tipoDocumento+':'+documento+':'+fechaNac+':'+idreserva+'">'+
                '<button class="text-right" onclick="return remove_huesped(this)"><span class="glyphicon glyphicon-trash"></span></button>' +
            '</td>' +
        +'</tr>');
    }
}


var autcomplete_select = function(suggestion) {
    var client_id = suggestion.data;

    if(client_id != 0) {
        $.ajax('index.php?page=nueva_venta&datoscliente='+client_id, {
            success: function(data) {
                $('#codcliente').data(data);
            },
            complete: function() {
                $('#cliente_loading').addClass('hidden');
            }
        })
        $('#codcliente').val(client_id);
    } else {
        $('#myModal').modal('show');
    }

    return false;
};

var autocomplete_response = function(query, suggestions) {
    // suggestions is the array that's about to be sent to the response callback.
    if (suggestions.length === 0) {
        suggestions.push({
            label: 'Agregar Cliente',
            value: 0
        });
    }
    return false;
};

var find_habitacion = function() {
    var baseUrl = 'index.php?page=reserva_habitacion&action=find';
    var formValues = {};
    $.each($('#habitaciones').serializeArray(), function(i, field) {
        formValues[field.name] = field.value;
    });
    $('#habitacionesResult').html('<span id="loading" class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>');
    $.post(
        baseUrl,
        formValues
    ).done(function (html) {
            $('#habitacionesResult').html(html);
            calculate_amount();
    });
};

var select_habitacion = function(event) {
    var clickedElementId = event.target.id;
    if(clickedElementId.indexOf('solucion') != -1) {
        var clickedElement = $('#'+clickedElementId);
        var catHabs = clickedElement.data('categorias').toString();
        var idsHabs = clickedElement.data('habitaciones').toString();
        $('.habitacionItem').removeClass('active');
        clickedElement.addClass('active');
        $('#idsHabitaciones').val(idsHabs);
        $('#idcategoriahab').val(catHabs);
        calculate_amount();
    }

};

function update_huesped_count() {
    var cantAdultos = 0;
    var cantMenores = 0;
    $('#huespedesFieldset > fieldset').each(function(index, element) {
        var fechaNac = $(this).find('.fechaNac').val();
        if(fechaNac.trim()) {
            var edad = _calculateAge(new Date(fechaNac));
            if(edad < 5) {
                cantMenores++;
            } else {
                cantAdultos++;
            }
        }
    })
    $("input[name='reserva[cantidadAdulto]']").val(cantAdultos);
    $("input[name='reserva[cantidadMenor]']").val(cantMenores);
}

function remove_huesped(element) {
    $(element.parentNode.parentNode).remove();
    update_huesped_count();
}
$(document).ready(function() {
    $('#habitacionesResult').on('click', select_habitacion);
    $('#agregar_pasajero').on('click', agregar_pasajero);
    $('#habitaciones').on('change', function() {
        find_habitacion();
        calculate_amount();
    });
    find_habitacion();
});