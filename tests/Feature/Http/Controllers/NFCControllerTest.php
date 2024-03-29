<?php

test('NFCController', function () {
    $response = $this->get('/nfc/hello');
    $response->assertStatus(200);
});
