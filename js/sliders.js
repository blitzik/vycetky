$(function() {

    function convertTimeToMinutes(time)
    {
        var x = time.split(':');
        var h = parseInt(x[0]);
        var m = parseInt(x[1]);

        var minutes = h * 60 + m;

        return minutes;
    }

    function convertMinutesToTime(minutes)
    {
        var hours = Math.floor(minutes / 60);
        var mins = minutes - (hours * 60);

        if(hours.length < 10) hours = '0' + hours;
        if(mins.length < 10) mins = '0' + mins;

        if(mins == 0) mins = '00';

        var result = hours + ":" + mins;

        return result;
    }

    function hoursAndMinutes2Minutes(time)
    {
        var t = time.split(',');
        var hours = parseInt(t[0]) * 60;
        var minutes = parseInt(t[1]);

        minutes = ((minutes == 5) ? 30 : 0);

        return (hours + minutes);
    }

    function convertMinutes2HoursAndMinutes(minutes)
    {
        var hours = Math.floor(minutes / 60);
        var mins = minutes % 60;

        if (mins == 30) {
            hours = hours + ',5';
        }

        return hours;
    }

    // -------------------------------

    var workStart = $('#workStart');
    var workEnd = $('#workEnd');
    var lunch = $('#lunch');
    var otherHours = $('#otherHours');

    // Sliders definition

    $('#slider-lunch').slider(
    {
        min: 0,
        max: 300,
        step: 30,
        value: hoursAndMinutes2Minutes(lunch.val()),
        slide: function( event, ui ) {
            var time = ui.value;
            var wsMinutes = convertTimeToMinutes(workStart.val());
            var weMinutes = convertTimeToMinutes(workEnd.val());

            var workedTime = weMinutes - wsMinutes - ui.value;

            if (workedTime < 0) {
                return false;
            }

            lunch.val(convertMinutes2HoursAndMinutes(time));

            $('.workedHours').text(convertMinutes2HoursAndMinutes(weMinutes - wsMinutes - ui.value));
        }
    }
    );

    $('#slider-range').slider(
    {
        range: true,
        min: 0,
        max: 1410,
        step: 30,
        values: [ convertTimeToMinutes(workStart.val()),
                  convertTimeToMinutes(workEnd.val())],
        slide: function( event, ui ) {
            var l = hoursAndMinutes2Minutes(lunch.val());
            var workedTime = ui.values[1] - ui.values[0] - l;

            if (workedTime < 0) {
                return false;
            }

            workStart.val(convertMinutesToTime(ui.values[0]));
            workEnd.val(convertMinutesToTime(ui.values[1]));

            $('.workedHours').text(convertMinutes2HoursAndMinutes(workedTime));
        }
    }
    );

    $('#slider-time-other').slider(
    {
        min: 0,
        max: 1410,
        step: 30,
        value: hoursAndMinutes2Minutes(otherHours.val()),
        slide: function( event, ui ) {
            otherHours.val(convertMinutes2HoursAndMinutes(ui.value));
        }
    }
    );

    // Sliders times set in item edit. Default values or values from DB

    workStart.change(function () {
        $('#slider-range').slider('values', 0, $(this).val());
    });

    workEnd.change(function () {
        $('#slider-range').slider('values', 1, $(this).val());
    });

    otherHours.change(function () {
        $('#slider-time-other').slider('value', $(this).val());
    });

    // Sliders appearance

    lunch.attr('readonly', true);
    workStart.attr('readonly', true);
    workEnd.attr('readonly', true);
    otherHours.attr('readonly', true);

    $('#btn-reset-time').click(function(){
        lunch.val('0');
        workStart.val('0:00');
        workEnd.val('0:00');
        $('.workedHours').text('0');
        otherHours.val('0');

        $('#slider-range').slider('values', 0, 0);
        $('#slider-range').slider('values', 1, 0);
        $('#slider-lunch').slider('value', 0);
        $('#slider-time-other').slider('value', 0);
    });

});