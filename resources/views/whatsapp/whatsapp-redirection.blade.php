<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Ir a WhatsApp</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<p>Toca el botón para abrir WhatsApp:</p>
<button onclick="abrirWhatsapp()">Abrir WhatsApp</button>

<script>
	function abrirWhatsapp() {
		window.location.href = "{{ $url }}";
	}
</script>
</body>
</html>