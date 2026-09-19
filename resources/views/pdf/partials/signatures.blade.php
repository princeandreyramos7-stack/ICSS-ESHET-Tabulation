{{-- Signature lines for the panel, then (optionally) the session chairs. $evaluators: [{name}], $chairs: [{title, name}] --}}
@php
    $chairs = array_values(array_filter($chairs ?? [], fn ($c) => !empty($c['name'])));
    // Up to four signatures per row; fewer names get wider, still-centered columns like the on-screen block.
    $cols = max(1, min(count($evaluators), 4));
    $chairCols = max(1, min(count($chairs), 2));
@endphp
@if(count($evaluators) > 0 || count($chairs) > 0)
    <div class="avoid-break">
        @if(count($evaluators) > 0)
            <p class="sig-heading">Panel of Evaluators</p>
            <table class="sigs" align="center" style="width: {{ 25 * $cols }}%;">
                @foreach(array_chunk($evaluators, $cols) as $row)
                    <tr>
                        @foreach($row as $evaluator)
                            <td style="width: {{ 100 / $cols }}%;">
                                <div class="sig-line"></div>
                                <p class="sig-name">{{ $evaluator['name'] }}</p>
                                <p class="sig-role">Evaluator</p>
                            </td>
                        @endforeach
                        @for($i = count($row); $i < $cols; $i++)
                            <td style="width: {{ 100 / $cols }}%;"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        @endif

        @if(count($chairs) > 0)
            <p class="sig-heading" style="margin-top: 16px;">Attested by</p>
            <table class="sigs" align="center" style="width: {{ 40 * $chairCols }}%;">
                <tr>
                    @foreach($chairs as $chair)
                        <td style="width: {{ 100 / $chairCols }}%;">
                            <div class="sig-line"></div>
                            <p class="sig-name">{{ $chair['name'] }}</p>
                            <p class="sig-role">{{ $chair['title'] }}</p>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif
    </div>
@endif
