<?php

use App\Application\Proveedores\ConciliarCuentasProveedoresUseCase;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('proveedores:conciliar-cuentas {--vincular : Vincula solo las coincidencias fiables por DNI} {--actor= : ID del administrador que autoriza la vinculación}', function (ConciliarCuentasProveedoresUseCase $conciliar): int {
    $informe = $conciliar->revisar();
    $this->info("Fichas ya vinculadas: {$informe['vinculados']}");
    $this->line('Fichas sin cuenta: '.count($informe['fichas_sin_cuenta']).' · Cuentas de proveedor sin ficha: '.count($informe['cuentas_sin_ficha']));
    if ($informe['fiables'] !== []) {
        $this->table(['Ficha', 'Cuenta', 'DNI'], array_map(fn ($p) => [$p['codigo'], $p['username'], $p['dni']], $informe['fiables']));
    }
    $this->line('Coincidencias fiables por DNI: '.count($informe['fiables']));
    if ($informe['conflictos'] !== []) {
        $this->warn('Conflictos que requieren revisión manual (no se vinculan):');
        $this->table(['DNI', 'Situación'], array_map(fn ($c) => [$c['dni'], $c['situacion']], $informe['conflictos']));
    }
    if (! $this->option('vincular')) {
        $this->comment('Modo revisión: no se modificó ningún registro. Usa --vincular --actor=ID para aplicar las coincidencias fiables.');

        return 0;
    }
    $actor = Usuario::query()->find($this->option('actor'));
    if ($actor === null || $actor->perfil_rol_id !== null || ! $actor->tieneRol('admin')) {
        $this->error('Indica con --actor el ID de un administrador activo que autoriza la vinculación.');

        return 1;
    }
    if ($informe['fiables'] === [] || ! $this->confirm('¿Vincular '.count($informe['fiables']).' fichas con sus cuentas? Quedará registrado en auditoría.')) {
        $this->comment('No se realizaron vinculaciones.');

        return 0;
    }
    // La auditoría operativa registra el cambio a nombre del administrador que autoriza.
    Auth::guard('operador')->setUser($actor);
    $this->info('Vinculaciones realizadas: '.$conciliar->vincularFiables());

    return 0;
})->purpose('Revisa fichas y cuentas de proveedor y vincula de forma supervisada las coincidencias fiables por DNI');

Schedule::call(function (): void {
    Comunicado::where('estado', 'programado')
        ->where('publicar_en', '<=', now())
        ->update(['estado' => 'publicado', 'publicado_en' => now()]);
})->everyMinute()->name('publicar-comunicados')->withoutOverlapping();
