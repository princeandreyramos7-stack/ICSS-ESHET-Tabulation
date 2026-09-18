@extends('pdf.layout')

@section('title', 'Paper ' . $result['paper']['paper_no'] . ' Breakdown')

@section('content')
    <div class="header">
        <div class="header-logo">{{ config('conference.short_name') }}</div>
        <div class="header-subtitle">{{ config('conference.name') }}</div>
        <div class="header-title">Paper {{ $result['paper']['paper_no'] }} - Evaluation Breakdown</div>
        <div class="header-info">Track {{ $track['number'] }}: {{ $track['name'] }}</div>
    </div>

    <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <div class="font-bold" style="font-size: 11pt; margin-bottom: 5px;">{{ $result['paper']['title'] }}</div>
        <div class="text-sm text-gray">{{ $result['paper']['researcher'] }}</div>
    </div>

    <div style="margin-bottom: 20px; font-size: 10pt;">
        <strong>Average Score:</strong> 
        <span style="font-size: 14pt; color: #065f46; font-weight: bold;">
            {{ $result['paper']['average'] !== null ? number_format($result['paper']['average'], 2) : 'N/A' }}
        </span>
        / 100
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Rank:</strong> 
        @if($result['paper']['rank'])
            {{ $result['paper']['rank'] }}{{ ['', 'st', 'nd', 'rd'][$result['paper']['rank']] ?? 'th' }}
        @else
            N/A
        @endif
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Evaluations:</strong> {{ count($result['evaluations']) }}
    </div>

    @if(count($result['evaluations']) > 0)
        @foreach($result['evaluations'] as $evaluation)
            <div style="margin-bottom: 30px; page-break-inside: avoid;">
                <div style="background-color: #065f46; color: white; padding: 8px 12px; font-weight: bold; margin-bottom: 10px; border-radius: 4px;">
                    Evaluator: {{ $evaluation['evaluator_name'] }}
                    <span style="float: right;">Total: {{ number_format($evaluation['total'], 2) }}</span>
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
                        @foreach($evaluation['ratings'] as $rating)
                            <tr>
                                <td class="text-center font-bold">{{ $loop->iteration }}</td>
                                <td>{{ $rating['criterion_label'] }}</td>
                                <td class="text-center">{{ number_format($rating['max_rating'], 2) }}</td>
                                <td class="text-center font-bold" style="background-color: #d1fae5;">
                                    {{ number_format($rating['rating'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr style="background-color: #e5e7eb;">
                            <td colspan="3" class="text-right font-bold">Total Score:</td>
                            <td class="text-center font-bold" style="font-size: 11pt; background-color: #d1fae5;">
                                {{ number_format($evaluation['total'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                @if($evaluation['comments'])
                    <div style="margin-top: 10px; padding: 10px; background-color: #f9fafb; border-left: 3px solid #065f46;">
                        <div class="font-bold text-sm" style="margin-bottom: 5px;">Comments:</div>
                        <div style="font-size: 9pt; white-space: pre-wrap;">{{ $evaluation['comments'] }}</div>
                    </div>
                @endif
            </div>
        @endforeach

        <div style="margin-top: 30px; padding: 15px; background-color: #f0fdf4; border: 1px solid #065f46; border-radius: 8px;">
            <div class="font-bold" style="font-size: 11pt; margin-bottom: 5px;">Summary Statistics</div>
            <table style="border: none;">
                <tr style="border: none;">
                    <td style="border: none; width: 40%;"><strong>Number of Evaluations:</strong></td>
                    <td style="border: none;">{{ count($result['evaluations']) }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"><strong>Average Score:</strong></td>
                    <td style="border: none;">{{ $result['paper']['average'] !== null ? number_format($result['paper']['average'], 2) : 'N/A' }} / 100</td>
                </tr>
                @if($result['paper']['rank'])
                    <tr style="border: none;">
                        <td style="border: none;"><strong>Rank in Track:</strong></td>
                        <td style="border: none;">
                            {{ $result['paper']['rank'] }}{{ ['', 'st', 'nd', 'rd'][$result['paper']['rank']] ?? 'th' }}
                        </td>
                    </tr>
                @endif
            </table>
        </div>
    @else
        <div style="padding: 40px; text-align: center; color: #9ca3af; border: 2px dashed #d1d5db; border-radius: 8px;">
            No evaluations have been submitted for this paper yet.
        </div>
    @endif

    <div class="footer">
        <div>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</div>
        <div>{{ config('conference.short_name') }} - {{ config('conference.organizer') }}</div>
    </div>
@endsection
