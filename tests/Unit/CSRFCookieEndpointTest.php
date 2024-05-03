<?php

declare(strict_types=1);

use Illuminate\Http\Response;

test('Endpoint /sanctum/csrf-cookie returns ok status', function () {
	$response = $this->get('/sanctum/csrf-cookie');

	$response->assertStatus(Response::HTTP_NO_CONTENT);
});
