@extends('pdf.layout')

@php
    $tracks = $result['tracks'];
    $leaderboard = $result['leaderboard'];
    $evaluators = $result['evaluators'];
    $ordinal = fn (?int $n) => $n ? $n . (['', 'st', 'nd', 'rd'][$n] ?? 'th') : '-';
    $fmt = fn ($v) => $v === null ? '-' : number_format((float) $v, 2);
    // Track cards per row on the landscape page: two rows whenever possible (7 tracks -> 4 + 3).
    $perRow = max(3, min(5, (int) ceil(count($tracks) / 2)));
    // ResultPdf fits each page separately: $only = 1 or 2 renders just that page.
    $only = $only ?? null;
@endphp

@section('title', 'Overall Results')
@section('document', 'Overall Results - All Tracks')

@section('content')
@if($only !== 2)
<div class="pg-1">
    {{-- ---------- Page 1: leaderboard and the panel's signatures ---------- --}}
    @include('pdf.partials.letterhead', ['subtitle' => 'Overall Results - All Tracks'])

    <table class="meta">
        <tr>
            <td>{{ count($tracks) }} tracks &middot; {{ count($evaluators) }} evaluator{{ count($evaluators) === 1 ? '' : 's' }}</td>
            <td class="right">
                @if($result['all_locked'])
                    <span class="badge">FINAL (ALL TRACKS LOCKED)</span>
                @else
                    <span class="badge badge-muted">PROVISIONAL - SOME TRACKS STILL OPEN</span>
                @endif
            </td>
        </tr>
    </table>

    <p class="section-title" style="margin-top: 0;">Top papers across all tracks</p>
    @if(count($leaderboard) === 0)
        <p class="empty">No submitted evaluations yet.</p>
    @else
        <table class="sheet">
            <thead>
                <tr>
                    <th class="center" style="width: 8%;">Overall</th>
                    <th style="width: 7%;">Track</th>
                    <th style="width: 10%;">Paper No.</th>
                    <th>Title / Researcher</th>
                    <th class="center" style="width: 10%;">Track rank</th>
                    <th class="rank-head" style="width: 10%;">Average</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaderboard as $p)
                    <tr class="{{ $p['overall_rank'] === 1 ? 'top' : ($loop->even ? 'even' : '') }}">
                        <td class="rank">{{ $ordinal($p['overall_rank']) }}</td>
                        <td class="sub">T{{ $p['track_number'] }}</td>
                        <td class="paper-no">{{ $p['paper_no'] }}</td>
                        <td>
                            <p class="title">{{ $p['title'] }}</p>
                            <p class="sub">{{ $p['researcher'] }}</p>
                        </td>
                        <td class="num">{{ $ordinal($p['rank']) }}</td>
                        <td class="avg">{{ $fmt($p['average']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @include('pdf.partials.signatures', ['evaluators' => $evaluators, 'chairs' => []])
</div>
@endif

@if($only !== 1)
    {{-- ---------- Page 2: every track's ranking ---------- --}}
    @if($only === null)<div class="page-break"></div>@endif
<div class="pg-2">

    <table class="page-head">
        <tr>
            <td><span class="lead">Results by Track</span> &middot; {{ $conference['acronym'] }}</td>
            <td class="right">{{ $conference['organizer'] }} &middot; {{ $conference['campus'] }}</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: separate; border-spacing: 0;">
        @foreach(array_chunk($tracks, $perRow) as $row)
            <tr>
                @foreach($row as $entry)
                    @php $track = $entry['track']; $papers = $entry['papers']; @endphp
                    <td class="card-cell{{ $loop->last ? ' last' : '' }}" style="width: {{ 100 / $perRow }}%;">
                        <table class="card">
                            <thead>
                                <tr>
                                    <th class="card-head" colspan="4">
                                        <span class="card-track">Track {{ $track['number'] }}{{ $track['is_locked'] ? ' - Locked' : '' }}</span>
                                        <span class="card-name">{{ $track['name'] }}</span>
                                    </th>
                                </tr>
                            </thead>
                            @if(count($papers) === 0)
                                <tr><td colspan="4" class="center" style="color: #6b7280; padding: 8px;">No papers</td></tr>
                            @else
                                <thead class="cols">
                                    <tr>
                                        <th class="center" style="width: 12%;">Rank</th>
                                        <th>Paper</th>
                                        <th style="width: 26%;">Researcher</th>
                                        <th class="right" style="width: 13%;">Avg</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($papers as $p)
                                        <tr class="{{ $p['rank'] === 1 ? 'top' : '' }}">
                                            <td class="center">{{ $ordinal($p['rank']) }}</td>
                                            <td><span class="paper-no">{{ $p['paper_no'] }}</span> <span style="color: #4b5563;">{{ $p['title'] }}</span></td>
                                            <td style="color: #374151;">{{ $p['researcher'] }}</td>
                                            <td class="right">{{ $fmt($p['average']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endif
                        </table>
                    </td>
                @endforeach
                @for($i = count($row); $i < $perRow; $i++)
                    <td style="width: {{ 100 / $perRow }}%;"></td>
                @endfor
            </tr>
        @endforeach
    </table>

    <p class="note">
        Average is the mean of submitted evaluator totals (out of 100). Track rank uses competition ranking within the
        track; the overall list orders all papers by average regardless of track.
    </p>
</div>
@endif
@endsection
