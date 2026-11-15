@extends('layouts.app')
@section('t','Report card')
@section('c')
@php($grade=$avg===null?'—':($avg>=80?'A':($avg>=70?'B':($avg>=60?'C':($avg>=50?'D':'E')))))
<div class="report-actions no-print"><button class="btn" type="button" onclick="window.print()">Print / Save as PDF</button></div>
<article class="report-page card">
    <header class="report-header">
        <img class="logo-transparent" src="{{ asset('images/logo.png') }}" alt="Groupe Scolaire APACOPE">
        <div><h2>Groupe Scolaire APACOPE</h2><p>Student report card · {{ \App\Models\Setting::val('term','Term 2') }} {{ \App\Models\Setting::val('academic_year',date('Y')) }}</p></div>
    </header>
    
    <table class="report-student-header">
        <tr>
            <td><strong>Name:</strong> {{ $s->name }}</td>
            <td><strong>Reg. No:</strong> {{ $s->admission_no }}</td>
            <td><strong>Class:</strong> {{ $s->schoolClass->name }}</td>
        </tr>
        <tr>
            <td><strong>SDMS No:</strong> {{ $s->sdms_no ?: '—' }}</td>
            <td><strong>Level:</strong> {{ $s->level() }}</td>
            <td><strong>Trade:</strong> {{ $s->trade() }}</td>
        </tr>
    </table>

    <table class="report-summary-compact">
        <tr>
            <td><strong>Overall:</strong> {{ $avg===null?'—':$avg.'%' }}</td>
            <td><strong>Grade:</strong> {{ $grade }}</td>
            <td><strong>Position:</strong> {{ $rank ? $rank.'/'.$rankedStudents : '—' }}</td>
            <td><strong>Competency:</strong> @if($avg!==null)<span class="{{ $avg>=70?'ok':'bad' }}">{{ $avg>=70?'Competent':'Not yet competent' }}</span>@else — @endif</td>
        </tr>
        <tr>
            <td colspan="4"><strong>Attendance (90 days):</strong> Present/Late: {{ ($att['P']??0)+($att['L']??0) }} · Absent: {{ $att['A']??0 }} · Excused: {{ $att['E']??0 }}</td>
        </tr>
    </table>

    <table class="report-subjects-compact">
        <thead>
            <tr>
                <th>Subject</th>
                @foreach($rows->first()['tests'] ?? [] as $i => $test)
                    <th>{{ $test['a']->title }}<br><small>{{ $test['a']->date->format('d M') }}</small></th>
                @endforeach
                <th>Result</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td><strong>{{ $row['m']->name }}</strong></td>
                    @foreach($row['tests'] as $test)
                        <td>{{ $test['v']!==null ? rtrim(rtrim(number_format($test['v'],1),'0'),'.') : '—' }}</td>
                    @endforeach
                    <td><strong>{{ $row['p']===null?'—':$row['p'].'%' }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="10" class="report-muted">No subjects or assessments are available yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="report-signatures"><tbody><tr><td>Class teacher's signature</td><td>DOS signature</td><td>Parent or guardian signature</td></tr></tbody></table>
</article>
<style>
    .report-page {
        padding: 20px;
        max-width: 800px;
        margin: 0 auto;
        font-size: 11px;
    }
    .report-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #333;
    }
    .report-header img {
        height: 60px;
    }
    .report-header h2 {
        margin: 0;
        font-size: 16px;
    }
    .report-header p {
        margin: 5px 0 0;
        font-size: 12px;
    }
    .report-student-header {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .report-student-header td {
        padding: 4px 8px;
        border: 1px solid #ddd;
    }
    .report-summary-compact {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        background: #f5f5f5;
    }
    .report-summary-compact td {
        padding: 6px 10px;
        border: 1px solid #ddd;
        text-align: center;
    }
    .report-subjects-compact {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .report-subjects-compact th,
    .report-subjects-compact td {
        padding: 5px 8px;
        border: 1px solid #ddd;
        text-align: center;
    }
    .report-subjects-compact th {
        background: #333;
        color: white;
        font-size: 10px;
    }
    .report-subjects-compact small {
        font-size: 9px;
    }
    .report-signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .report-signatures td {
        padding: 15px;
        border-top: 1px solid #333;
        text-align: center;
        font-size: 10px;
    }
    .report-muted {
        color: #999;
        font-style: italic;
    }
    .ok { color: #16a05a; }
    .bad { color: #e31b23; }
</style>
<script>
    const reportPage = document.querySelector('.report-page');
    const fitReportToPage = () => {
        reportPage.style.zoom = '';
        const availableHeight = 1000;
        const scale = Math.min(1, availableHeight / reportPage.scrollHeight);
        reportPage.style.zoom = String(scale);
    };
    window.addEventListener('beforeprint', fitReportToPage);
    window.addEventListener('afterprint', () => {
        reportPage.style.zoom = '';
    });
</script>
@endsection
