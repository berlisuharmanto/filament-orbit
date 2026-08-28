<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Domain management made seamless.');
})->purpose('Display an inspiring quote');
