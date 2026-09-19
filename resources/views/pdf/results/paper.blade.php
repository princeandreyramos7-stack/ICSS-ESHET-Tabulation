@extends('pdf.layout')

@section('title', 'Paper ' . $result['paper']['paper_no'] . ' Breakdown')

@php
    // Data shape comes from ResultService::forPaper(); $rank is the paper's
    // competition rank within its track (null when nothing is submitted yet).
    $submitted = array_values(array_filter($result['evaluations'], fn ($e) => $e['submitted']));
    $pending = array_values(array_filter($result['evaluations'], fn ($e) => ! $e['submitted']));
    $ordinal = fn (int $n) => $n . (['', 'st', 'nd', 'rd'][$n] ?? 'th');
@endphp

@section('content')
    <div class="header">
        <div class="header-logo">ISABELA STATE UNIVERSITY - CITY OF ILAGAN CAMPUS</div>
        <div class="header-subtitle">2nd International Conference on Sustainable Solutions in Engineering, Science, Health, Education, and Technology</div>
        <div class="header-info">RESEARCH AND EXTENSION · SEPTEMBER 23-25, 2026 · CITY OF ILAGAN, ISABELA</div>
    </div>

    <div class="track-badge">
        Track {{ $track['number'] }}: {{ $track['name'] }}
    </div>

    <div class="no-page-break" style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <div style="font-size: 12pt; margin-bottom: 5px; color: #065f46;"><strong>Paper {{ $result['paper']['paper_no'] }}</strong></div>
        <div class="font-bold" style="font-size: 11pt; margin-bottom: 5px;">{{ $result['paper']['title'] }}</div>
        <div class="text-sm text-gray">{{ $result['paper']['researcher'] }}</div>
        @if(!empty($result['paper']['affiliation']))
            <div class="text-xs text-gray">{{ $result['paper']['affiliation'] }}</div>
        @endif
    </div>

    <div class="no-page-break" style="margin-bottom: 20px; font-size: 10pt; background-color: #ecfdf5; padding: 12px; border-radius: 6px; border-left: 4px solid #065f46;">
        <strong>Average Score:</strong>
        <span style="font-size: 16pt; color: #065f46; font-weight: bold;">
            {{ $result['average'] !== null ? number_format($result['average'], 2) : 'N/A' }}
        </span>
        / 100
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Rank:</strong>
        @if($rank)
            <span style="font-weight: bold; color: #065f46;">{{ $ordinal($rank) }}</span>
        @else
            N/A
        @endif
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Evaluations:</strong> {{ $result['evaluations_count'] }} / {{ count($result['evaluations']) }}
    </div>

    @if(count($submitted) > 0)
        @foreach($submitted as $evaluation)
            <div class="no-page-break" style="margin-bottom: 30px;">
                <div style="background-color: #065f46; color: white; padding: 8px 12px; font-weight: bold; margin-bottom: 10px; border-radius: 4px;">
                    Evaluator: {{ $evaluation['evaluator_name'] }}
                    <span style="float: right;">Total: {{ number_format((float) $evaluation['total'], 2) }}</span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%;">No.</th>
                            <th style="width: 55%;">Criterion</th>
                            <th class="text-center" style="width: 15%;">Max</th>
                            <th class="text-center" style="width: 20%; background-color: #047857;">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($result['criteria'] as $criterion)
                            @php $score = $evaluation['scores'][$criterion['id']] ?? null; @endphp
                            <tr>
                                <td class="text-center font-bold">{{ $loop->iteration }}</td>
                                <td>{{ $criterion['name'] }}</td>
                                <td class="text-center">{{ number_format((float) $criterion['weight'], 2) }}</td>
                                <td class="text-center font-bold" style="background-color: #d1fae5;">
                                    {{ $score !== null ? number_format((float) $score, 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                        <tr style="background-color: #e5e7eb;">
                            <td colspan="3" class="text-right font-bold">Total Score:</td>
                            <td class="text-center font-bold" style="font-size: 11pt; background-color: #a7f3d0;">
                                {{ number_format((float) $evaluation['total'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                @if(!empty($evaluation['comments']))
                    <div style="margin-top: 10px; padding: 10px; background-color: #f9fafb; border-left: 3px solid #065f46;">
                        <div class="font-bold text-sm" style="margin-bottom: 5px;">Comments:</div>
                        <div style="font-size: 9pt; white-space: pre-wrap;">{{ $evaluation['comments'] }}</div>
                    </div>
                @endif
            </div>

            @if(!$loop->last)
                <div style="border-bottom: 2px dashed #d1d5db; margin: 20px 0;"></div>
            @endif
        @endforeach

        <div class="no-page-break" style="margin-top: 30px; padding: 15px; background-color: #f0fdf4; border: 1px solid #065f46; border-radius: 8px;">
            <div class="font-bold" style="font-size: 11pt; margin-bottom: 8px; color: #065f46;">Summary Statistics</div>
            <table style="border: none;">
                <tr style="border: none;">
                    <td style="border: none; width: 40%;"><strong>Number of Evaluations:</strong></td>
                    <td style="border: none;">{{ $result['evaluations_count'] }} of {{ count($result['evaluations']) }} panel member(s)</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"><strong>Average Score:</strong></td>
                    <td style="border: none; font-weight: bold; color: #065f46;">{{ $result['average'] !== null ? number_format($result['average'], 2) : 'N/A' }} / 100</td>
                </tr>
                @if($rank)
                    <tr style="border: none;">
                        <td style="border: none;"><strong>Rank in Track:</strong></td>
                        <td style="border: none; font-weight: bold; color: #065f46;">{{ $ordinal($rank) }}</td>
                    </tr>
                @endif
                @foreach($result['criteria'] as $criterion)
                    @php $avg = $result['criterion_averages'][$criterion['id']] ?? null; @endphp
                    <tr style="border: none;">
                        <td style="border: none;" class="text-sm">Avg. {{ $criterion['name'] }}</td>
                        <td style="border: none;" class="text-sm">{{ $avg !== null ? number_format((float) $avg, 2) : '-' }} / {{ $criterion['weight'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @else
        <div style="padding: 40px; text-align: center; color: #9ca3af; border: 2px dashed #d1d5db; border-radius: 8px;">
            No evaluations have been submitted for this paper yet.
        </div>
    @endif

    @if(count($pending) > 0)
        <div class="no-page-break text-sm text-gray" style="margin-top: 15px; padding: 10px; border: 1px dashed #d1d5db; border-radius: 6px;">
            <strong>Not yet submitted:</strong>
            {{ implode(', ', array_column($pending, 'evaluator_name')) }}
        </div>
    @endif

    <div class="footer">
        <div><strong>Generated:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</div>
        <div><strong>Document:</strong> Paper {{ $result['paper']['paper_no'] }} Evaluation Breakdown</div>
        <div style="margin-top: 5px; font-size: 7pt;">{{ config('conference.short_name') }} - {{ config('conference.organizer') }}</div>
    </div>
@endsection
