/**
 * Created by ggarcia on 05/01/2015.
 */

/**
 *
 * @param str
 * @returns {string}
 */
function ucfirst(str) {
    //  discuss at: http://phpjs.org/functions/ucfirst/
    // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // bugfixed by: Onno Marsman
    // improved by: Brett Zamir (http://brett-zamir.me)
    //   example 1: ucfirst('kevin van zonneveld');
    //   returns 1: 'Kevin van zonneveld'

    str += '';
    var f = str.charAt(0)
        .toUpperCase();
    return f + str.substr(1);
}

/**
 *
 * @param str
 * @returns {string}
 */
function ucwords(str) {
    //  discuss at: http://phpjs.org/functions/ucwords/
    // original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // improved by: Waldo Malqui Silva
    // improved by: Robin
    // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // bugfixed by: Onno Marsman
    //    input by: James (http://www.james-bell.co.uk/)
    //   example 1: ucwords('kevin van  zonneveld');
    //   returns 1: 'Kevin Van  Zonneveld'
    //   example 2: ucwords('HELLO WORLD');
    //   returns 2: 'HELLO WORLD'

    return (str + '')
        .replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function($1) {
            return $1.toUpperCase();
        });
}

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
 * @param first {Date}
 * @param second {Date}
 * @returns {number}
 */
function daydiff(first, second) {
    first.setHours(0, 0, 0);
    second.setHours(0, 0, 0);
    return Math.ceil((second-first)/(1000*60*60*24));
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
 * @param dias
 * @param descuento
 * @param cantAdultos
 * @param cantMenores
 * @returns {{total: (number|*), montoDescuento: (number|*), total_final: (number|*)}}
 */
function calculate_totals(tarifa, dias, descuento, cantAdultos, cantMenores) {
    var monto = parseFloat(tarifa.monto),
        cantAdultos = Number(cantAdultos),
        cantMenores = Number(cantMenores),
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

/**
 *
 */
function calculate_amount() {
    var fechaIn = parseDate($('#fecha_in').val()),
        fechaOut = parseDate($('#fecha_out').val()),
        dias = (fechaOut > fechaIn) ? daydiff(fechaIn, fechaOut) : 1,
        tarifa = $('#idtarifa').val(),
        cantAdultos = Number($('#cantidad_adultos').val()),
        cantMenores = Number($('#cantidad_menores').val()),
        descuento = Number($('#descuento').val());
    get_tarifa({idtarifa: tarifa}, function(data) {
        var totales = calculate_totals(data, dias, descuento, cantAdultos, cantMenores);
        $('#monto_tarifa').text(show_precio(data.monto));
        $('#total').text(show_precio(totales.total));
        $('#total_final').text(show_precio(totales.total_final));
    });
}

/**
 *
 * @returns {number}
 */
function get_pasajeros_count() {
    var cantAdultos = Number($('#cantidad_adultos').val()),
    cantMenores = Number($('#cantidad_menores').val());
    return cantAdultos+cantMenores;
}

/**
 *
 */
function clear_pasajero_fields() {
    $('#id_pasajero').val('');
    $('#nombre_pasajero').val('');
    $('#documento_pasajero').val('');
    $('input[name="documento_pasajero_tipo"]:checked').removeAttr('checked');
    $('#codgrupo_pasajero option:selected').removeAttr('selected');
    $('input[name="edad_pasajero"]:checked').removeAttr('checked');
}

/**
 *
 * @param event
 * @returns {boolean}
 */
var agregar_pasajero = function(event) {
    var idreserva = $('#idreserva').val(),
        idpasajero = $('#id_pasajero').val(),
        nombre = ucwords($('#nombre_pasajero').val().toLowerCase()),
        tipoDocumento = $('input[name="documento_pasajero_tipo"]:checked').val(),
        documento = $('#documento_pasajero').val(),
        codgrupo = $('#codgrupo_pasajero').val(),
        edad = $('input[name="edad_pasajero"]:checked').val().toLowerCase(),
        cantPasajeros = get_pasajeros_count(),
        pasajeros = $('tr.'+edad+' input[name="pasajeros[]"]');

    if(edad == 'adulto' && pasajeros.length >= Number($('#cantidad_adultos').val())) {
        alert('No puedes agregar más pasajeros adultos a la reserva');
        clear_pasajero_fields();
        return false;
    }
    if(edad == 'menor_6' && pasajeros.length >= Number($('#cantidad_menores').val())) {
        alert('No puedes agregar más pasajeros menorees de 6 a la reserva');
        clear_pasajero_fields();
        return false;
    }

    if(nombre && documento && codgrupo) {
        clear_pasajero_fields();
        $('#lista_pasajeros tbody').append('<tr class="'+edad.toLowerCase()+'">'+
            '<td>' + nombre + '</td>' +
            '<td>' + documento + '</td>' +
            '<td>' + ucfirst(edad) + '</td>' +
            '<td>' + $('#codgrupo_pasajero option[value='+codgrupo+']').text() + '</td>'+
            '<td>' +
                '<input type="hidden" name="pasajeros[]" value="'+nombre+':'+tipoDocumento+':'+documento+':'+edad+':'+codgrupo+':'+idreserva+':'+idpasajero+':">'+
                '<button class="text-right" onclick="return edit_huesped(this)"><span class="glyphicon glyphicon-pencil"></span></button>' +
                '<button class="text-right" onclick="return remove_huesped(this)"><span class="glyphicon glyphicon-trash"></span></button>' +
            '</td>' +
        +'</tr>');
    }
};

/**
 *
 * @param suggestion
 * @returns {boolean}
 */
var autcomplete_select = function(suggestion) {
    var client_id = suggestion.data;

    if(client_id != 0) {
        $.ajax('index.php?page=nueva_venta&datoscliente='+client_id, {
            success: function(data) {
                $('#codcliente').val(client_id)
                                .data(data);
                $('#codgrupo').val(data.codgrupo);
            },
            complete: function() {
                $('#cliente_loading').addClass('hidden');
            }
        });
    } else {
        $('#myModal').modal('show');
    }

    return false;
};

/**
 *
 * @param query
 * @param suggestions
 * @returns {boolean}
 */
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

/**
 *
 * @param element
 * @returns {boolean}
 */
function edit_huesped(element) {
    var parent = $(element.parentNode),
        huespedInfo = parent.find('input[type="hidden"]').val().split(':');
    remove_huesped(element, true);
    $('#nombre_pasajero').val(huespedInfo[0]);
    $('input[value="'+huespedInfo[1]+'"]').attr('checked','checked');
    $('#documento_pasajero').val(huespedInfo[2]);
    $('input[value="'+huespedInfo[3]+'"]').attr('checked','checked');
    $('#codgrupo_pasajero').find('option[value="'+huespedInfo[4]+'"]').attr("selected",true);
    $('#id_pasajero').val(huespedInfo[5]);
    return false;
}

/**
 *
 * @param element
 * @param force
 * @returns {boolean}
 */
function remove_huesped(element, force) {
    var parent = $(element.parentNode),
        form = parent.closest('form'),
        huespedInfo = parent.find('input[type="hidden"]').val().split(':'),
        force = typeof force !== 'undefined' ? force : confirm("Desea eliminar al pasajero "+huespedInfo[0]);
    if(force) {
        form.append('<input type="hidden" name="remover_pasajeros[]" value="'+huespedInfo.join(":")+'" />');
        parent.parent().remove();
    }
    return false;
}

/**
 *
 * @param habitacion
 */
function add_habitacion(habitacion) {
    var habPart = habitacion.value.split(":");
    if(confirm("Desea a gregar la habitacion "+habPart[0]+"?")) {
        $('#idsHabitaciones').val(function(i, val) {
            var ids = [];
            if(val != '') {
                ids = val.split(',');
            }
            ids.push(habitacion.data);
            return ids.join(',');
        });
        $('#habitacionesResult ul li').append(
            '<span style="color:black;" class="habitacion disponible" data-id="'+habitacion.data+'">' +
                 habitacion.value +
                '<a href="#" class="text-right" onclick="return remove_habitacion(this)"><span class="glyphicon glyphicon-remove"></span></a>' +
            '</span>'
        );
        $("#numeroHab").removeData('suggestion').val('');
        update_capacidad();
    }
}

/**
 *
 * @param element
 * @param force
 * @returns {boolean}
 */
function remove_habitacion(element, force) {
    var parent = $(element.parentNode),
        form = parent.closest('form'),
        idHabitacion = parent.data('id');
        force = typeof force !== 'undefined' ? force : confirm("Desea eliminar la habitacion?");

    $('#idsHabitaciones').val(function(i, val) {
        var ids = val.split(',');
        $.each(ids, function(i, value) {
            var habPart = value.split(':');
            if (habPart[0].indexOf(idHabitacion.toString()) > -1 && force) {
                ids.splice(i, 1);
                form.append('<input type="hidden" name="remover_habitaciones[]" value="'+value+'" />');
                parent.remove();
                update_capacidad();
            }
        });
        return ids.join(',');
    });
    return false;
}

/**
 *
 */
function update_capacidad() {
    var capacidad = $('#capacidad_habitaciones'),
        tmp = 0;
    $('#habitacionesResult ul li span.habitacion').each(function (i, value) {
        var habParts = $(value).text().split(':');
        tmp = Number(tmp) + Number(habParts[1]);
    });
    capacidad.text(tmp);

}

$(document).ready(function() {
    var nowTemp = new Date();
    var now = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0);
    var checkin = $('#fecha_in').datepicker({
        format: 'dd-mm-yyyy',
        onRender: function(date) {
            return date.valueOf() < now.valueOf() ? 'disabled' : '';
        }
    }).on('changeDate', function(ev) {
        if (ev.date.valueOf() > checkout.date.valueOf()) {
            var newDate = new Date(ev.date);
            newDate.setDate(newDate.getDate() + 1);
            checkout.setValue(newDate);
        }
        checkin.hide();
        $('#fecha_out')[0].focus();
    }).data('datepicker');
    var checkout = $('#fecha_out').datepicker({
        format: 'dd-mm-yyyy',
        onRender: function(date) {
            return date.valueOf() <= checkin.date.valueOf() ? 'disabled' : '';
        }
    }).on('changeDate', function(ev) {
        checkout.hide();
    }).data('datepicker');
    $('#idpabellon').on('change', function() {
        $("#numeroHab").val('')
                       .autocomplete({
            serviceUrl: 'index.php?page=reserva_habitacion&action=find',
            type: 'POST',
            params: {
                fecha_in: $('#fecha_in').val(),
                fecha_out: $('#fecha_out').val(),
                idpabellon: $('#idpabellon').val()
            },
            noCache: true,
            showNoSuggestionNotice: true,
            noSuggestionNotice: "Habitacion no disponible",
            onSelect: function(suggestion) {
                $("#numeroHab").data('suggestion',suggestion);
                $('#agregarHabitacion').prop('disabled', false);
            },
            transformResult: function (response) {
                var ret = typeof response === 'string' ? $.parseJSON(response) : response;
                var suggestions = ret.suggestions;
                var tmpSugg = [];
                $.each(suggestions, function (index, value){
                    var selec = '.habitacion.disponible[data-id='+value.data+']';
                    if($(selec).length != 1) {
                        tmpSugg.push(value)
                    }
                });
                ret.suggestions = tmpSugg;
                return ret;
            }
        });
    });
    $('#agregarHabitacion').on('click', function() {
        $('#agregarHabitacion').prop('disabled', true);
        add_habitacion($("#numeroHab").data('suggestion'));
    });
    $('#agregar_pasajero').on('click', agregar_pasajero);
    update_capacidad();
    $('#habitaciones').on('change', function() {
        calculate_amount();
    });
    $('#fecha_in, #fecha_out').on('blur', function() {
        $('#habitacionesResult ul li span.habitacion a').each(function (i, value) {
            remove_habitacion(value, true);
        });
    });
});