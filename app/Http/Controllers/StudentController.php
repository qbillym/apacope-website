<?php

namespace App\Http\Controllers;

use App\Models\{Fee,Guardian,Student};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['dosb', 'dost'], true), 403);

        $classIds = $user->classes()->pluck('id');
        $students = Student::with('schoolClass')->withCount('guardians')
            ->whereIn('school_class_id', $classIds)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($students) => $students
                    ->where('name', 'like', $term)
                    ->orWhere('admission_no', 'like', $term)
                    ->orWhere('sdms_no', 'like', $term));
            })
            ->orderBy('name')
            ->get();

        return view('students', [
            'rows' => $students,
            'sel' => $request->filled('show')
                ? Student::with(['schoolClass', 'guardians'])->withCount('guardians')->whereIn('school_class_id', $classIds)->find($request->integer('show'))
                : null,
            'classes' => $user->classes()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['dosb', 'dost'], true)
            && $user->classes()->whereKey($student->school_class_id)->exists(),
            403
        );

        $request->merge([
            'sdms_no' => trim((string) $request->input('sdms_no')),
            'admission_no' => trim((string) $request->input('admission_no')),
        ]);
        $values = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sdms_no' => ['required', 'string', 'max:50', Rule::unique('students', 'sdms_no')->ignore($student->id)],
            'admission_no' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_no')->ignore($student->id)],
            'status' => ['required', Rule::in(['Enrolled', 'Transferred', 'Dropped out', 'Graduated'])],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'previous_record' => ['nullable', 'string', 'max:1000'],
        ]);

        $student->update($values);

        return back()->with('ok', 'Student profile updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['dosb', 'dost'], true), 403);

        $request->merge(['sdms_no' => trim((string) $request->input('sdms_no'))]);
        $values = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sdms_no' => ['required', 'string', 'max:50', 'unique:students,sdms_no'],
            'school_class_id' => ['required', 'integer'],
            'guardians' => ['required', 'array', 'min:1', 'max:5'],
            'guardians.*.name' => ['required', 'string', 'max:100'],
            'guardians.*.relation' => ['nullable', 'string', 'max:40'],
            'guardians.*.phone' => ['required', 'string', 'max:30'],
        ]);

        $schoolClass = $user->classes()->findOrFail($values['school_class_id']);
        $student = DB::transaction(function () use ($values, $schoolClass): Student {
            $student = Student::create([
                'name' => $values['name'],
                'sdms_no' => trim($values['sdms_no']),
                'school_class_id' => $schoolClass->id,
                'admission_no' => 'APA-TEMP-'.bin2hex(random_bytes(12)),
                'guardian_phone' => $values['guardians'][0]['phone'],
            ]);
            $student->update([
                'admission_no' => sprintf('APA-%s-%05d', preg_replace('/\s+/', '', $schoolClass->name), $student->id),
            ]);

            foreach ($values['guardians'] as $guardian) {
                $student->guardians()->create($guardian);
            }

            Fee::create(['student_id' => $student->id, 'invoice' => 120000, 'paid' => 0]);

            return $student;
        });

        return redirect('/students?show='.$student->id)->with('ok', 'Student registered. Registration number: '.$student->admission_no);
    }

    public function guardian(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['dosb', 'dost'], true)
            && $user->classes()->whereKey($student->school_class_id)->exists(),
            403
        );

        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'relation' => ['nullable', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $student->guardians()->create($values);

        return back()->with('ok', 'Guardian added.');
    }

    public function guardianDelete(Request $request, Guardian $guardian): RedirectResponse
    {
        $user = $request->user();
        $student = Student::findOrFail($guardian->student_id);
        abort_unless(
            in_array($user->role, ['dosb', 'dost'], true)
            && $user->classes()->whereKey($student->school_class_id)->exists(),
            403
        );

        $guardian->delete();

        return back()->with('ok', 'Guardian removed.');
    }

    public function guardianUpdate(Request $request, Guardian $guardian): RedirectResponse
    {
        $user = $request->user();
        $student = Student::findOrFail($guardian->student_id);
        abort_unless(
            in_array($user->role, ['dosb', 'dost'], true)
            && $user->classes()->whereKey($student->school_class_id)->exists(),
            403
        );

        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'relation' => ['nullable', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $guardian->update($values);

        return back()->with('ok', 'Guardian contact updated.');
    }
}
