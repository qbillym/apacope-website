<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index(Request $r)
    {
        $u = $r->user();
        abort_unless(in_array($u->role, ['trainer', 'student', 'dosb', 'dost']), 403);
        $ids = $u->classes()->pluck('id');
        $sid = Student::whereIn('school_class_id', $ids)->pluck('id');
        $mine = collect();
        $trainees = collect();
        $staff = collect();
        if ($u->role === 'student') {
            $mine = Portfolio::where('kind', 'student')->where('student_id', $u->student_id)->latest()->get();
        }
        if ($u->role === 'trainer') {
            $mine = Portfolio::where('kind', 'trainer')->where('user_id', $u->id)->latest()->get();
            $classTeacherIds = SchoolClass::where('trainer_id', $u->id)->pluck('id');
            $classTeacherStudentIds = Student::whereIn('school_class_id', $classTeacherIds)->pluck('id');
            $trainees = Portfolio::with('student')->where('kind', 'student')->whereIn('student_id', $classTeacherStudentIds)->latest()->get();
        }
        if (in_array($u->role, ['dosb', 'dost'])) {
            $staff = Portfolio::with('owner')->where('kind', 'trainer')->whereIn('user_id', $u->classes()->pluck('trainer_id')->filter())->latest()->get();
            $trainees = Portfolio::with('student')->where('kind', 'student')->whereIn('student_id', $sid)->latest()->get();
        }

        return view('portfolio', ['mine' => $mine, 'trainees' => $trainees, 'staff' => $staff, 'students' => $u->role === 'trainer' ? Student::whereIn('school_class_id', SchoolClass::where('trainer_id', $u->id)->select('id'))->orderBy('name')->get() : collect()]);
    }

    public function store(Request $r)
    {
        $u = $r->user();
        abort_unless(in_array($u->role, ['trainer', 'student']), 403);
        $v = $r->validate(['title' => 'required|max:150', 'description' => 'nullable|max:800', 'category' => 'nullable|max:60', 'link' => 'nullable|url', 'for' => 'nullable', 'file' => 'nullable|file|max:20480|mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png,mp4']);
        unset($v['file'],$v['for']);
        if ($r->hasFile('file')) {
            $v['path'] = $r->file('file')->store('portfolios', 'local');
        }if (empty($v['link']) && empty($v['path'])) {
            return back()->with('err', 'Add a link or choose a file.')->withInput();
        }
        if ($u->role === 'student') {
            $v += ['kind' => 'student', 'student_id' => $u->student_id, 'user_id' => $u->id];
        } elseif ($r->for && $r->for !== 'me') {
            $s = Student::findOrFail((int) $r->for);
            abort_unless($u->isClassTeacherFor($s->school_class_id), 403);
            $v += ['kind' => 'student', 'student_id' => $s->id, 'user_id' => $u->id];
        } else {
            $v += ['kind' => 'trainer', 'user_id' => $u->id];
        }
        Portfolio::create($v);

        return back()->with('ok', 'Added to the portfolio.');
    }

    public function destroy(Request $r, Portfolio $portfolio)
    {
        abort_unless($portfolio->user_id === $r->user()->id, 403);
        $portfolio->delete();

        return back()->with('ok','Removed.');
    }
}
