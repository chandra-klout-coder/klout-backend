@component('mail::message')
# Reset Password
Dear User,

Thank you for your request to change your password.Please click on button to enter your new password using the following link:

@component('mail::button', ['url' => $resetLink])
Reset Password
@endcomponent

If you have not requested to change password, then ignore this mail.

Kind Regards,<br>
{{ config('app.name') }}
@endcomponent
