jQuery(document).ready(function($) {
    var meetingStartTime = $(".meetinghub_start_time").data("meeting-start-time");
    var countdownDate = new Date(meetingStartTime).getTime();
    const strings = mhubtran.strings;

    var countdown = setInterval(function() {
        var now = new Date().getTime();
        var distance = countdownDate - now;

        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        $(".meetinghub_start_time").empty(); // Clear existing content

        if (days > 0) {
            var dayLabel = days === 1 ? strings.day : strings.days;
            $(".meetinghub_start_time").append('<span class="mhub-countdown-wrapper"><span class="mhub-countdown-value">' + days + '</span><span class="mhub-countdown-label">' + dayLabel + '</span></span>');
        }

        if (hours > 0 || days > 0) {
            var hourLabel = hours === 1 ? strings.hour : strings.hours;
            $(".meetinghub_start_time").append('<span class="mhub-countdown-wrapper"><span class="mhub-countdown-value">' + hours + '</span><span class="mhub-countdown-label">' + hourLabel + '</span></span>');
        }

        if (minutes > 0 || hours > 0 || days > 0) {
            var minuteLabel = minutes === 1 ? strings.minute : strings.minutes;
            $(".meetinghub_start_time").append('<span class="mhub-countdown-wrapper"><span class="mhub-countdown-value">' + minutes + '</span><span class="mhub-countdown-label">' + minuteLabel + '</span></span>');
        }

        var secondLabel = seconds === 1 ? "Second" : (seconds === 0 ? strings.second : strings.seconds);
        $(".meetinghub_start_time").append('<span class="mhub-countdown-wrapper"><span class="mhub-countdown-value">' + seconds + '</span><span class="mhub-countdown-label">' + secondLabel + '</span></span>');

        if (distance < 0) {
            clearInterval(countdown);
            $(".meetinghub_start_time").html("");
            $(".meetinghub_meeting").show();
            location.reload();
        }
    }, 1000);


    $('.mhub-usertime').each(function () {
        var date = new Date($(this).data('time'));
        $(this).html(date.toString());
      });
});
