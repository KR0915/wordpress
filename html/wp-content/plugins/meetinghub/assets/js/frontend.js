window.$ = window.$ || jQuery;

const MHubFrontend = {
    init() {
        this.bindEvents();
        this.changetime();
    },
    bindEvents() {
        $(document).on('click', '.mhub-meeting-status', this.handleMeetingStatusButtonClick);
    },

    changetime() {
        // Get user's timezone using Intl
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        // Handle the special case
        const user_timezone = timezone === "Asia/Katmandu" ? "Asia/Kathmandu" : timezone;
    
        const $meetingWrapper = $(".meetinghub-wrapper");
    
        if ($meetingWrapper.length) {
            // Extract data attributes
            const originalTime = $meetingWrapper.data("mhub-starttime");
            const originalTimezone = $meetingWrapper.data("mhub-timezone");
            const meetingTitle = $meetingWrapper.data("mhub-title");
            const meetingPermalink = $meetingWrapper.data("mhub-permalilk");
            const enableUserZone = $meetingWrapper.data("mhub-enable-user-zone");

            if (enableUserZone) {
                if (!originalTime || !originalTimezone) {
                    console.error("Error: Missing required data attributes.");
                    return;
                }
    
                // Parse original time
                const originalDate = new Date(originalTime);
                if (isNaN(originalDate)) {
                    console.error("Error: Invalid date format! Check the input.");
                    return;
                }
    
                // Format the date in user's timezone
                const dateFormatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: user_timezone,
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });
    
                const timeFormatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: user_timezone,
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });
    
                const dayFormatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: user_timezone,
                    weekday: 'long'
                });
    
                const convertedTime = dateFormatter.format(originalDate);
                const onlyTime = timeFormatter.format(originalDate);
                const dayName = dayFormatter.format(originalDate);
    
                // Update Recurring time
                const timeRecurring = $(".mhub_retime");
                if (timeRecurring.length) {
                    timeRecurring.text(onlyTime);
                }
    
                // Update recurring day
                const dayRecurring = $(".mhub_reday");
                if (dayRecurring.length) {
                    dayRecurring.each(function () {
                        const currentText = $(this).text();
                        const updatedText = currentText.replace(/\b(on\s+\w+)\b/, "on " + dayName);
                        $(this).text(updatedText);
                    });
                }
    
                // Update time & timezone elements
                const $timeElement = $(".mhbu-tm");
                if ($timeElement.length) {
                    $timeElement.text(convertedTime);
                }
    
                const $timezoneElement = $(".mhub-tz");
                if ($timezoneElement.length) {
                    $timezoneElement.text(user_timezone);
                }
    
                // Handle duration and end time
                const $durationElement = $(".mhub-duration");
                const endDate = new Date(originalDate);
    
                let hours = 2; // Default to 2 hours
                let minutes = 0;
    
                if ($durationElement.length) {
                    const durationText = $durationElement.text().trim();
                    const hoursMatch = durationText.match(/(\d+)\s*hour/);
                    const minutesMatch = durationText.match(/(\d+)\s*minute/);
    
                    hours = hoursMatch ? parseInt(hoursMatch[1]) : 0;
                    minutes = minutesMatch ? parseInt(minutesMatch[1]) : 0;
                }
    
                endDate.setHours(endDate.getHours() + hours);
                endDate.setMinutes(endDate.getMinutes() + minutes);
    
                // Format times for Google Calendar URL
                const formatForCalendar = (date) => {
                    return date.toISOString().replace(/-|:|\.\d+/g, "");
                };
    
                const start_time_utc = formatForCalendar(originalDate);
                const end_time_utc = formatForCalendar(endDate);
    
                // Update Google Calendar button
                const $calendarButton = $(".mhub-add-to-calendar-btn");
                if ($calendarButton.length) {
                    const calendar_url = `https://www.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(meetingTitle)}&dates=${start_time_utc}/${end_time_utc}&details=${encodeURIComponent('Join the meeting: ' + meetingPermalink)}&ctz=${encodeURIComponent(user_timezone)}`;
                    $calendarButton.attr("href", calendar_url);
                }
            }
        }
    },
    handleMeetingStatusButtonClick() {
        const meetingId = $(this).data('meeting-id');
        const postId = $(this).data('post-id');
        const meetingStatus = $(this).data('meeting-status');
        MHubFrontend.sendMeetingStatusData(meetingId, postId, meetingStatus);
    },
    sendMeetingStatusData(meetingId, postId, meetingStatus) {
        if (meetingStatus === 'end') {
            // Display a confirmation alert
            if (confirm( mhubMeetingsData.strings.are_you_sure_you_want_to_end )) {
                // If user confirms, proceed with AJAX request
                sendAjaxRequest();
            }
        } else {
            // If meeting status is not 'end', proceed with AJAX request directly
            sendAjaxRequest();
        }

        if (meetingStatus === 'start') {
            // Display a confirmation alert
            if (confirm( mhubMeetingsData.strings.are_you_sure_you_want_to_start )) {
                // If user confirms, proceed with AJAX request
                sendAjaxRequest();
            }
        } else {
            // If meeting status is not 'end', proceed with AJAX request directly
            sendAjaxRequest();
        }

        function sendAjaxRequest() {
            $.ajax({
                url: mhub_frontend_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mhub_meeting_action',
                    nonce: mhub_frontend_params.nonce,
                    meeting_id: meetingId,
                    post_id: postId,
                    meeting_status: meetingStatus
                },
                success: function (response) {
                    // Reload the page upon successful response
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    },
   
};

$(document).ready(function () {
    MHubFrontend.init();
});
