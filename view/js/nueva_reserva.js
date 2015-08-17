/**
 * Created by ggarcia on 05/01/2015.
 */

/**
 *
 * @param str
 * @returns {Date}
 */
function parseDate(str) {
    var tmp = str.split(' ');
    var tmp2 = tmp[0].split('-');
    return new Date(tmp2[2],tmp2[1],tmp2[0]);
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

/**
 *
 * @param options "{idtarifa: int}" | "{idcategoria: int, codgrupo: str}"
 * @param done_func
 * @returns {*}
 */
function get_tarifa(options, done_func) {
    return $.post(
        'index.php?page=reserva_tarifa_habitacion&action=find',
        options
    ).done(done_func);
}

/**
 *
 * @param tarifa
 * @param descuento
 * @param cantAdultos
 * @param cantMenores
 * @returns {{total: (number|*), montoDescuento: (number|*), total_final: (number|*)}}
 */
function calculate_totals(tarifa, dias, descuento, cantAdultos, cantMenores) {
    var monto = parseFloat(tarifa.monto),
        cantAdultos = parseInt(cantAdultos),
        cantMenores = parseInt(cantMenores),
        cantPasajeros = cantAdultos + cantMenores;

    if (cantPasajeros == 1) {
        monto += monto * 0.6;
    }

    var totalPorDia = monto * cantAdultos;

    if (cantMenores > 0) {
        totalPorDia += (cantMenores * monto * 0.6);
    }

    var total = dias * totalPorDia,
        montoDescuento = descuento > 0 ? total * (1 / descuento) : 0,
        total_final = total - montoDescuento;
    return {
        "total": total,
        "montoDescuento": montoDescuento,
        "total_final": total_final
    };
}


function calculate_amount() {
    var fechaIn = parseDate($('#fecha_in').val()),
        fechaOut = parseDate($('#fecha_out').val()),
        dias = daydiff(fechaIn, fechaOut),
        tarifa = $('#idtarifa').val(),
        cantAdultos = parseInt($('#cantidad_adultos').val()),
        cantMenores = parseInt($('#cantidad_menores').val()),
        descuento = parseInt($('#descuento').val());
    get_tarifa({idtarifa: tarifa}, function(data) {
        var totales = calculate_totals(data, dias, descuento, cantAdultos, cantMenores);
        $('#total').val(totales.total);
        $('#total_final').val(totales.total_final);
    });
}

function clear_pasajero_fields() {
    $('#id_pasajero').val('');
    $('#nombre_pasajero').val('');
    $('#documento_pasajero').val('');
    $('input[name="documento_pasajero_tipo"]:checked').removeAttr('checked');
    $('#fechanac_pasajero').val('');
}
var agregar_pasajero = function(event) {
    var idreserva = $('#idreserva').val(),
        idpasajero = $('#id_pasajero').val(),
        nombre = $('#nombre_pasajero').val(),
        tipoDocumento = $('input[name="documento_pasajero_tipo"]:checked').val(),
        documento = $('#documento_pasajero').val(),
        fechaNac = $('#fechanac_pasajero').val(),
        cantAdultos = parseInt($('#cantidad_adultos').val()),
        cantMenores = parseInt($('#cantidad_menores').val()),
        cantPasajeros = cantAdultos+cantMenores;

    if($('input[name="pasajeros[]"').length >= cantPasajeros) {
        alert('No puedes agregar más pasajeros a la reserva');
        clear_pasajero_fields();
        return false;
    }

    if(nombre && documento && fechaNac) {
        clear_pasajero_fields();
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
                $('#codgrupo').val(data.codgrupo);
            },
            complete: function() {
                $('#cliente_loading').addClass('hidden');
            }
        });
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
            value: 'Agregar Cliente',
            data: 0
        });
    }
    return false;
};

var find_habitacion = function() {
    var habitaciones = $('#habitaciones');
    if(habitaciones.length === 0) {
        return ;
    }

    var baseUrl = 'index.php?page=reserva_habitacion&action=find';
    var formValues = {};
    $.each(habitaciones.serializeArray(), function(i, field) {
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
        huespedInfo = parent.find('input[type="hidden"]').val().split(':');
    remove_huesped(element);
    $('#nombre_pasajero').val(huespedInfo[0]);
    $('input[value="'+huespedInfo[1]+'"]').attr('checked','checked');
    $('#documento_pasajero').val(huespedInfo[2]);
    $('#fechanac_pasajero').val(huespedInfo[3]);
    $('#id_pasajero').val(huespedInfo[5]);
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
    $('#pasajeros').on('change', function() {
        //calculate_amount();
    });
    find_habitacion();
});