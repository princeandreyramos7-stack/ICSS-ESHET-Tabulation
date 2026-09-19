@extends('pdf.layout')

@section('title', 'Overall Results')

@section('content')
    <div class="header">
        <div class="header-logo">ISABELA STATE UNIVERSITY - CITY OF ILAGAN CAMPUS</div>
        <div class="header-subtitle">2nd International Conference on Sustainable Solutions in Engineering, Science, Health, Education, and Technology</div>
        <div class="header-info">RESEARCH AND EXTENSION · SEPTEMBER 23-25, 2026 · CITY OF ILAGAN, ISABELA</div>
    </div>

    <div style="text-align: center; margin-bottom: 20px;">
        <div class="track-badge" style="font-size: 12pt;">Overall Results Summary</div>
        <div class="text-sm" style="margin-top: 8px;">Cross-Track Leaderboard and Per-Track Results</div>
    </div>

    @if(count($result['leaderboard']) > 0)
        <h3 style="margin-top: 20px; margin-bottom: 10px; font-size: 12pt; color: #065f46; background-color: #ecfdf5; padding: 8px; border-radius: 4px;">
            Top Papers Across All Tracks
        </h3>
        <table class="no-page-break">
            <thead>
                <tr>
                    <th style="width: 8%;">Rank</th>
                    <th style="width: 8%;">Track</th>
                    <th style="width: 10%;">Paper</th>
                    <th style="width: 54%;">Title / Researcher</th>
                    <th class="text-center" style="width: 10%;">Average</th>
                    <th class="text-center" style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($result['leaderboard'] as $paper)
                    <tr @if($loop->iteration === 1) class="top-row" @endif>
                        <td class="text-center font-bold" style="font-size: 11pt;">{{ $paper['overall_rank'] }}</td>
                        <td class="text-center text-sm" style="background-color: #fef3c7;">T{{ $paper['track_number'] }}</td>
                        <td class="font-bold">{{ $paper['paper_no'] }}</td>
                        <td>
                            <div class="font-bold">{{ $paper['title'] }}</div>
                            <div class="text-xs text-gray">{{ $paper['researcher'] }}</div>
                        </td>
                        <td class="text-center font-bold" style="color: #065f46;">{{ number_format($paper['average'], 2) }}</td>
                        <td class="text-center text-xs">
                            @if($paper['track_locked'])
                                <span style="color: #065f46; font-weight: bold;">Final</span>
                            @else
                                <span style="color: #92400e;">Open</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-break-before"></div>

    <h3 style="margin-top: 20px; margin-bottom: 15px; font-size: 12pt; color: #065f46; background-color: #ecfdf5; padding: 8px; border-radius: 4px;">
        Results by Track
    </h3>

    @foreach($result['tracks'] as $track)
        <div class="no-page-break" style="margin-bottom: 25px;">
            <div style="background-color: #065f46; color: white; padding: 8px 12px; font-weight: bold; margin-bottom: 10px; border-radius: 4px;">
                Track {{ $track['track']['number'] }}: {{ $track['track']['name'] }}
                @if($track['track']['is_locked'])
                    <span class="badge" style="float: right; background-color: #fbbf24; color: #065f46;">LOCKED</span>
                @endif
            </div>

            @if(count($track['papers']) > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%;">Rank</th>
                            <th style="width: 12%;">Paper</th>
                            <th style="width: 58%;">Title / Researcher</th>
                            <th class="text-center" style="width: 10%;">Average</th>
                            <th class="text-center" style="width: 10%;">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($track['papers'] as $paper)
                            <tr>
                                <td class="text-center font-bold">
                                    @if($paper['rank'])
                                        {{ $paper['rank'] }}{{ ['', 'st', 'nd', 'rd'][$paper['rank']] ?? 'th' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="font-bold">{{ $paper['paper_no'] }}</td>
                                <td>
                                    <div class="font-bold">{{ $paper['title'] }}</div>
                                    <div class="text-xs text-gray">{{ $paper['researcher'] }}</div>
                                </td>
                                <td class="text-center font-bold" style="color: #065f46;">
                                    {{ $paper['average'] !== null ? number_format($paper['average'], 2) : '-' }}
                                </td>
                                <td class="text-center text-xs">
                                    {{ $paper['evaluations_count'] }} / {{ $track['evaluators_count'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="padding: 20px; text-align: center; color: #9ca3af; border: 1px dashed #d1d5db; border-radius: 4px;">
                    No papers in this track.
                </div>
            @endif
        </div>

        @if(!$loop->last && $loop->iteration % 2 === 0)
            <div class="page-break-after"></div>
        @endif
    @endforeach

    <div class="footer no-page-break" style="margin-top: 40px;">
        <div><strong>Generated:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</div>
        <div><strong>Document:</strong> Overall Results Summary (All Tracks)</div>
        <div style="margin-top: 5px; font-size: 7pt;">{{ config('conference.short_name') }} - {{ config('conference.organizer') }}</div>
        @if(!$result['all_locked'])
            <div style="color: #92400e; margin-top: 8px; background-color: #fef3c7; padding: 6px; border-radius: 4px;">
                <strong>Note:</strong> Some tracks are still open. Final results may change until all tracks are locked.
            </div>
        @endif
    </div>
@endsection