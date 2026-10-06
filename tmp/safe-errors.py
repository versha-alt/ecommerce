from pathlib import Path
p=Path('apps/backend/bootstrap/app.php');s=p.read_text();s=s.replace("$exceptions->shouldRenderJsonWhen(fn($request,$e)=>$request->is('api/*')||$request->expectsJson());", """$exceptions->shouldRenderJsonWhen(fn($request,$e)=>$request->is('api/*')||$request->expectsJson());
 $exceptions->respond(function ($response) {
     if (request()->is('api/*') && $response->getStatusCode() >= 500) {
         return response()->json(['message' => 'The server could not complete this request. Please try again.'], $response->getStatusCode());
     }
     return $response;
 });""");p.write_text(s)
