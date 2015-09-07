(function (global, $) {
    "use strict";

    var link = $('#ajaxTestClick');

    link.on('click', function () {
        $.nette.ajax({
            method: 'GET',
            url: this.href,
            dataType: 'json',
            success: function () {
                console.log('aaaaa')
            }
        });
    });

})(window, window.jQuery);