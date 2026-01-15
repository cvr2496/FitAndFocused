<?php

it('returns a successful response', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect(route('login'));
});
