<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect('/');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect('/');

    Notification::assertNothingSent();
});