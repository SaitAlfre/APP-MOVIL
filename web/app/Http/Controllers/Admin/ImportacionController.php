<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Importacion;
use App\Infrastructure\Persistence\Eloquent\PrecioLitro;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\SemanaOperativa;
use App\Infrastructure\Persistence\Eloquent\Zona;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ImportacionController extends Controller
{
    private const TIPOS = ['proveedores', 'rutas', 'precios', 'semanas'];

    public function index(): View
    {
        return view('admin.importaciones.index', ['importaciones' => Importacion::latest()->limit(20)->get()]);
    }

    public function validar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);
        $archivo = $request->file('archivo');
        $filas = $this->leerArchivo($archivo->getRealPath(), strtolower($archivo->getClientOriginalExtension()));
        $errores = $this->validarFilas($datos['tipo'], $filas);
        $token = (string) Str::uuid();
        $ruta = $archivo->storeAs('importaciones', $token.'.'.$archivo->getClientOriginalExtension(), 'local');
        $importacion = Importacion::create([
            'token' => $token, 'tipo' => $datos['tipo'], 'archivo_original' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta, 'estado' => $errores === [] ? 'validada' : 'con_errores',
            'filas_total' => count($filas), 'filas_error' => count($errores),
            'vista_previa' => array_slice($filas, 0, 100), 'errores' => $errores,
            'usuario_id' => auth('operador')->id(),
        ]);

        return redirect()->route('admin.importaciones.preview', $importacion);
    }

    public function preview(Importacion $importacion): View
    {
        abort_unless($importacion->usuario_id === auth('operador')->id() || auth('operador')->user()->tieneRol('admin'), 403);

        return view('admin.importaciones.preview', compact('importacion'));
    }

    public function confirmar(Importacion $importacion): RedirectResponse
    {
        abort_if($importacion->estado !== 'validada', 409, 'La importación no está lista para procesarse.');
        $ruta = Storage::disk('local')->path($importacion->ruta_archivo);
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $filas = $this->leerArchivo($ruta, $extension);
        $errores = $this->validarFilas($importacion->tipo, $filas);
        abort_if($errores !== [], 422, 'El archivo cambió o contiene errores. Vuelve a validarlo.');

        DB::transaction(function () use ($importacion, $filas): void {
            foreach ($filas as $fila) {
                $this->procesarFila($importacion->tipo, $fila);
            }
            $importacion->update(['estado' => 'procesada', 'filas_procesadas' => count($filas), 'procesada_en' => now()]);
        });

        return redirect()->route('admin.importaciones.index')->with('estado', count($filas).' filas importadas correctamente.');
    }

    public function plantilla(string $tipo): StreamedResponse
    {
        abort_unless(in_array($tipo, self::TIPOS, true), 404);
        $cabeceras = $this->cabeceras($tipo);

        return response()->streamDownload(function () use ($cabeceras): void {
            $salida = fopen('php://output', 'wb');
            fputcsv($salida, $cabeceras);
            fclose($salida);
        }, "plantilla-{$tipo}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return list<array<string, string>> */
    private function leerArchivo(string $ruta, string $extension): array
    {
        if ($extension === 'xlsx') {
            return $this->leerXlsx($ruta);
        }
        $contenido = file_get_contents($ruta);
        if ($contenido === false) {
            throw new RuntimeException('No se pudo leer el archivo.');
        }
        $lineas = preg_split('/\r\n|\n|\r/', trim($contenido));
        $separador = str_contains($lineas[0] ?? '', ';') ? ';' : ',';
        $matriz = array_map(fn (string $linea) => str_getcsv($linea, $separador), array_filter($lineas, fn ($linea) => trim($linea) !== ''));

        return $this->normalizarMatriz($matriz);
    }

    /** @return list<array<string, string>> */
    private function leerXlsx(string $ruta): array
    {
        $zip = new ZipArchive;
        throw_unless($zip->open($ruta) === true, RuntimeException::class, 'No se pudo abrir el archivo XLSX.');
        $compartidos = [];
        $xmlCompartidos = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlCompartidos !== false) {
            $xml = simplexml_load_string($xmlCompartidos);
            foreach ($xml->si ?? [] as $item) {
                $compartidos[] = trim((string) ($item->t ?? $item->r->t ?? ''));
            }
        }
        $hoja = simplexml_load_string((string) $zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        $matriz = [];
        foreach ($hoja->sheetData->row ?? [] as $fila) {
            $valores = [];
            foreach ($fila->c as $celda) {
                $referencia = (string) $celda['r'];
                preg_match('/^[A-Z]+/', $referencia, $coincidencia);
                $indiceColumna = $this->indiceColumnaExcel($coincidencia[0] ?? 'A');
                $valor = (string) $celda->v;
                $valores[$indiceColumna] = match ((string) $celda['t']) {
                    's' => $compartidos[(int) $valor] ?? '',
                    'inlineStr' => (string) $celda->is->t,
                    default => $valor,
                };
            }
            if ($valores !== []) {
                $ultimoIndice = max(array_keys($valores));
                $matriz[] = array_map(fn ($indice) => $valores[$indice] ?? '', range(0, $ultimoIndice));
            }
        }

        return $this->normalizarMatriz($matriz);
    }

    /** @param array<int, array<int, string>> $matriz
     * @return list<array<string, string>>
     */
    private function normalizarMatriz(array $matriz): array
    {
        $cabeceras = array_map(fn ($valor) => Str::of(ltrim((string) $valor, "\xEF\xBB\xBF"))->trim()->lower()->ascii()->replace(' ', '_')->toString(), array_shift($matriz) ?? []);

        return array_values(array_map(function (array $fila) use ($cabeceras): array {
            $fila = array_pad($fila, count($cabeceras), '');

            return array_combine($cabeceras, array_map(fn ($valor) => trim((string) $valor), array_slice($fila, 0, count($cabeceras))));
        }, $matriz));
    }

    /** @param list<array<string, string>> $filas
     * @return list<string>
     */
    private function validarFilas(string $tipo, array $filas): array
    {
        if ($filas === []) {
            return ['El archivo no contiene filas de datos.'];
        }
        $faltantes = array_diff($this->cabeceras($tipo), array_keys($filas[0]));
        if ($faltantes !== []) {
            return ['Faltan columnas: '.implode(', ', $faltantes).'.'];
        }
        $errores = [];
        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;
            if (collect($this->cabeceras($tipo))->contains(fn ($columna) => trim($fila[$columna] ?? '') === '')) {
                $errores[] = "Fila {$numeroFila}: contiene campos obligatorios vacíos.";

                continue;
            }
            if ($tipo === 'proveedores' && (! ctype_digit($fila['tachos']) || (int) $fila['tachos'] < 1 || ! is_numeric($fila['capacidad_tacho_l']) || (float) $fila['capacidad_tacho_l'] <= 0)) {
                $errores[] = "Fila {$numeroFila}: tachos y capacidad deben ser números positivos.";
            }
            if ($tipo === 'rutas' && filter_var($fila['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
                $errores[] = "Fila {$numeroFila}: activo debe ser true/false o 1/0.";
            }
            if ($tipo === 'precios' && (! is_numeric($fila['precio']) || (float) $fila['precio'] <= 0)) {
                $errores[] = "Fila {$numeroFila}: el precio debe ser positivo.";
            }
            if (in_array($tipo, ['precios', 'semanas'], true)) {
                try {
                    $inicio = $this->fechaExcel($fila[$tipo === 'precios' ? 'vigente_desde' : 'inicio']);
                    if ($tipo === 'semanas') {
                        $fin = $this->fechaExcel($fila['fin']);
                        if ($fin < $inicio || ! in_array($fila['estado'], ['abierta', 'cerrada'], true)) {
                            $errores[] = "Fila {$numeroFila}: periodo o estado de semana inválido.";
                        }
                    }
                } catch (\Throwable) {
                    $errores[] = "Fila {$numeroFila}: fecha inválida.";
                }
            }
        }

        return array_slice($errores, 0, 100);
    }

    /** @return list<string> */
    private function cabeceras(string $tipo): array
    {
        return match ($tipo) {
            'proveedores' => ['codigo', 'nombres', 'dni', 'telefono', 'direccion', 'zona', 'tachos', 'capacidad_tacho_l'],
            'rutas' => ['codigo', 'nombre', 'zona', 'activo'],
            'precios' => ['vigente_desde', 'precio'],
            'semanas' => ['inicio', 'fin', 'estado'],
        };
    }

    /** @param array<string, string> $fila */
    private function procesarFila(string $tipo, array $fila): void
    {
        if ($tipo === 'proveedores') {
            $zona = Zona::firstOrCreate(['nombre' => $fila['zona']], ['activo' => true]);
            Proveedor::updateOrCreate(['codigo' => $fila['codigo']], [
                'nombres' => $fila['nombres'], 'dni' => $fila['dni'], 'telefono' => $fila['telefono'],
                'direccion' => $fila['direccion'], 'zona_id' => $zona->id, 'tachos' => (int) $fila['tachos'],
                'capacidad_tacho_l' => (float) $fila['capacidad_tacho_l'], 'estado' => 'activo',
                'creado_por_usuario_id' => auth('operador')->id(),
            ]);

            return;
        }
        if ($tipo === 'rutas') {
            $zona = Zona::firstOrCreate(['nombre' => $fila['zona']], ['activo' => true]);
            Ruta::updateOrCreate(['codigo' => $fila['codigo']], ['nombre' => $fila['nombre'], 'zona_id' => $zona->id, 'activo' => filter_var($fila['activo'], FILTER_VALIDATE_BOOLEAN)]);

            return;
        }
        if ($tipo === 'precios') {
            PrecioLitro::updateOrCreate(['vigente_desde' => $this->fechaExcel($fila['vigente_desde'])], ['precio' => (float) $fila['precio']]);

            return;
        }
        SemanaOperativa::updateOrCreate(['inicio' => $this->fechaExcel($fila['inicio'])], ['fin' => $this->fechaExcel($fila['fin']), 'estado' => $fila['estado']]);
    }

    private function indiceColumnaExcel(string $letras): int
    {
        $indice = 0;
        foreach (str_split($letras) as $letra) {
            $indice = ($indice * 26) + ord($letra) - 64;
        }

        return $indice - 1;
    }

    private function fechaExcel(string $valor): string
    {
        if (is_numeric($valor)) {
            return (new DateTimeImmutable('1899-12-30'))->modify('+'.((int) $valor).' days')->format('Y-m-d');
        }

        return (new DateTimeImmutable($valor))->format('Y-m-d');
    }
}
