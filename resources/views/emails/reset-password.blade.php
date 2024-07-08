<!-- resources/views/emails/email-verification.blade.php -->
<!DOCTYPE html>
<html>
<head>
	<title>{{__('passwordReset.subject')}}</title>
</head>
<body>
<h1>{{__('passwordReset.subject')}}</h1>
<p>{{__('passwordReset.body')}}</p>
<a href="{{env('FRONT_URL', 'http://localhost:3000') . '/password-reset/' . $verificationToken}}">{{__('passwordReset.button')}}</a>
<p><small>{{env('FRONT_URL', 'http://localhost:3000') . '/password-reset/' . $verificationToken}}</small></p>
</body>
</html>