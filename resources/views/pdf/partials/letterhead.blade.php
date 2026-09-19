{{-- Letterhead: the three seals, organizer, conference name and theme, optional track band, venue and sheet title. --}}
<div class="letterhead">
    <div class="seals">
        @if(!empty($logos['university']))<img class="seal-side" src="{{ $logos['university'] }}" alt="">@endif
        @if(!empty($logos['conference']))<img class="seal-main" src="{{ $logos['conference'] }}" alt="">@endif
        @if(!empty($logos['city']))<img class="seal-side" src="{{ $logos['city'] }}" alt="">@endif
    </div>
    <p class="organizer">{{ $conference['organizer'] }} &middot; {{ $conference['campus'] }}</p>
    <p class="conf-name">{{ $conference['name'] }}</p>
    <p class="conf-theme">{{ $conference['theme'] }} &middot; {{ $conference['dates'] }} &middot; {{ $conference['venue'] }}</p>
    @if(!empty($trackLabel))
        {{-- A table is the one reliable way to get a shrink-to-fit, centered pill out of DomPDF. --}}
        <table class="track-band-wrap" align="center"><tr><td class="track-band">{{ $trackLabel }}</td></tr></table>
    @endif
    @if(!empty($venue))
        <p class="venue">Venue: {{ $venue }}</p>
    @endif
    @if(!empty($subtitle))
        <p class="sheet-title">{{ $subtitle }}</p>
    @endif
</div>
