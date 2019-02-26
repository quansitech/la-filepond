<?php
namespace Qs\La\Filepond\Middleware;

use Closure;

class RegisterFilter{

    public function handle($request, Closure $next){

        return $next($request);
    }
}