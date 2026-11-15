@extends('layouts.app')@section('t','TSS records')@section('c')
@php($dos=auth()->user()->role==='dost')@php($trainer=auth()->user()->role==='trainer')
@if(!$class)<div class="card">No class found.</div>@else
@if($classes->count()>1)<div class="chips">@foreach($classes as $c)<a class="{{ $c->id==$class->id?'on':'' }}" href="?class={{ $c->id }}">{{ $c->name }}</a>@endforeach</div>@endif
@if($dos)
<section class="card tss-module-manager">
    <div class="tss-page-heading"><div><p class="dash-label">CLASS SETUP</p><h3>Subjects · {{ $class->name }}</h3><p class="mu">Add a subject, rename one, or remove a subject that has no saved marks or submitted work.</p></div></div>
    <form method="post" action="/marks/module" class="tss-add-module">@csrf
        <input type="hidden" name="class_id" value="{{ $class->id }}">
        <input type="hidden" name="context" value="tss">
        <label for="tss-new-module">Add a subject</label>
        <div><input id="tss-new-module" name="name" maxlength="80" placeholder="For example, PHP Programming" required><button class="btn">Add subject</button></div>
    </form>
    @if($mods->isNotEmpty())
    <div class="tss-module-table">
        <table><thead><tr><th>Subject</th><th>Assigned trainer</th><th>Actions</th></tr></thead><tbody>
        @foreach($mods as $module)
            <tr><td><form method="post" action="/marks/modules/{{ $module->id }}" class="tss-module-edit">@csrf<label class="tss-sr-only" for="module-name-{{ $module->id }}">Subject name</label><input id="module-name-{{ $module->id }}" name="name" value="{{ $module->name }}" maxlength="80" required><button class="btn navy">Save</button></form></td><td>{{ $module->trainer?->name ?? 'Not assigned' }}</td><td><form method="post" action="/marks/modules/{{ $module->id }}" onsubmit="return confirm('Delete this subject? Its outcomes and unsubmitted subject work will also be removed.')">@csrf @method('DELETE')<button class="tss-delete">Delete</button></form></td></tr>
        @endforeach
        </tbody></table>
    </div>
    @else
    <p class="tss-empty">No subjects have been added for this class yet.</p>
    @endif
</section>
@endif
<section class="card tss-outcomes">
    <div class="tss-page-heading"><div><p class="dash-label">LEARNING PROGRESS</p><h3>Learning outcomes · {{ $class->name }}</h3><p class="mu">{{ $dos?'Add and maintain each module’s learning outcomes. The assigned trainer records when an outcome is covered.':'Review your assigned learning outcomes and mark them covered as you teach.' }}</p></div></div>
    @forelse($mods as $m)
        @php($os=$out[$m->id]??collect())
        @php($cv=$os->where('covered',true)->count())
        <section class="tss-outcome-module">
            <header><div><h4>{{ $m->name }}</h4><span>{{ $cv }} of {{ $os->count() }} outcomes covered</span></div><div class="tss-outcome-progress" role="progressbar" aria-label="{{ $m->name }} outcomes covered" aria-valuenow="{{ $os->count()?round($cv/$os->count()*100):0 }}" aria-valuemin="0" aria-valuemax="100"><i style="--w:{{ $os->count()?round($cv/$os->count()*100):0 }}%"></i></div></header>
            @if($os->isNotEmpty())
            <div class="tss-outcome-table"><table><thead><tr><th>Learning outcome</th><th>Status</th><th>{{ $dos?'Manage':'Update' }}</th></tr></thead><tbody>
                @foreach($os as $o)<tr><td>@if($dos)<form method="post" action="/tss/outcomes/{{ $o->id }}" class="row" style="margin:0">@csrf<input name="title" value="{{ $o->title }}" maxlength="200" required><button class="btn navy">Save</button></form>@else{{ $o->title }}@endif</td><td>@if($o->covered)<span class="b ok">Covered{{ $o->covered_on?' · '.$o->covered_on->format('d M'):'' }}</span>@else<span class="b warn">In progress</span>@endif</td><td>@if($trainer && (int)$m->trainer_id === auth()->id())<form method="post" action="/tss/outcomes/{{ $o->id }}/toggle">@csrf<button>{{ $o->covered?'Mark in progress':'Mark covered' }}</button></form>@elseif($dos)<form method="post" action="/tss/outcomes/{{ $o->id }}" onsubmit="return confirm('Delete this learning outcome?')">@csrf @method('DELETE')<button class="tss-delete">Delete</button></form>@endif</td></tr>@endforeach
            </tbody></table></div>
            @else
            <p class="tss-empty">No learning outcomes have been added to this module yet.</p>
            @endif
            @if($dos)<form method="post" action="/tss/outcomes" class="tss-add-outcome">@csrf<input type="hidden" name="module_id" value="{{ $m->id }}"><label for="outcome-title-{{ $m->id }}">Add an outcome</label><div><input id="outcome-title-{{ $m->id }}" name="title" maxlength="200" placeholder="Describe a skill or learning outcome" required><button class="btn navy">Add outcome</button></div></form>@endif
        </section>
    @empty
        <p class="tss-empty">No modules yet for this class. @if($dos)Add a subject above to get started.@endif</p>
    @endforelse
</section>
<div class="card"><h3>Practical assessments and competency</h3>@if($pa->isEmpty())<p class="mu">No saved practical tests yet. Create a test of type "Practical" in Marks and assessment.</p>@else<table><tr><th>Student</th>@foreach($pa as $a)<th>{{ $a->module->name }}<br><small>{{ $a->title }} /{{ $a->max }}</small></th>@endforeach</tr>@foreach($students as $s)@php($m2=($sc[$s->id]??collect())->keyBy('assessment_id'))<tr><td><b>{{ $s->name }}</b></td>@foreach($pa as $a)@php($v=$m2[$a->id]->score??null)<td>@if($v===null) — @else<span class="b {{ $v/$a->max>=.7?'ok':'bad' }}">{{ rtrim(rtrim(number_format($v,1),'0'),'.') }} · {{ $v/$a->max>=.7?'Competent':'Not yet' }}</span>@endif</td>@endforeach</tr>@endforeach</table>@endif</div>
@if($class->section==='tvet')<div class="card"><h3>Industrial attachment records</h3>@if($dos)<form method="post" action="/tss/attachments" class="toolbar" style="margin-bottom:20px">@csrf<div><label>Student</label><select name="student_id">@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div><div><label>Company</label><input name="company" required></div><div><label>Supervisor</label><input name="supervisor"></div><div><label>Start</label><input type="date" name="start" value="{{ today()->toDateString() }}"></div><div><label>End</label><input type="date" name="end"></div><div><label>Status</label><select name="status"><option>Planned</option><option>Ongoing</option><option>Completed</option></select></div><button class="btn">Add</button></form>@endif
<table><tr><th>Student</th><th>Company</th><th>Supervisor</th><th>Period</th><th>Status</th><th>Report</th>@if($dos)<th></th>@endif</tr>@forelse($att as $x)<tr><td><b>{{ $x->student->name }}</b></td><td>{{ $x->company }}</td><td>{{ $x->supervisor }}</td><td>{{ $x->start->format('d M') }}@if($x->end) – {{ $x->end->format('d M Y') }}@endif</td><td><span class="b {{ ['Planned'=>'info','Ongoing'=>'warn','Completed'=>'ok'][$x->status] }}">{{ $x->status }}</span></td><td>{{ $x->report?'✓ Submitted':'—' }}</td>@if($dos)<td><form method="post" action="/tss/attachments/{{ $x->id }}" class="row" style="margin:0">@csrf<select name="status">@foreach(['Planned','Ongoing','Completed'] as $st)<option @selected($x->status==$st)>{{ $st }}</option>@endforeach</select><label><input type="checkbox" name="report" value="1" @checked($x->report)> Report</label><button>Save</button></form><form method="post" action="/tss/attachments/{{ $x->id }}" onsubmit="return confirm('Delete this record?')" style="margin-top:6px">@csrf @method('DELETE')<button>Delete</button></form></td>@endif</tr>@empty<tr><td colspan="7" class="mu">No attachments recorded.</td></tr>@endforelse</table></div>@endif
@endif
@endsection
