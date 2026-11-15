<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Module;
use App\Models\Resource;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    private function canAccessResource(User $user, Resource $resource): bool
    {
        if ($user->role === 'trainer') {
            return Module::where('school_class_id', $resource->school_class_id)
                ->where('name', $resource->module)
                ->where('trainer_id', $user->id)
                ->exists() || $user->isClassTeacherFor($resource->school_class_id);
        }

        return $user->classes()->whereKey($resource->school_class_id)->exists();
    }

    public function index(Request $r)
    {
        $u = $r->user();
        abort_unless(in_array($u->role, ['trainer', 'student']), 403);
        $classTeacherIds = SchoolClass::where('trainer_id', $u->id)->select('id');
        $fav = DB::table('bookmarks')->where('user_id', $u->id)->pluck('resource_id')->all();
        $q = Resource::with('schoolClass', 'user', 'comments.user')->whereIn('school_class_id', $u->classes()->pluck('id'))->latest();
        if ($u->role === 'trainer') {
            $q->whereExists(fn ($modules) => $modules->selectRaw('1')->from('modules')->whereColumn('modules.school_class_id', 'resources.school_class_id')->whereColumn('modules.name', 'resources.module')->where(fn ($scope) => $scope->where('modules.trainer_id', $u->id)->orWhereIn('modules.school_class_id', $classTeacherIds)));
        }
        if ($u->role === 'student') {
            $q->where(fn ($w) => $w->whereNull('publish_at')->orWhere('publish_at', '<=', today()));
        }
        if ($r->q) {
            $q->where('title', 'like', "%{$r->q}%");
        }
        if ($r->module) {
            $q->where('module', $r->module);
        }
        if ($r->fav) {
            $q->whereIn('id', $fav ?: [0]);
        }

        return view('materials', ['list' => $q->get()->each(fn ($m) => $m->size = $m->path ? rescue(fn () => round(Storage::disk('public')->size($m->path) / 1048576, 1), null) : null), 'can' => $u->role === 'trainer', 'viewers' => DB::table('resource_views')->join('users', 'users.id', '=', 'resource_views.user_id')->where('users.role', 'student')->get(['resource_views.resource_id as r', 'users.name as n'])->groupBy('r'), 'sizes' => Student::selectRaw('school_class_id as c, count(*) as n')->groupBy('school_class_id')->pluck('n', 'c'), 'classes' => $u->classes()->get(), 'fav' => $fav, 'mods' => Module::whereIn('school_class_id', $u->classes()->pluck('id'))->when($u->role === 'trainer', fn ($q) => $q->where(fn ($scope) => $scope->where('trainer_id', $u->id)->orWhereIn('school_class_id', $classTeacherIds)))->pluck('name')->unique()->values()]);
    }

    public function store(Request $r)
    {
        $u = $r->user();
        abort_unless($u->role === 'trainer', 403);
        $v = $r->validate(['title' => 'required|max:150', 'type' => 'required', 'module' => 'required', 'link' => 'nullable|url', 'school_class_id' => 'required|integer', 'description' => 'nullable|max:1000', 'outcome' => 'nullable|max:120', 'publish_at' => 'nullable|date', 'file' => 'nullable|file|max:102400|mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png,txt,mp4,webm,mov']);
        $c = $u->classes()->findOrFail($v['school_class_id']);
        $isAssignedModule = Module::where('school_class_id', $c->id)->where('name', $v['module'])->where('trainer_id', $u->id)->exists();
        abort_unless($isAssignedModule || $u->isClassTeacherFor($c->id), 422, 'Choose a subject assigned to you.');
        unset($v['file']);
        if ($r->hasFile('file')) {
            $v['path'] = $r->file('file')->store('resources', 'local');
        }
        if (empty($v['link']) && empty($v['path'])) {
            return back()->with('err', 'Add a link or choose a file.')->withInput();
        }
        Resource::create($v + ['user_id' => $u->id]);
        if (empty($v['publish_at']) || $v['publish_at'] <= today()->toDateString()) {
            Announcement::create(['user_id' => $u->id, 'title' => 'New learning material: '.$v['title'], 'body' => $v['module'].' · '.$v['type'].'. Open Learning materials to see it.', 'audience' => $c->name]);
        }

        return back()->with('ok', empty($v['publish_at']) ? 'Published. The class has been notified.' : 'Scheduled. Students will see it from '.$v['publish_at'].'.');
    }

    public function open(Request $r, Resource $resource)
    {
        $u = $r->user();
        abort_unless($u->role === 'head' || $this->canAccessResource($u, $resource), 403);
        $resource->increment('views');
        $w = ['resource_id' => $resource->id, 'user_id' => $u->id];
        $row = DB::table('resource_views')->where($w)->first();
        if ($row) {
            DB::table('resource_views')->where($w)->update(['views' => $row->views + 1, 'updated_at' => now()]);
        } else {
            DB::table('resource_views')->insert($w + ['views' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        return redirect($resource->link ?: url('/files/material/'.$resource->id));
    }

    public function bookmark(Request $r, Resource $resource)
    {
        $user = $r->user();
        abort_unless($this->canAccessResource($user, $resource), 403);
        $w = ['user_id' => $user->id, 'resource_id' => $resource->id];
        if (DB::table('bookmarks')->where($w)->exists()) {
            DB::table('bookmarks')->where($w)->delete();
        } else {
            DB::table('bookmarks')->insert($w);
        }

        return back();
    }

    public function update(Request $r, Resource $resource)
    {
        abort_unless($r->user()->id === $resource->user_id, 403);
        $v = $r->validate(['title' => 'required|max:150', 'description' => 'nullable|max:1000', 'outcome' => 'nullable|max:120', 'link' => 'nullable|url', 'file' => 'nullable|file|max:102400|mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png,txt,mp4,webm,mov']);
        unset($v['file']);
        if ($r->hasFile('file')) {
            $v['path'] = $r->file('file')->store('resources', 'local');
            $v['link'] = null;
        } elseif (! empty($v['link'])) {
            $v['path'] = null;
        } else {
            unset($v['link']);
        }
        $resource->update($v);

        return back()->with('ok', 'Material updated.');
    }

    public function destroy(Request $r, Resource $resource)
    {
        abort_unless($r->user()->id === $resource->user_id, 403);
        $resource->delete();

        return back()->with('ok', 'Removed.');
    }

    public function comment(Request $r, Resource $resource)
    {
        $user = $r->user();
        abort_unless($this->canAccessResource($user, $resource), 403);
        $v = $r->validate(['body' => 'required|max:500']);
        Comment::create(['resource_id' => $resource->id, 'user_id' => $user->id, 'body' => $v['body']]);

        return back()->with('ok', 'Comment added.');
    }

    public function deleteComment(Request $r, Comment $comment)
    {
        $res = Resource::find($comment->resource_id);
        abort_unless($comment->user_id === $r->user()->id || ($res && $res->user_id === $r->user()->id), 403);
        $comment->delete();

        return back()->with('ok', 'Comment removed.');
    }
}
