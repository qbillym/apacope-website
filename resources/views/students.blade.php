@extends('layouts.app')
@section('t', 'Students')
@section('c')
@if($sel)
    <div class="card">
        <h3>{{ $sel->name }} <span class="b ok">{{ $sel->status }}</span></h3>
        <p>Registration no: <b>{{ $sel->admission_no }}</b> · SDMS no: <b>{{ $sel->sdms_no ?: 'Not recorded' }}</b> · Class: <b>{{ $sel->schoolClass->name }}</b> · {{ $sel->level() }} · {{ $sel->trade() }}</p>
        <form method="post" action="/students/{{ $sel->id }}">
            @csrf
            <div class="toolbar">
                <div><label>Full name</label><input name="name" value="{{ $sel->name }}" required></div>
                <div><label>SDMS number</label><input name="sdms_no" value="{{ $sel->sdms_no }}" required></div>
                <div><label>Registration number</label><input name="admission_no" value="{{ $sel->admission_no }}" required></div>
                <div><label>Enrollment status</label><select name="status">@foreach(['Enrolled','Transferred','Dropped out','Graduated'] as $x)<option @selected($sel->status === $x)>{{ $x }}</option>@endforeach</select></div>
                <div><label>Main guardian phone</label><input name="guardian_phone" value="{{ $sel->guardian_phone }}"></div>
            </div>
            <label class="mu" style="display:block;margin:16px 0 4px">Previous school and academic record</label>
            <textarea name="previous_record" rows="2">{{ $sel->previous_record }}</textarea>
            <p style="margin-top:14px"><button class="btn">Save profile</button></p>
        </form>
        <h3 style="margin-top:26px">Guardians and contacts</h3>
        <div>
            @forelse($sel->guardians as $g)
                <form method="post" action="/guardians/{{ $g->id }}" class="toolbar" style="margin:12px 0">
                    @csrf
                    <div><label>Name</label><input name="name" value="{{ $g->name }}" required></div>
                    <div><label>Relation</label><input name="relation" value="{{ $g->relation }}"></div>
                    <div><label>Phone</label><input name="phone" value="{{ $g->phone }}" required></div>
                    <button class="btn navy">Save contact</button>
                </form>
                <form method="post" action="/guardians/{{ $g->id }}" onsubmit="return confirm('Remove this contact?')">@csrf @method('DELETE')<button>Remove</button></form>
            @empty
                <p class="mu">No guardian contacts yet.</p>
            @endforelse
        </div>
        <form method="post" action="/students/{{ $sel->id }}/guardians" class="toolbar" style="margin-top:14px">
            @csrf
            <div><label>Name</label><input name="name" required></div>
            <div><label>Relation</label><input name="relation" placeholder="Mother, Father, Guardian"></div>
            <div><label>Phone</label><input name="phone" required></div>
            <button class="btn navy">Add guardian</button>
        </form>
    </div>
@endif

<form method="post" action="/students" class="card">
    @csrf
    <h3>Register a student</h3>
    <div class="toolbar">
        <div><label>Full name</label><input name="name" placeholder="Student's full name" value="{{ old('name') }}" required></div>
        <div><label>Unique SDMS number</label><input name="sdms_no" value="{{ old('sdms_no') }}" required></div>
        <div><label>Class</label><select name="school_class_id" required>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    </div>
    <h4 style="margin-top:18px">Guardian contacts</h4>
    <p class="mu">Add at least one guardian. Registration numbers are generated uniquely by the school.</p>
    <div data-guardian-list>
        <div class="toolbar guardian-row" data-guardian-row>
            <div><label>Guardian name</label><input name="guardians[0][name]" required></div>
            <div><label>Relationship</label><input name="guardians[0][relation]" placeholder="Mother, Father, Guardian"></div>
            <div><label>Phone</label><input name="guardians[0][phone]" required></div>
        </div>
    </div>
    <button type="button" data-add-guardian>Add another guardian</button>
    <p style="margin-top:14px"><button class="btn">Register student</button></p>
</form>

<div class="card">
    <h3>Students in your section</h3>
    <form method="get" class="row">
        <input name="q" list="student-suggestions" value="{{ request('q') }}" placeholder="Search by name, SDMS or registration number" style="flex:1;min-width:220px">
        <datalist id="student-suggestions">@foreach($rows as $s)<option value="{{ $s->name }}">{{ $s->sdms_no }} · {{ $s->admission_no }} · {{ $s->schoolClass->name }}</option><option value="{{ $s->sdms_no }}">{{ $s->name }} · {{ $s->schoolClass->name }}</option><option value="{{ $s->admission_no }}">{{ $s->name }} · {{ $s->schoolClass->name }}</option>@endforeach</datalist>
        <button class="btn navy">Search</button>
    </form>
    <table>
        <tr><th>Registration no</th><th>SDMS no</th><th>Name</th><th>Class</th><th>Guardians</th><th>Main phone</th><th></th></tr>
        @forelse($rows as $s)
            <tr><td>{{ $s->admission_no }}</td><td>{{ $s->sdms_no ?: '—' }}</td><td>{{ $s->name }}</td><td>{{ $s->schoolClass->name }}</td><td>{{ $s->guardians_count ?? $s->guardians->count() }}</td><td>{{ $s->guardian_phone }}</td><td><a href="?show={{ $s->id }}">Open profile</a></td></tr>
        @empty
            <tr><td colspan="7" class="mu">No students found.</td></tr>
        @endforelse
    </table>
</div>
@endsection
