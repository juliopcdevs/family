<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFamilyMembership
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->hasFamily()) {
            return response()->json([
                'message' => 'You must belong to a family',
            ], 403);
        }

        return $next($request);
    }
}
