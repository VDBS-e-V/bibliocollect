@props(['as' => 'section'])

<{{ $as }} {{ $attributes->class('bc-card') }}>
    {{ $slot }}
</{{ $as }}>
