@props(['active'])

@php
    $base = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out';
    $text = ($active ?? false) ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700';
    $style = ($active ?? false) ? 'border-bottom-color: var(--brand-1);' : '';
    $classes = trim($base.' '.$text.' focus:outline-none');
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
    {{ $slot }}
</a>
