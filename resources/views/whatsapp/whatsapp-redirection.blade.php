<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Redirigiendo a WhatsApp...</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<p>Un momento... estamos redirigiéndote a WhatsApp.</p>

<script>
	setTimeout(() => {
		const newWindow = window.open("{{ $url }}", "_blank");

		// Por si el navegador bloquea el popup
		if (!newWindow) {
			window.location.href = "{{ $url }}";
		}

		}, 1500); // 1.5 segundos de delay
</script>
</body>
</html>