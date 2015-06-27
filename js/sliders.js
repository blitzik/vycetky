$(function() {

    // Sliders definition

    $('#slider-lunch').slider(
    {
        min: 0,
        max: 300,
        step: 30,
        value: hoursAndMinutes2Minutes($('#lunch').val()),
        slide: function( event, ui ) {
            var time = ui.value;
            var workStart = convertTimeToMinutes($('#workStart').val());
            var workEnd = convertTimeToMinutes($('#workEnd').val());

            var workedTime = workEnd - workStart - ui.value;

            if (workedTime < 0) {
                return false;
            }

            $('#lunch').val(convertMinutes2HoursAndMinutes(time));

            $('.workedHours').text(convertMinutes2HoursAndMinutes(workEnd - workStart - ui.value));
        }
    }
    );

    $('#slider-range').slider(
    {
        range: true,
        min: 0,
        max: 1410,
        step: 30,
        values: [ convertTimeToMinutes($('#workStart').val()),
                  convertTimeToMinutes($('#workEnd').val())],
        slide: function( event, ui ) {
            var lunch = hoursAndMinutes2Minutes($('#lunch').val());
            var workedTime = ui.values[1] - ui.values[0] - lunch;

            if (workedTime < 0) {
                return false;
            }

            $('#workStart').val(convertMinutesToTime(ui.values[0]));
            $('#workEnd').val(convertMinutesToTime(ui.values[1]));

            $('.workedHours').text(convertMinutes2HoursAndMinutes(workedTime));
        }
    }
    );

    $('#slider-time-other').slider(
    {
        min: 0,
        max: 1410,
        step: 30,
        value: hoursAndMinutes2Minutes($('#otherHours').val()),
        slide: function( event, ui ) {
            $('#otherHours').val(convertMinutes2HoursAndMinutes(ui.value));
        }
    }
    );

    // Sliders times set in item edit. Default values or values from DB

    $('#workStart').change(function () {
        $('#slider-range').slider('values', 0, $(this).val());
    });

    $('#workEnd').change(function () {
        $('#slider-range').slider('values', 1, $(this).val());
    });

    $('#otherHours').change(function () {
        $('#slider-time-other').slider('value', $(this).val());
    });

    // Sliders appearance

    $('#lunch').attr('readonly', true);
    $('#workStart').attr('readonly', true);
    $('#workEnd').attr('readonly', true);
    $('#otherHours').attr('readonly', true);

    $('#btn-reset-time').click(function(){
        $('#lunch').val('0');
        $('#workStart').val('0:00');
        $('#workEnd').val('0:00');
        $('.workedHours').text('0');
        $('#otherHours').val('0');

        $('#slider-range').slider('values', 0, 0);
        $('#slider-range').slider('values', 1, 0);
        $('#slider-lunch').slider('value', 0);
        $('#slider-time-other').slider('value', 0);
    });

});