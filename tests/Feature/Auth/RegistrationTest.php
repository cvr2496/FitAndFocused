<?php

test('registration screen can be rendered', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/');
});