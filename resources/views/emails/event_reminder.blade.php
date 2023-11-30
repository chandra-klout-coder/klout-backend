<!DOCTYPE html>
<html>
<head>
    <title>Event Reminder</title>
</head>
<body>
    <h1>Event Reminder: {{ $event_details['event_details']['title'] }}</h1>
    <p>Hello {{$event_details['attendee_details']['first_name']}},</p>
    <p>This is a reminder for the upcoming event on : {{ $event_details['event_details']['event_date'] }}</p>
    <!-- Add any other event details or instructions here -->
</body>
</html>