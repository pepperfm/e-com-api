<?php

declare(strict_types=1);

use App\Jobs\CancelOldOrdersJob;

Schedule::job(new CancelOldOrdersJob())->everyMinute();
