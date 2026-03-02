@props(['linkComponent', 'items' => []])

@foreach ($items as $item)
    <x-dynamic-component
        :component="$linkComponent"
        :href="$item['href']"
        :active="$item['active']"
    >
        {{ $item['label'] }}
    </x-dynamic-component>
@endforeach
