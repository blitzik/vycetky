(function (global, $) {
    "use strict";

    $(function () {

        function inputAutoFocus(input) {
            var strlen = input.value.length * 2;
            input.focus();
            input.setSelectionRange(strlen, strlen);
        }

        /*function linkAjaxHandler(event) {
            event.preventDefault();

            console.log(this.href);

            var settings = {
                method: 'get',
                url: this.href,
                dataType: 'json',
                complete: function () {
                    autoFocus(SearchInput);
                    $('.myAjax').on('click', linkAjaxHandler);
                    $('.submitButton').on('click', clickHandler);
                }
            };

            $.nette.ajax(settings);
        }*/

        function formAjaxHandler(e)
        {
            e.preventDefault();

            var form = this.form; // "this" is Submit button
            var csrfTokenInput = form.querySelector('input[name=_token_]');
            var doSignalInput = form.querySelector('input[name=do]');
            var currentSearch = form.querySelector('#search');

            var settings = {
                method: form.method,
                url: form.action,
                dataType: 'json',
                data: {
                    do: doSignalInput.value,
                    search: currentSearch.value,
                    _token_: csrfTokenInput.value
                },
                complete: function (request) {
                    var searchInput = global.document.querySelector('#search');
                    inputAutoFocus(searchInput);

                    // we do not want to send Ajax request if value of the current Search Input
                    // is same as value of the old one
                    searchInput.dataset.oldSearchValue = searchInput.value;
                    if (this.name == 'hide') {
                        searchInput.dataset.oldSearchValue = currentSearch.value;
                    }

                    $('.submitButton').on('click', formAjaxHandler);
                }
            };
            settings['data'][this.name] = this.value;

            if (this.name == 'hide') {
                settings['data']['lcls'] = [];

                var items = form.querySelectorAll('.itemToCheck');
                var count = items.length;
                var wasCheckAtleastOne = false;
                for (var i = 0; i < count; i++) {
                    if (items[i].checked === true) {
                        settings['data']['lcls'].push(items[i].value);
                        wasCheckAtleastOne = true;
                    }
                }

                if (wasCheckAtleastOne === true) {
                    console.log(form.action); // todo - tady v action zustava stale ten search parametr
                    $.nette.ajax(settings);
                }
            } else {
                if (currentSearch.value != currentSearch.dataset.oldSearchValue) {
                    $.nette.ajax(settings);
                }
            }
        }
        // todo - nejprve vyhledat, potom se vratit na hlavni stranku tak, ze zadam do search pole
        // prazdny string, potom oznacim jedno pracoviste a dam ho smazat -> zobrazi se
        // predtim zobrazeny seznam -> ERROR - takhle se to nema chovat
        inputAutoFocus(global.document.querySelector('#search'));
        $('.submitButton').on('click', formAjaxHandler);

    });

})(window, window.jQuery);