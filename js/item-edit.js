(function ($) {
    "use strict";

    $(function () {
        var workedHours = $('#workedHours');
        workedHours.text(workedHours.data('hours'));

        var locality = $('#locality');
        locality.autocomplete({
            source: locality.data('localityAutoSignal'),
            delay: 500,
            minLength: 3
        });
    });

})(window.jQuery);