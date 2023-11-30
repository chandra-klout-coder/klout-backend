<!DOCTYPE html>
<html>

<head>
    <title>Mail Subject</title>
</head>

<body>

    <p>
        Dear {{ ucfirst($data['attendee']['first_name']) }},<br />

        We hope this email finds you well and full of excitement for the upcoming event.
        We are thrilled to extend our heartfelt gratitude for registering to attend {{ $data['event']['title'] }},
        hosted by Klout Club. Your participation means a lot to us, and we are looking forward to a fantastic event.
    </p>

    <div>
        <img src="{{ asset($data['event']['image']) }}" width="800"/>
    </div>

    <h5>Event Details : </h5>
    <p>
        Date: {{ $data['event']['event_date'] }}
        Time: {{ $data['event']['start_time'].' : '.$data['event']['start_minute_time'].' '.$data['event']['start_time_format'] }} to {{ $data['event']['end_time'].' : '.$data['event']['end_minute_time'].' '.$data['event']['end_time_format'] }}
        Location: {{ ucfirst($data['event']['event_venue_name']).', '.$data['event']['event_venue_address_1'].', '.$data['event']['city'].', '.$data['event']['state'].', '.$data['event']['country'].' - '.$data['event']['pincode'] }}
    </p>

    <p>
        We have carefully curated an engaging program with insightful sessions and exciting activities to ensure a memorable experience for all attendees.
        Whether you're a seasoned enthusiast or just starting in this field, we have something special in store for each of you.
    </p>


    <p>
        As a reminder, the event is scheduled to take place on <b>{{ $data['event']['event_date'] }}</b>.
        To help you keep track of your plans, we will send you a friendly reminder via email one day prior to the event.
    </p>

    <p>
        If you have any questions or require further assistance regarding the event,
        please do not hesitate to reach out to us. You can contact our friendly support team at:
    </p>

    <p>
        <b>Email : </b> Klout@club.com
    </p>

    <p>
        <b> Phone :</b> +91-99457899900
    </p>

    <p>
        Once again, thank you for joining us for <b>{{ $data['event']['title'] }} </b>.
        We are confident that this event will be a remarkable and enriching experience for all participants.
    </p>

    <p>
        Looking forward to meeting you there!
    </p>

    <p><b>Best regards,</b></p>
    <p>Klout Club</p>

</body>

</html>