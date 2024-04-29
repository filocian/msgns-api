<?php

declare(strict_types=1);

test('Endpoint /sanctum/csrf-cookie returns ok status', function () {
	$response = $this->get('/sanctum/csrf-cookie');

	$response->assertStatus(204);
});
