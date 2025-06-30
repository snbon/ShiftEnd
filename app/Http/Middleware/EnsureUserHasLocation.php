<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasLocation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        // Allow if user is not authenticated (should be handled by auth middleware)
        if (!$user) {
            return $next($request);
        }
        // Allow if user has a location
        if ($user->location_id) {
            return $next($request);
        }
        // Allow onboarding, invitations, and logout endpoints
        $allowed = [
            'api/locations', // allow POST for onboarding
            'api/invitations',
            'api/logout',
            'api/user', // allow user info for onboarding
            'api/users/me/role', // allow role update for onboarding
            'api/register',
            'api/auth/check',
            'api/resend-verification',
        ];
        $path = $request->path();
        foreach ($allowed as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }
        return response()->json([
            'success' => false,
            'message' => 'Complete onboarding before accessing this resource.'
        ], 403);
    }
}
