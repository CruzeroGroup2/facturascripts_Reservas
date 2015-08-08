/**
 * Created by ggarcia on 05/01/2015.
 */

/**
 *
 * @param str
 * @returns {Date}
 */
function parseDate(str) {
    return new Date(str);
}

/**
 *
 * @param first
 * @param second
 * @returns {number}
 */
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
        cantAdultos = parseInt($('#cantidad_adultos').val()),
        cantMenores = parseInt($('#cantidad_menores').val()),
        cantPasajeros = cantAdultos+cantMenores,
        descuento = parseInt($('#descuento').val());
    $.post(
        'index.php?page=reserva_tarifa_habitacion&action=find',
        {idcategoria: catHab, codgrupo: codgrupo}
    ).done(function(data) {
        monto = parseFloat(data.monto);

        if(cantPasajeros == 1) {
            monto += monto*0.6;
        }

        totalPorDia = monto*cantAdultos

        if(cantMenores > 0) {
            totalPorDia += (cantMenores*monto*0.6);
        }

        total = dias*cantPasajeros*monto;
        montoDescuento = descuento > 0 ? total*(1/descuento) : 0;
        total_final = total - montoDescuento;
        $('#total').val(total);
        $('#total_final').val(total_final);
    });

}

var agregar_pasajero = function(event) {
    var idreservaEle = $('#idreserva'),
        idreserva = idreservaEle.val(),
        idpasajeroEle = $('#id_pasajero'),
        idpasajero = idpasajeroEle.val(),
        nombreEle = $('#nombre_pasajero'),
        nombre = nombreEle.val(),
        tipoDocumentoEle = $('input[name="documento_pasajero_tipo"]:checked'),
        tipoDocumento = tipoDocumentoEle.val(),
        documentoEle = $('#documento_pasajero'),
        documento = documentoEle.val(),
        fechaNacEle = $('#fechanac_pasajero'),
        fechaNac = fechaNacEle.val();
    if(nombre && documento && fechaNac) {
        idpasajeroEle.val('');
        nombreEle.val('');
        documentoEle.val('');
        tipoDocumentoEle.removeAttr('checked');
        fechaNacEle.val('');
        $('#lista_pasajeros tbody').append('<tr>'+
            '<td>' + nombre + '</td>' +
            '<td>' + documento + '</td>' +
            '<td>' + fechaNac + '</td>'+
            '<td>' +
                '<input type="hidden" name="pasajeros[]" value="'+nombre+':'+tipoDocumento+':'+documento+':'+fechaNac+':'+idreserva+':'+idpasajero+'">'+
                '<button class="text-right" onclick="return edit_huesped(this)"><span class="glyphicon glyphicon-pencil"></span></button>' +
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

function edit_huesped(element) {
    var parent = $(element.parentNode),
        huespedInfo = parent.find('input[type="hidden"]').val().split(':'),
        fechaNac;
    remove_huesped(element);
    $('#nombre_pasajero').val(huespedInfo[0]);
    $('input[value="'+huespedInfo[1]+'"]').attr('checked','checked');
    $('#documento_pasajero').val(huespedInfo[2]);
    $('#fechanac_pasajero').val(huespedInfo[3]);
    $('#id_pasajero').val(huespedInfo[5])
    return false;
}

function remove_huesped(element) {
    var parent = $(element.parentNode),
        form = parent.closest('form'),
        huespedInfo = parent.find('input[type="hidden"]').val().split(':');
    if(confirm("Desea eliminar al pasajero "+huespedInfo[0])) {
        console.log(parent);
        console.log(parent.parent());
        form.append('<input type="hidden" name="remover_pasajeros[]" value="'+huespedInfo.join(":")+'" />');
        parent.parent().remove();
    }
    return false;
    //update_huesped_count();
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