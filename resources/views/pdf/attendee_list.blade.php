<!DOCTYPE html>
<html>

<head>
    <title>Attendee List</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Attendee List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Phone Number</th>
                <th>Email</th>
                <th>Company</th>
                <th>Industry</th>
                <th>Job Title</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendeeData as $attendee)
            <tr>
                <td>{{ $attendee['id'] }}</td>
                <td>{{ $attendee['event'] }}</td>
                <td>{{ $attendee['first_name'] }}</td>
                <td>{{ $attendee['last_name'] }}</td>
                <td>{{ $attendee['phone_number'] }}</td>
                <td>{{ $attendee['email'] }}</td>
                <td>{{ $attendee['company'] }}</td>
                <td>{{ $attendee['industry'] }}</td>
                <td>{{ $attendee['job_title'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>