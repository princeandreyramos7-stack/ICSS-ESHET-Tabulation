@extends('pdf.layout')

@section('title', 'Overall Results')

@section('content')
    <div class="header">
        <div class="header-logo">{{ config('conference.short_name') }}</div>
        <div class="header-subtitle">{{ config('conference.name') }}</div>
        <div class="header-title">Overall Results Summary</div>
        <div class="header-info">Cross-Track Leaderboard and Per-Track Results</div>
    </div>

    @if(count($result['leaderboard']) > 0)
        <h3 style="margin-top: 20px; margin-bottom: 10px; font-size: 12pt; color: #065f46;">Top Papers Across All Tracks</h3>
        <table>
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
                        <td class="text-center font-bold">{{ $paper['overall_rank'] }}</td>
                        <td class="text-center text-sm">T{{ $paper['track_number'] }}</td>
                        <td class="font-bold">{{ $paper['paper_no'] }}</td>
                        <td>
                            <div class="font-bold">{{ $paper['title'] }}</div>
                            <div class="text-xs text-gray">{{ $paper['researcher'] }}</div>
                        </td>
                        <td class="text-center font-bold">{{ number_format($paper['average'], 2) }}</td>
                        <td class="text-center text-xs">
                            @if($paper['track_locked'])
                                <span style="color: #065f46;">✓ Final</span>
                            @else
                                <span style="color: #92400e;">Open</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="page-break-before: always;"></div>

    <h3 style="margin-top: 20px; margin-bottom: 15px; font-size: 12pt; color: #065f46;">Results by Track</h3>

    @foreach($result['tracks'] as $track)
        <div style="margin-bottom: 25px; page-break-inside: avoid;">
            <div style="background-color: #065f46; color: white; padding: 8px 12px; font-weight: bold; margin-bottom: 10px;">
                Track {{ $track['number'] }}: {{ $track['name'] }}
                @if($track['locked'])
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
                                <td class="text-center font-bold">
                                    {{ $paper['average'] !== null ? number_format($paper['average'], 2) : '-' }}
                                </td>
                                <td class="text-center text-xs">
                                    {{ $paper['evaluations_count'] }} / {{ $track['evaluator_count'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="padding: 20px; text-align: center; color: #9ca3af; border: 1px dashed #d1d5db;">
                    No papers in this track.
                </div>
            @endif
        </div>

        @if(!$loop->last && $loop->iteration % 2 === 0)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

    <div class="footer" style="margin-top: 40px;">
        <div>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</div>
        <div>{{ config('conference.short_name') }} - {{ config('conference.organizer') }}</div>
        @if(!$result['all_locked'])
            <div style="color: #92400e; margin-top: 5px;">
                <strong>Note:</strong> Some tracks are still open. Final results may change until all tracks are locked.
            </div>
        @endif
    </div>
@endsection
