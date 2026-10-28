@props(['tableId', 'columns' => [], 'hiddenColumns' => []])
<details class="gestion-columns" data-table-columns="{{ $tableId }}" data-default-hidden="{{ json_encode($hiddenColumns) }}" hidden>
    <summary><span aria-hidden="true">☷</span> Columnas</summary>
    <fieldset><legend class="sr-only">Columnas de la tabla</legend><p class="gestion-menu-title">Mostrar columnas</p>
        @foreach($columns as $clave=>$etiqueta)
            <label><input type="checkbox" value="{{ $clave }}" data-column-toggle checked><span aria-hidden="true" class="gestion-column-check">✓</span> {{ $etiqueta }}</label>
        @endforeach
        <button type="button" data-column-reset>Mostrar todas</button>
    </fieldset>
</details>
