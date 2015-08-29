(function ($) {
    "use strict";

    $(function() {
        var checkbox = $(':checkbox');

        checkbox.prop('checked', false);

        checkbox.on('click', function() {
            var otherItemID = $(this).data('other');
            var otherItem = $('#rowID-' + otherItemID);

            if (this.checked) {
                otherItem.find('input[type="checkbox"]').prop('disabled', true);
                otherItem.fadeOut(400);
            } else {
                otherItem.find('input[type="checkbox"]').prop('disabled', false);
                otherItem.fadeIn(400);
            }
        });
    });

})(window.jQuery);