@props(['tableId', 'columns' => [], 'hiddenColumns' => []])
<details class="gestion-columns" data-table-columns="{{ $tableId }}" data-default-hidden="{{ json_encode($hiddenColumns) }}" hidden>
    <summary><span aria-hidden="true">☷</span> Columnas</summary>
    <fieldset><legend data-i18n class="sr-only">Columnas de la tabla</legend><p data-i18n class="gestion-menu-title">Mostrar columnas</p>
        @foreach($columns as $clave=>$etiqueta)
            <label><input type="checkbox" value="{{ $clave }}" data-column-toggle checked><span aria-hidden="true" class="gestion-column-check">✓</span> {{ $etiqueta }}</label>
        @endforeach
        <button data-i18n type="button" data-column-reset>Mostrar todas</button>
    </fieldset>
</details>
