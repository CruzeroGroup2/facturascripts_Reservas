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
    return new Date(tmp2[2],Number(tmp2[1])-1,tmp2[0], 0, 0, 0);
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
 * Return a string representing one of MENOR_7, ADULTO, MENOR_3
 * @param fecha {string}
 * @return {string}
 */
function date_to_option(fecha) {
    if($.inArray(fecha, ['menor_3', 'menor_7', 'adulto']) !== -1) {
        return fecha.toUpperCase();
    }
    var date = parseDate(fecha),
        now = new Date(),
        menor_3 = new Date(),
        menor_7 = new Date();
    menor_3.setFullYear(now.getFullYear()-3);
    menor_7.setFullYear(now.getFullYear()-7);
    if(date >= menor_3) {
        return 'MENOR_3';
    } else if(date >= menor_7) {
        return 'MENOR_7';
    } else {
        return 'ADULTO';
    }
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
        montoDescuento = descuento > 0 ? total * (descuento/100) : 0,
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
        $('#montoDescuento').text(show_precio(totales.montoDescuento));
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
    $('#pasajero_fecha_out').datepicker('setValue', parseDate($('#fecha_in').val()));
    $('#pasajero_fecha_out').datepicker('setValue', parseDate($('#fecha_out').val()));
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
        codclientepax = $('#codcliente_pasajero').val(),
        nombre = ucwords($('#nombre_pasajero').val().toLowerCase()),
        tipoDocumento = $('input[name="documento_pasajero_tipo"]:checked').val(),
        documento = $('#documento_pasajero').val(),
        codgrupo = $('#codgrupo_pasajero').val(),
        edad = $('input[name="edad_pasajero"]:checked').val().toLowerCase(),
        fecha_in = $('#pasajero_fecha_in').val() + ' 12:00:00',
        fecha_out = $('#pasajero_fecha_out').val() + ' 10:00:00',
        cantPasajeros = get_pasajeros_count(),
        pasajeros = $('tr.'+edad+' input[name="pasajeros[]"]');

    if(edad == 'adulto' && pasajeros.length >= Number($('#cantidad_adultos').val())) {
        alert('No puedes agregar más pasajeros adultos a la reserva');
        clear_pasajero_fields();
        return false;
    }
    if(edad == 'menor_7' && pasajeros.length >= Number($('#cantidad_menores').val())) {
        alert('No puedes agregar más pasajeros menorees de 7 a la reserva');
        clear_pasajero_fields();
        return false;
    }

    if(nombre && documento && codgrupo) {
        clear_pasajero_fields();
        $('#lista_pasajeros tbody').append('<tr class="'+edad.toLowerCase()+'">'+
            '<td>' + nombre + '</td>' +
            '<td>' + documento + '</td>' +
            '<td>' + ucfirst(edad) + '</td>' +
            '<td>' + fecha_in + '</td>'+
            '<td>' + fecha_out + '</td>'+
            '<td>' + $('#codgrupo_pasajero option[value='+codgrupo+']').text() + '</td>'+
            '<td>N/A</td>' +
            '<td>' +
                '<input type="hidden" name="pasajeros[]" value="' + [
                nombre,
                tipoDocumento,
                documento,
                edad,
                fecha_in,
                fecha_out,
                codgrupo,
                idreserva,
                idpasajero,
                codclientepax
            ].join("#") + '" />'+
                '<button class="text-right" onclick="return edit_huesped(this)"><span class="glyphicon glyphicon-pencil"></span></button>' +
                '<button class="text-right" onclick="return remove_huesped(this)"><span class="glyphicon glyphicon-trash"></span></button>' +
            '</td>' +
        +'</tr>');
    }
};

/**
 *
 * @param element
 * @returns {boolean}
 */
function edit_huesped(element) {
    var parent = $(element.parentNode),
        huespedInfo = parent.find('input[type="hidden"]').val().split('#');
    clear_pasajero_fields();
    remove_huesped(element, true, true);
    $('#nombre_pasajero').val(huespedInfo[0]);
    $('input[value="'+huespedInfo[1]+'"]').attr('checked','checked');
    $('#documento_pasajero').val(huespedInfo[2]);
    var selector = 'input[value="'+date_to_option(huespedInfo[3])+'"]';
    $(selector).attr('checked','checked');
    $('#pasajero_fecha_in').datepicker('setValue', huespedInfo[4]);
    $('#pasajero_fecha_out').datepicker('setValue', huespedInfo[5]);
    $('#codgrupo_pasajero').find('option[value="'+huespedInfo[6]+'"]').attr("selected",true);
    //IdReserva: huespedInfo[7]
    $('#id_pasajero').val(huespedInfo[8]);
    $('#codcliente_pasajero').val(huespedInfo[9]);
    return false;
}

/**
 *
 * @param element
 * @param force
 * @returns {boolean}
 */
function remove_huesped(element, force, edit) {
    var parent = $(element.parentNode),
        form = parent.closest('form'),
        huespedInfo = parent.find('input[type="hidden"]').val().split('#'),
        force = typeof force !== 'undefined' ? force : confirm("Desea eliminar al pasajero "+huespedInfo[0]);
        edit = typeof edit !== 'undefined' ? edit : false;
    if(force) {
        if(huespedInfo[8] !== 'undefined' && huespedInfo[8] != '' && !edit) {
            form.append('<input type="hidden" name="remover_pasajeros[]" value="'+huespedInfo.join("#")+'" />');
        }
        parent.parent().remove();
    }
    return false;
}

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
    var fecha_in_res = parseDate($('#fecha_in').val());
    var fecha_out_res = parseDate($('#fecha_out').val());
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
    var pax_checkin = $('#pasajero_fecha_in').datepicker({
        format: 'dd-mm-yyyy',
        onRender: function(pdate) {
            var test = pdate.valueOf() < fecha_in_res.valueOf() ||
                       pdate.valueOf() > fecha_out_res.valueOf();
            return test ? 'disabled' : '';
        }
    }).on('changeDate', function(ev) {
        if (ev.date.valueOf() > checkout.date.valueOf()) {
            var newDate = new Date(ev.date);
            newDate.setDate(newDate.getDate() + 1);
            pax_checkout.setValue(newDate);
        }
        pax_checkin.hide();
        $('#pasajero_fecha_out')[0].focus();
    }).data('datepicker');
    var pax_checkout = $('#pasajero_fecha_out').datepicker({
        format: 'dd-mm-yyyy',
        onRender: function(pcdate) {
            var test = pcdate.valueOf() < fecha_in_res.valueOf() ||
                       pcdate.valueOf() <= pax_checkin.date.valueOf() ||
                       pcdate.valueOf() > fecha_out_res.valueOf();
            return test ? 'disabled' : '';
        }
    }).on('changeDate', function(ev) {
        pax_checkout.hide();
    }).data('datepicker');
    $('#idcategoria').on('change', function() {
        $('#idpabellon').attr('disabled',true);
        $.ajax('index.php?page=reserva_pabellon&action=find&by=idcategoria',{
            data: {
                value: $('#idcategoria').val()
            },
            success: function (data) {
                $('#idpabellon')
                        .find('option')
                        .remove()
                        .end()
                        .append('<option value="">Seleccione...</option>');
                $.each(data, function(i, value) {
                    console.log(i);
                    console.log(value);
                    $('#idpabellon').append(
                        $('<option></option>').val(value.id).html(value.descripcion)
                    );
                    $('#idpabellon').attr('disabled',false);
                });
            }
        });
    });
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
    $('#habitaciones, #descuento').on('change', function() {
        calculate_amount();
    });
    $('#fecha_in, #fecha_out').on('blur', function() {
        $('#habitacionesResult ul li span.habitacion a').each(function (i, value) {
            remove_habitacion(value, true);
        });
    });
});