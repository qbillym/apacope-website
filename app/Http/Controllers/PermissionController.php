<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\RoleDeny;
use App\Services\Menu;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    const ROLES = ['head' => 'Head Teacher', 'dosb' => 'DOS · N–S3', 'dost' => 'DOS · TVET', 'trainer' => 'Trainer', 'student' => 'Student', 'parent' => 'Parent', 'accountant' => 'Accountant', 'storekeeper' => 'Storekeeper'];

    private function feats()
    {
        $f = [];
        foreach (Menu::items() as $m) {
            if ($m[4] === ['admin'] || in_array($m[2], ['/home', '/account'], true)) {
                continue;
            }$f[trim($m[2], '/')] = ['label' => $m[1], 'roles' => $m[4]];
        }

return $f;
    }

    public function index(Request $r)
    {
        abort_unless($r->user()->role === 'admin', 403);

        return view('permissions', ['feats' => $this->feats(), 'roles' => self::ROLES, 'den' => RoleDeny::all()->groupBy('role')->map(fn ($g) => $g->pluck('feature')->all())->all()]);
    }

    public function save(Request $r)
    {
        abort_unless($r->user()->role === 'admin', 403);
        $al = (array) $r->allow;
        RoleDeny::query()->delete();
        $n = 0;
        foreach ($this->feats() as $k => $f) {
            foreach (array_keys(self::ROLES) as $role) {
                $def = ! $f['roles'] || in_array($role, $f['roles']);
                if ($def && empty($al[$role][$k])) {
                    RoleDeny::create(['role' => $role, 'feature' => $k]);
                    $n++;
                }
            }
        }
        Audit::log('Permissions changed', $n.' feature(s) switched off');

        return back()->with('ok', 'Permissions saved. '.$n.' feature(s) are switched off.');
    }
}
