<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;

arch('app')
    ->expect('App')
    ->toUseStrictTypes()
    ->ignoring('App\Providers');

arch('enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('queued jobs')
    ->expect('App\Jobs')
    ->toImplement(ShouldQueue::class);

arch('globals')
    ->expect(['dd', 'dump', 'ray', 'env'])
    ->not->toBeUsed();
