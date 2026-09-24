@props(['active'])

@php
    $classes = 'nav-link inline-flex items-center px-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none';
    $style = ($active ?? false) ? 'color: var(--brand-1);' : '';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'style' => $style]) }}>
    {{ $slot }}
</a>
