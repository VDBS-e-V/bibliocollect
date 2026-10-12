@props(['title' => 'Kontakt', 'name' => null, 'description' => null, 'email' => null, 'phone' => null, 'website' => null, 'addressLines' => [], 'density' => 'comfortable'])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp
<address {{ $attributes->class('bc-contact-card bc-contact-card--'.$density) }}>
    <strong class="bc-contact-card__title">{{ $title }}</strong>
    @if (filled($name)) <span class="bc-contact-card__name">{{ $name }}</span> @endif
    @if (filled($description)) <span class="bc-contact-card__description">{{ $description }}</span> @endif
    @if (count($addressLines)) <span class="bc-contact-card__address">@foreach ($addressLines as $line)<span>{{ $line }}</span>@endforeach</span> @endif
    @if (filled($email) || filled($phone) || filled($website))
        <ul class="bc-contact-card__links">
            @if (filled($email)) <li><span>E-Mail</span><a href="mailto:{{ $email }}">{{ $email }}</a></li> @endif
            @if (filled($phone)) <li><span>Telefon</span><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></li> @endif
            @if (filled($website)) <li><span>Website</span><a href="{{ $website }}">{{ $website }}</a></li> @endif
        </ul>
    @endif
    @isset($actions)<div class="bc-contact-card__actions">{{ $actions }}</div>@endisset
</address>
