<!-- resources/views/emails/email-change-verification.blade.php -->
<!DOCTYPE html>
<html>
<head>
	<title>{{ __('emailChange.subject') }}</title>
</head>
<body>
<h1>{{ __('emailChange.subject') }}</h1>
<p>{{ __('emailChange.body', ['currentEmail' => $currentEmail, 'newEmail' => $newEmail]) }}</p>
<a href="{{ env('FRONT_URL', 'http://localhost:5173') . '/email/confirm-change?token=' . $token }}">{{ __('emailChange.button') }}</a>
<p><small>{{ env('FRONT_URL', 'http://localhost:5173') . '/email/confirm-change?token=' . $token }}</small></p>
<p><small>{{ __('emailChange.expires') }}</small></p>
</body>
</html>
