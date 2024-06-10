<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Content</title>
</head>

<body>
    <p>Dear {{ ucfirst($attendee->first_name) }},</p>

    <p>{!! $messageContent !!}</p>

</body>

</html>