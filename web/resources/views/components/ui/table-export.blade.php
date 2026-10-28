@props(['tableId', 'formId', 'filename'])
<details class="gestion-columns gestion-export" data-export-control="{{ $tableId }}" hidden>
    <summary><x-icon name="download" class="h-4 w-4" /> Exportar</summary>
    <div class="gestion-export-panel">
        <p>Exportar todos los resultados filtrados</p>
        @foreach(['xlsx'=>'Excel (.xlsx)','csv'=>'CSV (.csv)','print'=>'Imprimir / guardar como PDF'] as $tipo=>$etiqueta)
            <button type="button" data-export-table="{{ $tableId }}" data-export-form="{{ $formId }}" data-export-filename="{{ $filename }}" data-export-format="{{ $tipo }}"><x-icon :name="$tipo === 'print' ? 'printer' : 'documentText'" class="h-4 w-4" />{{ $etiqueta }}</button>
        @endforeach
        <span data-export-status role="status" aria-live="polite"></span>
    </div>
</details>
