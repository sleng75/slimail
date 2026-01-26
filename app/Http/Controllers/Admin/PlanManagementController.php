<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlanManagementController extends Controller
{
    /**
     * Display a listing of plans.
     */
    public function index(): Response
    {
        $plans = Plan::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'email_limit' => $plan->email_limit,
                'contact_limit' => $plan->contact_limit,
                'features' => $plan->features,
                'is_active' => $plan->is_active,
                'is_featured' => $plan->is_featured,
                'sort_order' => $plan->sort_order,
                'active_subscriptions' => $plan->subscriptions_count,
                'monthly_revenue' => $plan->subscriptions_count * $plan->price_monthly,
            ]);

        // Calculate totals
        $totals = [
            'total_plans' => $plans->count(),
            'active_plans' => $plans->where('is_active', true)->count(),
            'total_subscribers' => $plans->sum('active_subscriptions'),
            'estimated_mrr' => $plans->sum('monthly_revenue'),
        ];

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'totals' => $totals,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Plans/Create', [
            'availableFeatures' => $this->getAvailableFeatures(),
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:plans',
            'description' => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'email_limit' => 'required|integer|min:-1',
            'contact_limit' => 'required|integer|min:-1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'trial_days' => 'integer|min:0|max:365',
        ]);

        $maxOrder = Plan::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        Plan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Forfait créé avec succès.');
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan): Response
    {
        return Inertia::render('Admin/Plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'email_limit' => $plan->email_limit,
                'contact_limit' => $plan->contact_limit,
                'features' => $plan->features ?? [],
                'is_active' => $plan->is_active,
                'is_featured' => $plan->is_featured,
                'trial_days' => $plan->trial_days,
            ],
            'availableFeatures' => $this->getAvailableFeatures(),
            'subscribersCount' => $plan->subscriptions()->where('status', 'active')->count(),
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'email_limit' => 'required|integer|min:-1',
            'contact_limit' => 'required|integer|min:-1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'trial_days' => 'integer|min:0|max:365',
        ]);

        $plan->update($validated);

        return back()->with('success', 'Forfait mis à jour.');
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(Plan $plan)
    {
        // Check if plan has active subscriptions
        $activeSubscriptions = $plan->subscriptions()->where('status', 'active')->count();

        if ($activeSubscriptions > 0) {
            return back()->withErrors([
                'error' => "Ce forfait a {$activeSubscriptions} abonnement(s) actif(s). Migrez-les d'abord."
            ]);
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Forfait supprimé.');
    }

    /**
     * Reorder plans.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:plans,id',
        ]);

        foreach ($validated['order'] as $index => $planId) {
            Plan::where('id', $planId)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Ordre mis à jour.');
    }

    /**
     * Get available features for plans.
     */
    protected function getAvailableFeatures(): array
    {
        return [
            'contacts_management' => 'Gestion des contacts',
            'email_campaigns' => 'Campagnes email',
            'email_templates' => 'Templates email',
            'ab_testing' => 'Tests A/B',
            'automations' => 'Automatisations',
            'advanced_automations' => 'Automatisations avancées',
            'analytics' => 'Statistiques',
            'advanced_analytics' => 'Analytics avancés',
            'api_access' => 'Accès API',
            'webhooks' => 'Webhooks',
            'custom_domain' => 'Domaine personnalisé',
            'dedicated_ip' => 'IP dédiée',
            'priority_support' => 'Support prioritaire',
            'phone_support' => 'Support téléphonique',
            'custom_branding' => 'Branding personnalisé',
            'team_members' => 'Membres d\'équipe illimités',
            'role_permissions' => 'Rôles et permissions',
            'sso' => 'Single Sign-On',
            'audit_logs' => 'Logs d\'audit',
            'data_export' => 'Export de données',
        ];
    }
}
