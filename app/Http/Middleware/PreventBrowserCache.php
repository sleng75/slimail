<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBrowserCache
{
    /**
     * Handle an incoming request.
     *
     * Prevents browser from caching Inertia pages which can cause
     * stale content to be displayed after navigation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply to Inertia requests (both initial and XHR)
        if ($request->header('X-Inertia') || $this->isInertiaResponse($response)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * Check if the response is an Inertia response.
     */
    protected function isInertiaResponse(Response $response): bool
    {
        return $response->headers->has('X-Inertia');
    }
}
