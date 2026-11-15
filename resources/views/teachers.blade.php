@extends('layouts.app')
@section('t', 'Teachers and subjects')
@section('c')
<div class="card">
    <h3>Create a trainer account</h3>
    <p class="mu">Create a new teacher login here. To use an existing teacher, select them in the subject or class-teacher assignments below; one account can teach across multiple classes and school sections.</p>
    @if($modules->isEmpty())
        <p class="mu">Add a subject on the Marks &amp; assessment page before creating a trainer account.</p>
    @else
        <form method="post" action="/teachers">
            @csrf
            <div class="toolbar">
                <div><label>Full name</label><input name="name" required></div>
                <div><label>Email</label><input type="email" name="email" required></div>
                <div><label>First subject and class</label><select name="module_id" required>@foreach($modules as $module)<option value="{{ $module->id }}">{{ $module->schoolClass->name }} · {{ $module->name }}</option>@endforeach</select></div>
                <label><input type="checkbox" name="class_teacher" value="1"> Also class teacher</label>
                <button class="btn">Create trainer</button>
            </div>
        </form>
    @endif
</div>

<div class="card">
    <h3>Class teachers</h3>
    <table>
        <tr><th>Class</th><th>Class teacher</th><th></th></tr>
        @foreach($classes as $class)
            <tr>
                <td><b>{{ $class->name }}</b></td>
                <td colspan="2">
                    <form method="post" action="/teachers/classes" class="row" style="margin:0">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $class->id }}">
                        <select name="trainer_id">
                            <option value="">No class teacher</option>
                            @foreach($trainers as $trainer)
                                <option value="{{ $trainer->id }}" @selected($class->trainer_id === $trainer->id)>{{ $trainer->name }}@if($trainer->modules->isNotEmpty()) · {{ $trainer->modules->map(fn($module) => $module->schoolClass->name.' · '.$module->name)->join(', ') }}@endif</option>
                            @endforeach
                        </select>
                        <button>Save</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
</div>

<div class="card">
    <h3>Subject teachers</h3>
    <table>
        <tr><th>Class</th><th>Subject</th><th>Assigned teacher</th><th></th></tr>
        @foreach($modules as $module)
            <tr>
                <td>{{ $module->schoolClass->name }}</td>
                <td><b>{{ $module->name }}</b></td>
                <td colspan="2">
                    <form method="post" action="/teachers/assignments" class="row" style="margin:0">
                        @csrf
                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                        <select name="trainer_id">
                            <option value="">Unassigned</option>
                            @foreach($trainers as $trainer)
                                <option value="{{ $trainer->id }}" @selected($module->trainer_id === $trainer->id)>{{ $trainer->name }}@if($trainer->modules->isNotEmpty()) · {{ $trainer->modules->map(fn($assignment) => $assignment->schoolClass->name.' · '.$assignment->name)->join(', ') }}@endif</option>
                            @endforeach
                        </select>
                        <button>Save assignment</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
</div>

<div class="card">
    <h3>Trainer accounts in your section</h3>
    @forelse($visibleTrainers as $trainer)
        <details class="itm">
            <summary>{{ $trainer->name }} <span class="mu">· {{ $trainer->email }}</span></summary>
            <form method="post" action="/teachers/{{ $trainer->id }}" class="toolbar">
                @csrf
                <div><label>Full name</label><input name="name" value="{{ $trainer->name }}" required></div>
                <div><label>Email</label><input type="email" name="email" value="{{ $trainer->email }}" required></div>
                <button class="btn">Save account</button>
            </form>
            <p class="mu">Assigned subjects: {{ $trainer->modules->whereIn('school_class_id', $classes->pluck('id'))->map(fn($module) => $module->schoolClass->name.' · '.$module->name)->join(', ') ?: 'None' }}</p>
        </details>
    @empty
        <p class="mu">No trainers are assigned in your section yet.</p>
    @endforelse
</div>
@endsection
