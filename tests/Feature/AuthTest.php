<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\postJson;

test('process login', function () {
    $user = User::factory()->create();

    $response = postJson(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $response->assertOk();
    $response->assertJsonStructure(['token', 'user' => []]);
});
