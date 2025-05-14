<?php

it('returns a successful response', function () {
    $response = $this->get('/api/telegram/bot/' . rand(0, 1000) . '/webhook');

    $response->assertStatus(200);
});

it('Returns 403 for unauthorized requests', function () {
    $response = $this->post('/api/telegram/bot/' . rand(0, 1000) . '/webhook');

    $response->assertStatus(403);
});
