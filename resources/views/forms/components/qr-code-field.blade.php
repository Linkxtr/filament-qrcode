@php
    $field = $field ?? null;
    $fieldWrapperView = null;
    try {
        if ($field && method_exists($field, 'getCustomFieldWrapperView') && filled($field->getCustomFieldWrapperView())) {
            $fieldWrapperView = $field->getCustomFieldWrapperView();
        } elseif ($field && method_exists($field, 'hasContainer') && $field->hasContainer()) {
            $fieldWrapperView = is_callable($getFieldWrapperView ?? null)
                ? $getFieldWrapperView()
                : $field->getFieldWrapperView();
        }
    } catch (\Throwable) {
        $fieldWrapperView = null;
    }
@endphp

@if ($fieldWrapperView && $field)
    <x-dynamic-component
        :component="$fieldWrapperView"
        :field="$field"
    >
        @include('filament-qr::forms.components.qr-code-field-card')
    </x-dynamic-component>
@else
    @include('filament-qr::forms.components.qr-code-field-card')
@endif
