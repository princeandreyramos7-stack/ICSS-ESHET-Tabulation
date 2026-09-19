@extends('pdf.layout')

@php
    $track = $result['track'];
    $evaluators = $result['evaluators'];
    $papers = $result['papers'];
    $topAverage = collect($papers)->firstWhere('rank', 1)['average'] ?? null;
    $ordinal = fn (int $n) => $n . (['', 'st', 'nd', 'rd'][$n] ?? 'th');
    $fmt = fn ($v) => $v === null ? '-' : number_format((float) $v, 2);
@endphp

@section('title', 'Track ' . $track['number'] . ' Results')
@section('document', 'Track ' . $track['number'] . ' Summary of Evaluation Scores')

@section('content')
<div class="pg-1">
    @include('pdf.partials.letterhead', [
        'trackLabel' => 'Track ' . $track['number'] . ': ' . $track['name'],
        'venue' => $track['venue'] ?? null,
        'subtitle' => 'Summary of Evaluation Scores',
    ])

    <table class="meta">
        <tr>
            <td>{{ count($papers) }} paper{{ count($papers) === 1 ? '' : 's' }} - {{ count($evaluators) }} evaluator{{ count($evaluators) === 1 ? '' : 's' }}</td>
            <td class="right">
                @if($track['is_locked'])
                    <span class="badge">FINAL (LOCKED)</span>
                @else
                    <span class="badge badge-muted">PROVISIONAL - TRACK STILL OPEN</span>
                @endif
            </td>
        </tr>
    </table>

    @if(count($evaluators) === 0)
        <p class="empty">No evaluators are assigned to this track yet.</p>
    @elseif(count($papers) === 0)
        <p class="empty">No papers have been added to this track.</p>
    @else
        @php
            $evalCols = count($evaluators);
            $evalWidth = $evalCols > 0 ? min(12, 40 / $evalCols) : 0;
            $titleWidth = 100 - 10 - 11 - 8 - ($evalWidth * $evalCols);
        @endphp
        <table class="sheet">
            <thead>
                <tr>
                    <th style="width: 10%;">Paper No.</th>
                    <th style="width: {{ $titleWidth }}%;">Title / Researcher</th>
                    @foreach($evaluators as $i => $evaluator)
                        <th class="center" style="width: {{ $evalWidth }}%;">
                            <span class="th-sub">Evaluator {{ $i + 1 }}</span>
                            {{ $evaluator['name'] }}
                        </th>
                    @endforeach
                    <th class="avg-head" style="width: 11%;">Average</th>
                    <th class="rank-head" style="width: 8%;">Rank</th>
                </tr>
            </thead>
            <tbody>
                @foreach($papers as $paper)
                    @php $isTop = $paper['rank'] === 1 && $topAverage !== null; @endphp
                    <tr class="{{ $isTop ? 'top' : ($loop->even ? 'even' : '') }}">
                        <td class="paper-no">{{ $paper['paper_no'] }}</td>
                        <td>
                            <p class="title">{{ $paper['title'] }}</p>
                            <p class="sub">{{ $paper['researcher'] }}</p>
                        </td>
                        @foreach($evaluators as $evaluator)
                            <td class="num">{{ $fmt($paper['totals'][$evaluator['id']] ?? null) }}</td>
                        @endforeach
                        <td class="avg">
                            {{ $fmt($paper['average']) }}
                            @if($paper['evaluations_count'] > 0 && $paper['evaluations_count'] < count($evaluators))
                                <span class="partial">{{ $paper['evaluations_count'] }}/{{ count($evaluators) }} submitted</span>
                            @endif
                        </td>
                        <td class="rank">{{ $paper['rank'] ? $ordinal($paper['rank']) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="note">
        Each evaluator's score is the sum of five criteria (Originality 25, Significance 25, Clarity of Presentation 15,
        Mastery of Subject 20, Presentation Materials 15) out of 100. Average is across evaluators who submitted.
        Rank uses competition ranking: tied papers share a rank.
    </p>

    @include('pdf.partials.signatures', [
        'evaluators' => $evaluators,
        'chairs' => [
            ['title' => 'Session Chair', 'name' => $track['session_chair'] ?? null],
            ['title' => 'Co-Session Chair', 'name' => $track['co_session_chair'] ?? null],
        ],
    ])
</div>
@endsection
