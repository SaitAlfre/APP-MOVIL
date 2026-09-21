<?php

use App\Infrastructure\Persistence\Eloquent\Comunicado;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Comunicado::where('estado', 'programado')
        ->where('publicar_en', '<=', now())
        ->update(['estado' => 'publicado', 'publicado_en' => now()]);
})->everyMinute()->name('publicar-comunicados')->withoutOverlapping();
