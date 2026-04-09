<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Atualiza status de condicionais vencidos todo dia à meia-noite e cinco
Schedule::command('condicionais:atualizar-vencidos')->dailyAt('00:05');
