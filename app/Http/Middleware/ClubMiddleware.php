<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClubMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->role === 'club') {
            return $next($request);
        }

        \Log::warning('ClubMiddleware 403', [
            'user_id' => optional($request->user())->id,
            'user_email' => optional($request->user())->email,
            'role' => optional($request->user())->role,
            'has_bearer' => (bool) $request->bearerToken(),
            'path' => $request->path(),
        ]);

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
