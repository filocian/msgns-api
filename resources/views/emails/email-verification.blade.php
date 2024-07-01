<!-- resources/views/emails/email-verification.blade.php -->
<!DOCTYPE html>
<html>
<head>
	<title>{{__('emailVerification.subject')}}</title>
</head>
<body>
<h1>{{__('emailVerification.subject')}}</h1>
<p>{{__('emailVerification.body')}}</p>
<a href="{{'frontURL/' . $verificationToken}}">{{__('emailVerification.button')}}</a>
<p><small>{{'frontURL/' . $verificationToken}}</small></p>
</body>
</html>