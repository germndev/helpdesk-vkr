@props(['name', 'class' => ''])

@php
    $iconPath = public_path('assets/icons/'.$name);
    $svg = is_file($iconPath) ? file_get_contents($iconPath) : null;
@endphp

@if($svg)
    <span {{ $attributes->merge(['class' => trim('icon-inline '.$class)]) }} aria-hidden="true">{!! $svg !!}</span>
@endif
