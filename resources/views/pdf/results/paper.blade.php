@extends('pdf.layout')

@php
    // $result: ResultService::forPaper(); $track: [number, name]; $rank: the paper's rank in its track (nullable).
    $paper = $result['paper'];
    $criteria = $result['criteria'];
    $evaluations = $result['evaluations'];
    $maxTotal = array_sum(array_column($criteria, 'weight'));
    $withComments = array_values(array_filter($evaluations, fn ($e) => $e['submitted'] && !empty($e['comments'])));
    $ordinal = fn (?int $n) => $n ? $n . (['', 'st', 'nd', 'rd'][$n] ?? 'th') : '-';
    $fmt = fn ($v) => $v === null ? '-' : number_format((float) $v, 2);
@endphp

@section('title', 'Paper ' . $paper['paper_no'] . ' Breakdown')
@section('document', 'Paper ' . $paper['paper_no'] . ' Evaluation Breakdown')

@section('content')
<div class="pg-1">
    @include('pdf.partials.letterhead', [
        'trackLabel' => 'Track ' . $track['number'] . ': ' . $track['name'],
        'subtitle' => 'Evaluation Breakdown',
    ])

    <table class="dl">
        <tr><td class="k">Paper No.</td><td class="paper-no">{{ $paper['paper_no'] }}</td></tr>
        <tr><td class="k">Title</td><td>{{ $paper['title'] }}</td></tr>
        <tr><td class="k">Researcher</td><td>{{ $paper['researcher'] }}{{ !empty($paper['affiliation']) ? ' - ' . $paper['affiliation'] : '' }}</td></tr>
    </table>

    <table class="meta">
        <tr>
            <td>{{ $result['evaluations_count'] }} of {{ count($evaluations) }} evaluator{{ count($evaluations) === 1 ? '' : 's' }} submitted</td>
            <td class="right">
                <span class="badge badge-muted">AVERAGE {{ $fmt($result['average']) }} / {{ $maxTotal }}</span>
                <span class="badge">RANK IN TRACK: {{ $ordinal($rank) }}</span>
            </td>
        </tr>
    </table>

    @if(count($evaluations) === 0)
        <p class="empty">No evaluators are assigned to this track yet.</p>
    @else
        <table class="sheet">
            <thead>
                <tr>
                    <th style="width: 30%;">Evaluator</th>
                    @foreach($criteria as $i => $c)
                        <th class="center">
                            C{{ $i + 1 }}
                            <span class="th-sub">{{ $c['weight'] }}%</span>
                        </th>
                    @endforeach
                    <th class="avg-head" style="width: 14%;">Total / {{ $maxTotal }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluations as $row)
                    <tr class="{{ $loop->even ? 'even' : '' }}">
                        <td>
                            <p class="title">{{ $row['evaluator_name'] }}</p>
                            <p class="sub">
                                {{ $row['submitted'] ? 'Submitted ' . \Carbon\Carbon::parse($row['submitted_at'])->format('M j, Y g:i A') : 'Not yet submitted' }}
                            </p>
                        </td>
                        @foreach($criteria as $c)
                            <td class="num">{{ $fmt($row['scores'][$c['id']] ?? null) }}</td>
                        @endforeach
                        <td class="avg">{{ $fmt($row['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>
                        Average
                        <span class="sub" style="display: block; font-weight: normal;">{{ $result['evaluations_count'] }} of {{ count($evaluations) }} submitted</span>
                    </td>
                    @foreach($criteria as $c)
                        <td class="num">{{ $fmt($result['criterion_averages'][$c['id']] ?? null) }}</td>
                    @endforeach
                    <td class="avg" style="font-size: 10pt;">{{ $fmt($result['average']) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin-top: 6px;">
        @foreach(array_chunk($criteria, 2) as $pair)
            <tr>
                @foreach($pair as $c)
                    @php $i = array_search($c, $criteria, true); @endphp
                    <td class="note" style="width: 50%; padding: 0 6px 0 0;"><strong style="color: #1f2937;">C{{ $i + 1 }}</strong> - {{ $c['name'] }} ({{ $c['weight'] }}%)</td>
                @endforeach
                @if(count($pair) === 1)<td style="width: 50%;"></td>@endif
            </tr>
        @endforeach
    </table>

    <p class="section-title" style="margin-top: 14px; color: #374151;">Comments and suggestions</p>
    @if(count($withComments) === 0)
        <p class="note" style="margin-top: 0;">No comments were submitted.</p>
    @else
        @foreach($withComments as $row)
            <div class="comment">
                <p class="who">{{ $row['evaluator_name'] }}</p>
                <p class="text">{{ $row['comments'] }}</p>
            </div>
        @endforeach
    @endif

    @include('pdf.partials.signatures', [
        'evaluators' => array_map(fn ($e) => ['name' => $e['evaluator_name']], $evaluations),
        'chairs' => [],
    ])
</div>
@endsection
