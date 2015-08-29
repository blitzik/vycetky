(function (global, $) {
    "use strict";

    $(function () {
        var SearchInput = $('#search');
        var strlen = SearchInput.val().length * 2;
        SearchInput.focus();
        SearchInput[0].setSelectionRange(strlen, strlen);

        global.checkAllFunc();
    });

})(window, window.jQuery);