@extends('layouts.app')@section('t','Timetable')@section('c')
@if($trainerView)
<div class="card"><h3>Your lessons today</h3>
@php($todayLessons=$trainerLessons->where('day',$todayDay))
@forelse($todayLessons as $lesson)
<div class="dash-lesson"><time>{{ $lesson->time }}</time><div><b>{{ $lesson->subject }}</b><small>{{ $lesson->className }}@if($lesson->room) · {{ $lesson->room }}@endif</small></div>@if($lesson->moduleId)<a href="/marks?class={{ $lesson->school_class_id }}&module={{ $lesson->moduleId }}">Open module →</a>@endif</div>
@empty<p class="mu">No lessons are scheduled for you today.</p>@endforelse</div>
<div class="card"><h3>Your weekly timetable</h3><p class="mu">Lessons are grouped across every class you teach. Class teachers also see the full timetable for their designated class.</p>
<table><tr><th>Day</th><th>Time</th><th>Class</th><th>Lesson</th><th>Teacher</th><th></th></tr>
@forelse($trainerLessons as $lesson)
<tr><td><b>{{ $lesson->dayName }}</b>@if($lesson->day===$todayDay)<span class="b info">Today</span>@endif</td><td>{{ $lesson->time }}</td><td>{{ $lesson->className }}</td><td><b>{{ $lesson->subject }}</b>@if($lesson->room)<br><small class="mu">{{ $lesson->room }}</small>@endif</td><td>{{ $lesson->teacherName ?: 'Assigned teacher' }}</td><td>@if($lesson->moduleId)<a href="/marks?class={{ $lesson->school_class_id }}&module={{ $lesson->moduleId }}">Open module</a>@endif</td></tr>
@empty<tr><td colspan="6" class="mu">No lessons have been assigned to your timetable yet.</td></tr>@endforelse</table></div>
@else
@if(!$class)
<div class="card"><h3>No classes are available yet</h3><p class="mu">There are no classes in your section to display or edit. Ask the administrator to add your classes first.</p></div>
@else
@if($classes->count()>1)<div class="chips">@foreach($classes as $c)<a class="{{ $c->id==$class->id?'on':'' }}" href="?class={{ $c->id }}">{{ $c->name }}</a>@endforeach</div>@endif
@if($edit)<datalist id="subs">@foreach($subjects as $x)<option value="{{ $x }}">@endforeach</datalist><form method="post" action="/timetable">@csrf<input type="hidden" name="class_id" value="{{ $class->id }}">@endif
<div class="card"><h3>Weekly timetable · {{ $class?->name }}</h3>@if($edit)<p class="mu">Only the DOS edits the timetable. Set the start and end time of each period, then the lessons. Leave a period's start time empty to remove that period. Everyone in the class sees the changes straight away.</p>@endif
<table class="tt"><tr><th>Period</th>@foreach(['Mon','Tue','Wed','Thu','Fri'] as $d)<th>{{ $d }}</th>@endforeach</tr>
@foreach($slots as $s)@php($p=$P[$s]??null)<tr><td style="min-width:170px">@if($edit)<b>Period {{ $s }}</b><div class="row" style="margin:6px 0 0;gap:6px"><input type="time" name="p[{{ $s }}][start]" value="{{ $p->start??'' }}" style="width:112px"><span class="mu">to</span><input type="time" name="p[{{ $s }}][end]" value="{{ $p->end??'' }}" style="width:112px"></div>@else<b>{{ $p->start }} – {{ $p->end }}</b>@endif</td>@foreach(range(1,5) as $d)@php($l=$L[$d.'-'.$s]??null)<td>@if($edit)<input name="c[{{ $d }}-{{ $s }}][subject]" list="subs" value="{{ $l->subject??'' }}" placeholder="Subject" style="width:100%;margin-bottom:6px"><input name="c[{{ $d }}-{{ $s }}][room]" value="{{ $l->room??'' }}" placeholder="Room" style="width:100%;margin-bottom:6px"><select name="c[{{ $d }}-{{ $s }}][trainer]" style="width:100%"><option value="">Use subject's assigned teacher</option>@foreach($trainers as $tr)<option value="{{ $tr->id }}" @selected(($l->trainer_id??($moduleTrainers[$l->subject??'']??null))==$tr->id)>{{ $tr->name }}</option>@endforeach</select>@elseif($l)<div class="ls {{ $l->subject==='Practical Lab'?'pr':'' }}">{{ $l->subject }}<small>{{ $l->room }}@if($l->trainer_id) · {{ $tn[$l->trainer_id]??'' }}@endif</small></div>@endif</td>@endforeach</tr>@endforeach</table>
@if($edit)<div class="row" style="margin:20px 0 0"><button class="btn">Save times and timetable</button></div>@endif</div>@if($edit)</form>@endif
@endif
@endif
@endsection
