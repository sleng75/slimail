<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\SentEmail;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        // Global statistics
        $stats = [
            'tenants' => [
                'total' => Tenant::count(),
                'active' => Tenant::whereHas('subscription', fn($q) => $q->where('status', 'active'))->count(),
                'trial' => Tenant::whereHas('subscription', fn($q) => $q->where('status', 'trialing'))->count(),
                'new_this_month' => Tenant::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'users' => [
                'total' => User::count(),
                'active_today' => User::where('last_login_at', '>=', now()->startOfDay())->count(),
            ],
            'contacts' => [
                'total' => Contact::count(),
                'subscribed' => Contact::where('status', 'subscribed')->count(),
            ],
            'emails' => [
                'sent_today' => SentEmail::where('created_at', '>=', now()->startOfDay())->count(),
                'sent_this_month' => SentEmail::where('created_at', '>=', now()->startOfMonth())->count(),
                'bounce_rate' => $this->calculateBounceRate(),
                'complaint_rate' => $this->calculateComplaintRate(),
            ],
            'campaigns' => [
                'total' => Campaign::count(),
                'sent_this_month' => Campaign::where('status', 'sent')
                    ->where('sent_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'revenue' => [
                'this_month' => Invoice::where('status', 'paid')
                    ->where('paid_at', '>=', now()->startOfMonth())
                    ->sum('total'),
                'last_month' => Invoice::where('status', 'paid')
                    ->whereBetween('paid_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
                    ->sum('total'),
                'mrr' => $this->calculateMRR(),
            ],
        ];

        // Recent signups
        $recentTenants = Tenant::with(['owner:id,name,email', 'subscription.plan'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'owner' => $tenant->owner?->name,
                'plan' => $tenant->subscription?->plan?->name ?? 'Aucun',
                'status' => $tenant->subscription?->status ?? 'none',
                'created_at' => $tenant->created_at->format('d/m/Y H:i'),
            ]);

        // Email volume chart (last 30 days)
        $emailVolume = SentEmail::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
            DB::raw('SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue chart (last 12 months)
        $revenueChart = Invoice::select(
            DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
            DB::raw('SUM(total) as revenue')
        )
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Plan distribution
        $planDistribution = Subscription::where('status', 'active')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->with('plan:id,name')
            ->get()
            ->map(fn($item) => [
                'plan' => $item->plan?->name ?? 'Unknown',
                'count' => $item->count,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'emailVolume' => $emailVolume,
            'revenueChart' => $revenueChart,
            'planDistribution' => $planDistribution,
        ]);
    }

    /**
     * Calculate bounce rate for the last 30 days.
     */
    protected function calculateBounceRate(): float
    {
        $stats = SentEmail::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced')
            ->first();

        if ($stats->total === 0) {
            return 0;
        }

        return round(($stats->bounced / $stats->total) * 100, 2);
    }

    /**
     * Calculate complaint rate for the last 30 days.
     */
    protected function calculateComplaintRate(): float
    {
        $stats = SentEmail::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "complained" THEN 1 ELSE 0 END) as complained')
            ->first();

        if ($stats->total === 0) {
            return 0;
        }

        return round(($stats->complained / $stats->total) * 100, 4);
    }

    /**
     * Calculate Monthly Recurring Revenue.
     */
    protected function calculateMRR(): float
    {
        return Subscription::where('status', 'active')
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                $plan = $subscription->plan;
                if (!$plan) return 0;

                // Convert yearly to monthly
                if ($subscription->billing_period === 'yearly') {
                    return $plan->price_yearly / 12;
                }
                return $plan->price_monthly;
            });
    }
}
