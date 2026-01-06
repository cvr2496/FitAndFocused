<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->actingAs($user = User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});