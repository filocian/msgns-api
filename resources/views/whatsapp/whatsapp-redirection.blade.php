<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Ir a WhatsApp</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		html, body {
			height: 100%;
			margin: 0;
			padding: 0;
			font-family: 'Segoe UI', sans-serif;
			background-color: #e5ddd5; /* color de fondo suave tipo WhatsApp */
			display: flex;
			justify-content: center;
			align-items: center;
		}

		.container {
			text-align: center;
		}

		.wa-button {
			background-color: #25D366; /* verde WhatsApp */
			color: white;
			border: none;
			border-radius: 8px;
			padding: 15px 25px;
			font-size: 18px;
			display: inline-flex;
			align-items: center;
			cursor: pointer;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
			transition: background-color 0.3s ease;
		}

		.wa-button:hover {
			background-color: #1ebe5d;
		}

		.wa-icon {
			width: 24px;
			height: 24px;
			margin-right: 10px;
			fill: white;
		}
	</style>
</head>
<body>
<div class="container">
	<button class="wa-button" onclick="openWhatsapp()">
		<svg class="wa-icon" viewBox="0 0 32 32">
			<path
				d="M16.004 3C9.375 3 4 8.375 4 15c0 2.65.94 5.083 2.51 7.01L4 29l7.21-2.45A11.924 11.924 0 0 0 16.004 27C22.625 27 28 21.625 28 15S22.625 3 16.004 3zm0 2c5.522 0 10 4.477 10 10s-4.478 10-10 10a9.97 9.97 0 0 1-5.1-1.376l-.365-.222-4.085 1.387 1.373-3.922-.24-.374A9.957 9.957 0 0 1 6 15c0-5.523 4.478-10 10.004-10zm-2.074 5.52c-.225-.513-.462-.525-.68-.534l-.579-.01c-.198 0-.52.074-.793.372s-1.04 1.014-1.04 2.474 1.064 2.862 1.212 3.06c.149.197 2.054 3.27 5.06 4.46.708.306 1.26.489 1.69.625.71.227 1.355.195 1.867.118.57-.088 1.75-.716 2.002-1.41.252-.694.252-1.29.176-1.41-.075-.118-.275-.197-.579-.344s-1.75-.865-2.023-.963c-.272-.1-.47-.148-.667.147-.197.295-.765.962-.937 1.158-.173.197-.343.222-.637.075-.294-.148-1.238-.454-2.36-1.45-.873-.779-1.462-1.742-1.635-2.037-.173-.295-.018-.455.13-.602.135-.134.297-.347.446-.52.15-.173.2-.296.3-.494.1-.197.05-.37-.025-.519s-.668-1.61-.91-2.194z"/>
		</svg>
		Open WhatsApp
	</button>
</div>
<script>
	function openWhatsapp() {
		window.location.href = "{{ $url }}";
	}
</script>
</body>
</html>