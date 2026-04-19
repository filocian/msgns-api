<!-- resources/views/emails/email-verification.blade.php -->
<!DOCTYPE html>
<html>
<head>
	<title>{{__('emailVerification.subject')}}</title>
</head>
<body>
<h1>{{__('emailVerification.subject')}}</h1>
<p>{{__('emailVerification.body')}}</p>
<a href="{{ env('FRONT_URL', 'http://localhost:5173') . '/verify-email/' . $verificationToken }}">{{ __('emailVerification.button') }}</a>
<p><small>{{ env('FRONT_URL', 'http://localhost:5173') . '/verify-email/' . $verificationToken }}</small></p>
</body>
</html>
