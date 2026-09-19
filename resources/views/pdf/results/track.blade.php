@extends('pdf.layout')

@section('title', 'Track ' . $result['track']['number'] . ' Results')

@section('content')
    <div class="header">
        <div class="header-logo">ISABELA STATE UNIVERSITY - CITY OF ILAGAN CAMPUS</div>
        <div class="header-subtitle">2nd International Conference on Sustainable Solutions in Engineering, Science, Health, Education, and Technology</div>
        <div class="header-info">RESEARCH AND EXTENSION · SEPTEMBER 23-25, 2026 · CITY OF ILAGAN, ISABELA</div>
    </div>

    <div class="track-badge">
        Track {{ $result['track']['number'] }}: {{ $result['track']['name'] }}
    </div>

    <div class="no-page-break" style="margin-bottom: 15px; font-size: 9pt; background-color: #ecfdf5; padding: 10px; border-radius: 4px;">
        <strong>{{ count($result['papers']) }}</strong> paper(s) - 
        <strong>{{ count($result['evaluators']) }}</strong> evaluator(s)
        @if($result['track']['is_locked'])
            <span class="badge" style="margin-left: 10px;">FINAL (LOCKED)</span>
        @endif
        @if($result['track']['venue'])
            <span style="margin-left: 10px;">| <strong>Venue:</strong> {{ $result['track']['venue'] }}</span>
        @endif
    </div>

    @if(count($result['evaluators']) > 0 && count($result['papers']) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Paper No.</th>
                    <th style="width: 35%;">Title / Researcher</th>
                    @foreach($result['evaluators'] as $index => $evaluator)
                        <th class="text-center" style="width: {{ 40 / count($result['evaluators']) }}%;">
                            <div class="text-xs" style="color: #a7f3d0;">Evaluator {{ $index + 1 }}</div>
                            <div>{{ $evaluator['name'] }}</div>
                        </th>
                    @endforeach
                    <th class="text-center" style="width: 10%; background-color: #047857;">Average</th>
                    <th class="text-center" style="width: 7%; background-color: #fbbf24; color: #065f46;">Rank</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $topAverage = collect($result['papers'])->where('rank', 1)->first()['average'] ?? null;
                @endphp
                @foreach($result['papers'] as $paper)
                    <tr @if($paper['rank'] === 1 && $topAverage !== null) class="top-row" @endif>
                        <td class="font-bold text-center">{{ $paper['paper_no'] }}</td>
                        <td>
                            <div class="font-bold">{{ $paper['title'] }}</div>
                            <div class="text-xs text-gray">{{ $paper['researcher'] }}</div>
                        </td>
                        @foreach($result['evaluators'] as $evaluator)
                            <td class="text-center">
                                @if(isset($paper['totals'][$evaluator['id']]))
                                    {{ number_format($paper['totals'][$evaluator['id']], 2) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center font-bold" style="background-color: #d1fae5;">
                            {{ $paper['average'] !== null ? number_format($paper['average'], 2) : '-' }}
                            @if($paper['evaluations_count'] > 0 && $paper['evaluations_count'] < count($result['evaluators']))
                                <div class="text-xs" style="color: #92400e;">
                                    {{ $paper['evaluations_count'] }}/{{ count($result['evaluators']) }} submitted
                                </div>
                            @endif
                        </td>
                        <td class="text-center font-bold">
                            @if($paper['rank'])
                                {{ $paper['rank'] }}{{ ['', 'st', 'nd', 'rd'][$paper['rank']] ?? 'th' }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="text-xs text-gray no-page-break" style="margin-top: 10px; background-color: #f9fafb; padding: 8px; border-radius: 4px;">
            <strong>Evaluation Breakdown:</strong> Each evaluator's score is the sum of five criteria (Originality 25, Significance 25, Clarity of Presentation 15, 
            Mastery of Subject 20, Presentation Materials 15) out of 100. Average is across evaluators who submitted. 
            Rank uses competition ranking: tied papers share a rank.
        </div>

        <div class="signatures no-page-break">
            <div style="font-weight: bold; margin-bottom: 15px; font-size: 11pt; color: #065f46;">Panel of Evaluators:</div>
            <div class="signature-grid">
                @foreach($result['evaluators'] as $evaluator)
                    <div class="signature-item">
                        <div class="signature-label">Evaluator {{ $loop->iteration }}</div>
                        <div class="signature-line">{{ $evaluator['name'] }}</div>
                    </div>
                    @if($loop->iteration % 3 === 0 && !$loop->last)
                        </div><div class="signature-grid" style="margin-top: 30px;">
                    @endif
                @endforeach
            </div>

            @if($result['track']['session_chair'] || $result['track']['co_session_chair'])
                <div class="signature-grid" style="margin-top: 40px;">
                    @if($result['track']['session_chair'])
                        <div class="signature-item">
                            <div class="signature-label">Session Chair</div>
                            <div class="signature-line">{{ $result['track']['session_chair'] }}</div>
                        </div>
                    @endif
                    @if($result['track']['co_session_chair'])
                        <div class="signature-item">
                            <div class="signature-label">Co-Session Chair</div>
                            <div class="signature-line">{{ $result['track']['co_session_chair'] }}</div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @else
        <div style="padding: 40px; text-align: center; color: #9ca3af; border: 2px dashed #d1d5db; border-radius: 8px;">
            @if(count($result['evaluators']) === 0)
                No evaluators are assigned to this track yet.
            @else
                No papers have been added to this track.
            @endif
        </div>
    @endif

    <div class="footer">
        <div><strong>Generated:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</div>
        <div><strong>Document:</strong> Track {{ $result['track']['number'] }} Results Summary</div>
        <div style="margin-top: 5px; font-size: 7pt;">{{ config('conference.short_name') }} - {{ config('conference.organizer') }}</div>
    </div>
@endsection