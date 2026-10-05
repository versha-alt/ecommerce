<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
class AdminSession {
 public function handle(Request $r,Closure $next) {
  $token=$r->bearerToken();
  $session=$token?DB::table('admin_tokens')->where('token',hash('sha256',$token))->where('expires_at','>',now())->first():null;
  $user=$session?User::find($session->user_id):null;
  if(!$user||$user->status!=='Active')return response()->json(['message'=>'Your session has expired. Please sign in.'],401);
  $r->setUserResolver(fn()=>$user);
  return $next($r);
 }
}
