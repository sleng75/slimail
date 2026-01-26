<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Display API settings and keys.
     */
    public function index(): Response
    {
        $tenantId = auth()->user()->tenant_id;

        $apiKeys = ApiKey::where('tenant_id', $tenantId)
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'key_preview' => $key->key_preview,
                'permissions' => $key->permissions,
                'ip_whitelist' => $key->ip_whitelist,
                'rate_limit' => $key->rate_limit,
                'is_active' => $key->is_active,
                'last_used_at' => $key->last_used_at?->format('d/m/Y H:i'),
                'expires_at' => $key->expires_at?->format('d/m/Y'),
                'created_at' => $key->created_at->format('d/m/Y'),
                'creator' => $key->creator?->name,
                'usage_count' => $key->requests_count,
            ]);

        return Inertia::render('Api/Index', [
            'apiKeys' => $apiKeys,
            'availablePermissions' => ApiKey::AVAILABLE_PERMISSIONS,
        ]);
    }

    /**
     * Store a new API key.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(ApiKey::AVAILABLE_PERMISSIONS)),
            'ip_whitelist' => 'nullable|string',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
            'expires_at' => 'nullable|date|after:today',
        ]);

        // Parse IP whitelist
        $ipWhitelist = null;
        if (!empty($validated['ip_whitelist'])) {
            $ips = array_filter(array_map('trim', explode(',', $validated['ip_whitelist'])));
            $ipWhitelist = array_values(array_filter($ips, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP)));
        }

        // Generate unique key
        $key = 'slm_' . Str::random(32);

        $apiKey = ApiKey::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'key' => $key,
            'permissions' => $validated['permissions'],
            'ip_whitelist' => $ipWhitelist,
            'rate_limit' => $validated['rate_limit'] ?? 100,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('api-settings.index')
            ->with('success', 'Clé API créée avec succès.')
            ->with('newApiKey', $key);
    }

    /**
     * Update an API key.
     */
    public function update(Request $request, ApiKey $apiKey): RedirectResponse
    {
        // Ensure the key belongs to the tenant
        if ($apiKey->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(ApiKey::AVAILABLE_PERMISSIONS)),
            'ip_whitelist' => 'nullable|string',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
            'expires_at' => 'nullable|date|after:today',
            'is_active' => 'boolean',
        ]);

        // Parse IP whitelist
        $ipWhitelist = null;
        if (!empty($validated['ip_whitelist'])) {
            $ips = array_filter(array_map('trim', explode(',', $validated['ip_whitelist'])));
            $ipWhitelist = array_values(array_filter($ips, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP)));
        }

        $apiKey->update([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'],
            'ip_whitelist' => $ipWhitelist,
            'rate_limit' => $validated['rate_limit'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? $apiKey->is_active,
        ]);

        return redirect()->route('api-settings.index')
            ->with('success', 'Clé API mise à jour.');
    }

    /**
     * Delete an API key.
     */
    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        // Ensure the key belongs to the tenant
        if ($apiKey->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $apiKey->delete();

        return redirect()->route('api-settings.index')
            ->with('success', 'Clé API supprimée.');
    }

    /**
     * Regenerate an API key.
     */
    public function regenerate(ApiKey $apiKey): RedirectResponse
    {
        // Ensure the key belongs to the tenant
        if ($apiKey->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $newKey = 'slm_' . Str::random(32);
        $apiKey->update(['key' => $newKey]);

        return redirect()->route('api-settings.index')
            ->with('success', 'Clé API régénérée.')
            ->with('newApiKey', $newKey);
    }

    /**
     * Toggle API key status.
     */
    public function toggle(ApiKey $apiKey): RedirectResponse
    {
        // Ensure the key belongs to the tenant
        if ($apiKey->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $apiKey->update(['is_active' => !$apiKey->is_active]);

        $status = $apiKey->is_active ? 'activée' : 'désactivée';

        return redirect()->route('api-settings.index')
            ->with('success', "Clé API {$status}.");
    }
}
