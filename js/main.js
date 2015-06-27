function debounce(fn, delay) {
    var timer = null;
    return function () {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(context, args);
        }, delay);
    };
}

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

    if(hours.length < 10) hours= '0' + hours;
    if(mins.length < 10) mins = '0' + mins;

    if(mins == 0) mins = '00';

    var result = hours+":"+mins;

    return result;
}

function hoursAndMinutes2Minutes(time)
{
    var t = time.split(',');
    var hours = parseInt(t[0]) * 60;
    var minutes = parseInt(t[1]);

    if (minutes == 5){ minutes = 30; }else{ minutes = 0; };

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

function checkAllFunc()
{
    $("#checkAll").change(function () {
        $(".itemToCheck").prop('checked', $(this).prop("checked"));
    });
}

$(function () {
    $.nette.init();
});