@extends('layouts.app')
@section('t', 'Quiz Results')
@section('c')
<div class="card">
    <h3>{{ $assignment->title }} - Results</h3>
    <p><strong>Student:</strong> {{ $user->name }}</p>
    <p><strong>Class:</strong> {{ $assignment->schoolClass->name }}</p>
    <p><strong>Module:</strong> {{ $assignment->module }}</p>
    <p class="b {{ $earnedPoints == $totalPoints ? 'ok' : ($earnedPoints > 0 ? 'warn' : 'bad') }}" style="font-size:1.2em;margin-top:12px">
        Your Score: {{ $earnedPoints }} / {{ $totalPoints }} ({{ $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0 }}%)
    </p>
</div>

@foreach($questions as $q)
    @php($answer = $answers->get($q->id))
    <div class="card" style="margin-top:14px">
        <p><strong>Q{{ $loop->iteration }}:</strong> {{ $q->question }} <span class="mu">({{ $q->points }} point{{ $q->points>1?'s':'' }})</span></p>
        
        @if($q->type==='multiple_choice')
            <p class="mu">Your answer: <strong class="{{ $answer?->is_correct ? 'ok' : 'bad' }}">{{ $answer?->answer ?? 'Not answered' }}</strong></p>
            <p class="mu">Correct answer: <strong class="ok">{{ $q->correct_answer }}</strong></p>
            @if(!$answer?->is_correct)
                <p class="mu">Options:</p>
                @foreach($q->options??[] as $opt)
                    <p class="mu" style="margin-left:12px">{{ $opt }}</p>
                @endforeach
            @endif
        @elseif($q->type==='true_false')
            <p class="mu">Your answer: <strong class="{{ $answer?->is_correct ? 'ok' : 'bad' }}">{{ $answer?->answer ?? 'Not answered' }}</strong></p>
            <p class="mu">Correct answer: <strong class="ok">{{ $q->correct_answer }}</strong></p>
        @elseif($q->type==='matching')
            @php($correctPairs = json_decode($q->correct_answer, true) ?? [])
            @php($studentPairs = json_decode($answer?->answer ?? '{}', true) ?? [])
            <p class="mu">Your matches:</p>
            @foreach($correctPairs as $item=>$match)
                <p class="mu" style="margin-left:12px">
                    {{ $item }} → <strong class="{{ ($studentPairs[$item] ?? '') === $match ? 'ok' : 'bad' }}">{{ $studentPairs[$item] ?? 'Not matched' }}</strong>
                    (Correct: {{ $match }})
                </p>
            @endforeach
        @else
            <p class="mu">Your answer: <strong class="{{ $answer?->is_correct ? 'ok' : 'bad' }}">{{ $answer?->answer ?? 'Not answered' }}</strong></p>
            <p class="mu">Correct answer: <strong class="ok">{{ $q->correct_answer }}</strong></p>
        @endif
        
        <p class="b {{ $answer?->is_correct ? 'ok' : 'bad' }}" style="margin-top:8px">
            {{ $answer?->is_correct ? '✓ Correct' : '✗ Incorrect' }} - {{ $answer?->points_earned ?? 0 }} / {{ $q->points }} points
        </p>
    </div>
@endforeach

<div class="card" style="margin-top:20px">
    <a href="/assignments" class="btn">Back to Assignments</a>
</div>
@endsection
