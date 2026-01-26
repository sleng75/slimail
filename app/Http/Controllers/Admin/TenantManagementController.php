<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantManagementController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): Response
    {
        $query = Tenant::with(['owner:id,name,email', 'subscription.plan']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by subscription status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->whereHas('subscription', fn($q) => $q->where('status', $request->status));
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->whereHas('subscription', fn($q) => $q->where('plan_id', $request->plan));
        }

        // Sort
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $tenants = $query->paginate(20)->through(fn($tenant) => [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'email' => $tenant->email,
            'owner' => [
                'id' => $tenant->owner?->id,
                'name' => $tenant->owner?->name,
                'email' => $tenant->owner?->email,
            ],
            'subscription' => $tenant->subscription ? [
                'plan' => $tenant->subscription->plan?->name,
                'status' => $tenant->subscription->status,
                'ends_at' => $tenant->subscription->ends_at?->format('d/m/Y'),
            ] : null,
            'contacts_count' => $tenant->contacts()->count(),
            'campaigns_count' => $tenant->campaigns()->count(),
            'emails_sent' => $tenant->sentEmails()->count(),
            'created_at' => $tenant->created_at->format('d/m/Y'),
        ]);

        $plans = Plan::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'plans' => $plans,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'plan' => $request->plan,
            ],
        ]);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load(['owner', 'subscription.plan', 'users']);

        // Get tenant statistics
        $stats = [
            'contacts' => [
                'total' => $tenant->contacts()->count(),
                'subscribed' => $tenant->contacts()->where('status', 'subscribed')->count(),
                'unsubscribed' => $tenant->contacts()->where('status', 'unsubscribed')->count(),
            ],
            'campaigns' => [
                'total' => $tenant->campaigns()->count(),
                'sent' => $tenant->campaigns()->where('status', 'sent')->count(),
                'draft' => $tenant->campaigns()->where('status', 'draft')->count(),
            ],
            'emails' => [
                'sent' => $tenant->sentEmails()->count(),
                'delivered' => $tenant->sentEmails()->where('status', 'delivered')->count(),
                'opened' => $tenant->sentEmails()->whereNotNull('opened_at')->count(),
                'clicked' => $tenant->sentEmails()->whereNotNull('clicked_at')->count(),
                'bounced' => $tenant->sentEmails()->where('status', 'bounced')->count(),
            ],
            'automations' => [
                'total' => $tenant->automations()->count(),
                'active' => $tenant->automations()->where('status', 'active')->count(),
            ],
            'lists' => $tenant->contactLists()->count(),
            'templates' => $tenant->emailTemplates()->count(),
        ];

        // Recent activity
        $recentCampaigns = $tenant->campaigns()
            ->with('creator:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'sent_at', 'created_by', 'created_at']);

        // Invoices
        $invoices = $tenant->subscription?->invoices()
            ->latest()
            ->limit(10)
            ->get(['id', 'number', 'total', 'status', 'due_date', 'paid_at']);

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'owner' => $tenant->owner,
                'users' => $tenant->users->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'last_login_at' => $u->last_login_at?->format('d/m/Y H:i'),
                ]),
                'subscription' => $tenant->subscription ? [
                    'id' => $tenant->subscription->id,
                    'plan' => $tenant->subscription->plan,
                    'status' => $tenant->subscription->status,
                    'billing_period' => $tenant->subscription->billing_period,
                    'current_period_start' => $tenant->subscription->current_period_start?->format('d/m/Y'),
                    'current_period_end' => $tenant->subscription->current_period_end?->format('d/m/Y'),
                    'trial_ends_at' => $tenant->subscription->trial_ends_at?->format('d/m/Y'),
                    'ends_at' => $tenant->subscription->ends_at?->format('d/m/Y'),
                    'emails_sent_this_month' => $tenant->subscription->emails_sent_this_month,
                    'contacts_count' => $tenant->subscription->contacts_count,
                ] : null,
                'settings' => $tenant->settings,
                'created_at' => $tenant->created_at->format('d/m/Y H:i'),
            ],
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
            'invoices' => $invoices,
            'plans' => Plan::where('is_active', true)->get(),
        ]);
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email,' . $tenant->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Tenant mis à jour.');
    }

    /**
     * Change tenant subscription.
     */
    public function changeSubscription(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_period' => 'required|in:monthly,yearly',
            'action' => 'required|in:upgrade,downgrade,change',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $subscription = $tenant->subscription;

        if (!$subscription) {
            // Create new subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_period' => $validated['billing_period'],
                'current_period_start' => now(),
                'current_period_end' => $validated['billing_period'] === 'yearly'
                    ? now()->addYear()
                    : now()->addMonth(),
            ]);
        } else {
            $subscription->update([
                'plan_id' => $plan->id,
                'billing_period' => $validated['billing_period'],
            ]);
        }

        return back()->with('success', 'Abonnement modifié.');
    }

    /**
     * Suspend tenant.
     */
    public function suspend(Tenant $tenant)
    {
        if ($tenant->subscription) {
            $tenant->subscription->update(['status' => 'suspended']);
        }

        return back()->with('success', 'Tenant suspendu.');
    }

    /**
     * Reactivate tenant.
     */
    public function reactivate(Tenant $tenant)
    {
        if ($tenant->subscription) {
            $tenant->subscription->update(['status' => 'active']);
        }

        return back()->with('success', 'Tenant réactivé.');
    }

    /**
     * Delete tenant and all associated data.
     */
    public function destroy(Tenant $tenant)
    {
        // This is a destructive operation - soft delete or fully remove
        DB::transaction(function () use ($tenant) {
            // Delete all tenant data
            $tenant->contacts()->delete();
            $tenant->contactLists()->delete();
            $tenant->campaigns()->delete();
            $tenant->emailTemplates()->delete();
            $tenant->automations()->delete();
            $tenant->tags()->delete();
            $tenant->sentEmails()->delete();
            $tenant->users()->delete();

            if ($tenant->subscription) {
                $tenant->subscription->invoices()->delete();
                $tenant->subscription->delete();
            }

            $tenant->delete();
        });

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant supprimé définitivement.');
    }

    /**
     * Impersonate a tenant (login as tenant owner).
     */
    public function impersonate(Tenant $tenant)
    {
        $owner = $tenant->owner;

        if (!$owner) {
            return back()->withErrors(['error' => 'Ce tenant n\'a pas de propriétaire.']);
        }

        // Store original admin user ID in session
        session(['impersonating_from' => auth()->id()]);

        // Login as tenant owner
        auth()->login($owner);

        return redirect()->route('dashboard')
            ->with('info', 'Vous êtes maintenant connecté en tant que ' . $owner->name);
    }
}
