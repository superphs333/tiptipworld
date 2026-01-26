@props(['alt' => 'TipTipWorld logo'])

<img
    src="{{ asset('images/logo-tip.png') }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => '']) }}
/>
