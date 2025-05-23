<?php

use App\Models\User;

test('process login', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $response->assertOk();

    $this->assertAuthenticatedAs($user);
});
