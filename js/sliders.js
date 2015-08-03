(function (global, $, tc) {
    $(function () {

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
                value: tc.timeWithComma2Minutes(lunch.val()),
                slide: function (event, ui) {
                    var time = ui.value;
                    var wsMinutes = tc.time2Minutes(workStart.val());
                    var weMinutes = tc.time2Minutes(workEnd.val());

                    var workedTime = weMinutes - wsMinutes - ui.value;

                    if (workedTime < 0) {
                        return false;
                    }

                    lunch.val(tc.minutes2TimeWithComma(time));

                    $('.workedHours').text(tc.minutes2TimeWithComma(weMinutes - wsMinutes - ui.value));
                }
            }
        );

        $('#slider-range').slider(
            {
                range: true,
                min: 0,
                max: 1410,
                step: 30,
                values: [tc.time2Minutes(workStart.val()),
                         tc.time2Minutes(workEnd.val())],
                slide: function (event, ui) {
                    var l = tc.timeWithComma2Minutes(lunch.val());
                    var workedTime = ui.values[1] - ui.values[0] - l;

                    if (workedTime < 0) {
                        return false;
                    }

                    workStart.val(tc.minutes2Time(ui.values[0]));
                    workEnd.val(tc.minutes2Time(ui.values[1]));

                    $('.workedHours').text(tc.minutes2TimeWithComma(workedTime));
                }
            }
        );

        $('#slider-time-other').slider(
            {
                min: 0,
                max: 1410,
                step: 30,
                value: tc.timeWithComma2Minutes(otherHours.val()),
                slide: function (event, ui) {
                    otherHours.val(tc.minutes2TimeWithComma(ui.value));
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

        $('#btn-reset-time').click(function () {
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

}) (window, window.jQuery, window.TimeConverter);