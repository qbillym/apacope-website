<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\MediaItem;
use App\Models\Module;
use App\Models\Portfolio;
use App\Models\Resource;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    private function inScope($u, $sid)
    {
        $s = Student::find($sid);

        return $s && $u->classes()->where('id', $s->school_class_id)->exists();
    }

    public function show(Request $r, $kind, $id)
    {
        $u = $r->user();
        $path = null;
        $ok = false;
        if ($kind === 'material') {
            $m = Resource::findOrFail($id);
            $path = $m->path;
            $ok = $u->role === 'head' || $m->user_id === $u->id || $u->classes()->where('id', $m->school_class_id)->exists();
            if ($u->role === 'trainer') {
                $ok = $m->user_id === $u->id || Module::where('school_class_id', $m->school_class_id)->where('name', $m->module)->where('trainer_id', $u->id)->exists() || $u->isClassTeacherFor($m->school_class_id);
            }
        } elseif ($kind === 'media') {
            $m = MediaItem::findOrFail($id);
            $path = $m->path;
            $ok = app(MediaController::class)->allowed($u, $m);
        } elseif ($kind === 'portfolio') {
            $m = Portfolio::findOrFail($id);
            $path = $m->path;
            $student = $m->student_id ? Student::find($m->student_id) : null;
            $ok = $u->role === 'head' || $m->user_id === $u->id || ($student && ($u->student_id == $student->id || ($u->role === 'trainer' ? $u->isClassTeacherFor($student->school_class_id) : $this->inScope($u, $student->id))));
        } elseif ($kind === 'submission') {
            $m = Submission::findOrFail($id);
            $path = $m->path;
            $a = Assignment::find($m->assignment_id);
            $ok = $u->role === 'head' || (in_array($u->role, ['student', 'parent']) && $u->student_id == $m->student_id) || ($a && $u->classes()->where('id', $a->school_class_id)->exists());
            if ($u->role === 'trainer') {
                $ok = $a && (Module::where('school_class_id', $a->school_class_id)->where('name', $a->module)->where('trainer_id', $u->id)->exists() || $u->isClassTeacherFor($a->school_class_id));
            }
        } else {
            abort(404);
        }
        abort_unless($ok && $path, 403);
        foreach (['local', 'public'] as $d) {
            if (Storage::disk($d)->exists($path)) {
                return response()->file(Storage::disk($d)->path($path));
            }
        }
        abort(404);
    }
}
