<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The required permission (e.g., 'contacts.manage')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->handleUnauthorized($request, 'Vous devez être connecté.');
        }

        // Check permission - if it ends with .view, use canView() method
        // which also allows .manage permission
        $hasPermission = false;
        if (str_ends_with($permission, '.view')) {
            $resource = str_replace('.view', '', $permission);
            $hasPermission = $user->canView($resource);
        } else {
            $hasPermission = $user->hasPermission($permission);
        }

        if (!$hasPermission) {
            return $this->handleUnauthorized(
                $request,
                'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.'
            );
        }

        return $next($request);
    }

    /**
     * Handle unauthorized response.
     */
    protected function handleUnauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => $message,
            ], 403);
        }

        return redirect()->route('dashboard')->with('error', $message);
    }
}
