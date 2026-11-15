<?php
namespace App\Http\Middleware;
use Closure;use App\Services\Perm;
class FeatureAccess{public function handle($r,Closure $next){$u=$r->user();$f=$r->segment(1);
if($u&&$u->role!=='admin'&&$f&&Perm::denied($u->role,$f))abort(403,'The IT administrator has switched this feature off for your role.');return $next($r);}}
