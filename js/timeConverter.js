/**
 * Created by aleš tichava on 3.8.2015.
 */

(function (global) {

    var TimeConverter = {
        'timeRegExp': /^-?\d+:[0-5][0-9]$/,
        'hoursAndMinutesRegExp': /^\d+(,[05])?$/,

        'isInt': function (x) {
            var y = parseInt(x, 10);
            return !isNaN(y) && x == y && x.toString() == y.toString();
        },

        /**
         * @param {string} time
         * @returns {number} number of minutes
         */
        'time2Minutes': function (time) {
            if (!this.timeRegExp.test(time)) {
                throw 'Wrong format of argument "time".';
            }

            var timeParts = time.split(':');
            var hours = parseInt(timeParts[0]);
            var minutes = parseInt(timeParts[1]);

            return hours * 60 + minutes;
        },

        /**
         * @param {number} minutes
         * @returns {string} Time in format (-)HH:MM
         */
        'minutes2Time': function (minutes) {
            if (!this.isInt(minutes)) {
                throw 'Argument "minutes" must be integer number!';
            }

            var isNegative = minutes < 0;

            minutes = isNegative ? (minutes * (-1)) : minutes;
            var hours = Math.floor(minutes / 60);
            var minutes = minutes - (hours * 60);

            if (minutes < 10) {
                minutes = '0' + minutes;
            }

            return (isNegative ? '-' : '') + hours + ':' + minutes;
        },

        /**
         * @param {string} hoursAndMinutes Time in format HH,MM (eg. 1,5| 1| but not 1,0)
         * @returns {number} number of minutes
         */
        'timeWithComma2Minutes': function (hoursAndMinutes) {
            if (!this.hoursAndMinutesRegExp.test(hoursAndMinutes)) {
                throw 'Wrong format of argument "hoursAndMinutes"';
            } else {
                if (this.isInt(hoursAndMinutes)) {
                    hoursAndMinutes = hoursAndMinutes.toString();
                }
            }

            var timeParts = hoursAndMinutes.split(',');
            var hours = parseInt(timeParts[0]) * 60;
            var minutes = parseInt(timeParts[1]);

            minutes = ((minutes == 5) ? 30 : 0);

            return (hours + minutes);
        },

        /**
         *
         * @param {number} minutes
         * @returns {string} Method returns time in format HH,MM where minutes are stepped by 30 mins
         */
        'minutes2TimeWithComma': function (minutes) {
            if (!this.isInt(minutes) || minutes < 0) {
                throw 'Argument "minutes" must be integer number!';
            }

            var minutes = parseInt(minutes);

            if (minutes % 30 !== 0) {
                throw 'Argument "minutes" must be divisible by 30 without reminder!';
            }

            var t = this.minutes2Time(minutes);
            var timeParts = t.split(':');

            var time, m;
            if (timeParts[1] == 30) {
                m = ',5';
            } else if (timeParts[1] == 0) {
                m = '';
            }

            return timeParts[0] + m;
        }
    };

    global.TimeConverter = global.tc = TimeConverter;

})(window);