@props(['linkComponent', 'tabs', 'adminTab', 'isAdminArea'])

@foreach ($tabs as $key => $label)
    <x-dynamic-component
        :component="$linkComponent"
        :href="route('admin', ['tab' => $key])"
        :active="$isAdminArea && $adminTab === $key"
    >
        {{ $label }}
    </x-dynamic-component>
@endforeach
