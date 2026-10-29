@props(['name' => 'estado', 'options' => [], 'value' => null, 'formId', 'label' => 'Filtrar por estado'])
<nav aria-label="{{ $label }}" class="gestion-status-tabs">
    @foreach($options as $clave=>$etiqueta)
        @php $parametros = array_diff_key(request()->query(), ['page'=>true, $name=>true]); if ((string)$clave !== '') $parametros[$name] = $clave; @endphp
        <a href="{{ url()->current().($parametros ? '?'.http_build_query($parametros) : '') }}" data-status-tab data-status-name="{{ $name }}" data-status-value="{{ $clave }}" data-status-form="{{ $formId }}" class="gestion-status-tab" @if((string)$value === (string)$clave) aria-current="page" @endif>{{ $etiqueta }}</a>
    @endforeach
</nav>
