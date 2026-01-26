<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of team members.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        $query = User::where('tenant_id', $tenant->id)
            ->whereNull('deleted_at');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'total' => User::where('tenant_id', $tenant->id)->count(),
            'owners' => User::where('tenant_id', $tenant->id)->where('role', User::ROLE_OWNER)->count(),
            'admins' => User::where('tenant_id', $tenant->id)->where('role', User::ROLE_ADMIN)->count(),
            'editors' => User::where('tenant_id', $tenant->id)->where('role', User::ROLE_EDITOR)->count(),
            'analysts' => User::where('tenant_id', $tenant->id)->where('role', User::ROLE_ANALYST)->count(),
            'developers' => User::where('tenant_id', $tenant->id)->where('role', User::ROLE_DEVELOPER)->count(),
        ];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'roles' => User::getRoles(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Show the form for creating a new team member.
     */
    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => $this->getAssignableRoles(),
        ]);
    }

    /**
     * Store a newly created team member.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($tenant) {
                    return $query->where('tenant_id', $tenant->id);
                }),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys($this->getAssignableRoles()))],
            'phone' => 'nullable|string|max:20',
        ]);

        // Check if the current user can assign this role
        if (!$this->canAssignRole($request->user(), $validated['role'])) {
            return redirect()->back()->withErrors(['role' => 'Vous ne pouvez pas attribuer ce rôle.']);
        }

        // Check team member limits based on subscription
        $currentCount = User::where('tenant_id', $tenant->id)->count();
        $maxMembers = $tenant->subscription?->plan->features['max_team_members'] ?? 1;

        if ($currentCount >= $maxMembers) {
            return redirect()->back()->with('error', "Vous avez atteint la limite de {$maxMembers} membres d'équipe pour votre forfait.");
        }

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // TODO: Send welcome email with credentials

        return redirect()->route('users.index')
            ->with('success', "L'utilisateur {$user->name} a été créé avec succès.");
    }

    /**
     * Display the specified team member.
     */
    public function show(User $user): Response
    {
        $this->authorizeUser($user);

        return Inertia::render('Users/Show', [
            'user' => $user->load('tenant'),
            'roles' => User::getRoles(),
        ]);
    }

    /**
     * Show the form for editing the specified team member.
     */
    public function edit(User $user): Response
    {
        $this->authorizeUser($user);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $this->getAssignableRoles(),
        ]);
    }

    /**
     * Update the specified team member.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);
        $currentUser = $request->user();
        $tenant = $currentUser->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')
                    ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
                    ->ignore($user->id),
            ],
            'role' => ['required', Rule::in(array_keys($this->getAssignableRoles()))],
            'phone' => 'nullable|string|max:20',
        ]);

        // Prevent owner from being demoted if they're the only owner
        if ($user->isOwner() && $validated['role'] !== User::ROLE_OWNER) {
            $ownerCount = User::where('tenant_id', $tenant->id)
                ->where('role', User::ROLE_OWNER)
                ->count();

            if ($ownerCount <= 1) {
                return redirect()->back()->withErrors(['role' => 'Il doit y avoir au moins un propriétaire dans l\'équipe.']);
            }
        }

        // Check if the current user can assign this role
        if (!$this->canAssignRole($currentUser, $validated['role'])) {
            return redirect()->back()->withErrors(['role' => 'Vous ne pouvez pas attribuer ce rôle.']);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
        ]);

        return redirect()->route('users.index')
            ->with('success', "L'utilisateur {$user->name} a été mis à jour.");
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Le mot de passe a été mis à jour.');
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);
        $currentUser = $request->user();

        // Prevent self-deletion
        if ($user->id === $currentUser->id) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Prevent deleting the only owner
        if ($user->isOwner()) {
            $ownerCount = User::where('tenant_id', $currentUser->tenant_id)
                ->where('role', User::ROLE_OWNER)
                ->count();

            if ($ownerCount <= 1) {
                return redirect()->back()->with('error', 'Impossible de supprimer le seul propriétaire de l\'équipe.');
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "L'utilisateur {$userName} a été supprimé.");
    }

    /**
     * Resend invitation/welcome email.
     */
    public function resendInvitation(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        // TODO: Implement email sending
        // Mail::to($user->email)->send(new WelcomeEmail($user));

        return redirect()->back()->with('success', 'Email d\'invitation renvoyé à ' . $user->email);
    }

    /**
     * Check if the authenticated user can manage the target user.
     */
    protected function authorizeUser(User $user): void
    {
        $currentUser = auth()->user();

        if ($user->tenant_id !== $currentUser->tenant_id) {
            abort(403, 'Non autorisé.');
        }
    }

    /**
     * Get roles that the current user can assign.
     */
    protected function getAssignableRoles(): array
    {
        $user = auth()->user();
        $allRoles = User::getRoles();

        // Owner can assign all roles except owner (unless they are owner)
        if ($user->isOwner()) {
            return $allRoles;
        }

        // Admin can assign editor, analyst, developer
        if ($user->isAdmin()) {
            return [
                User::ROLE_EDITOR => $allRoles[User::ROLE_EDITOR],
                User::ROLE_ANALYST => $allRoles[User::ROLE_ANALYST],
                User::ROLE_DEVELOPER => $allRoles[User::ROLE_DEVELOPER],
            ];
        }

        return [];
    }

    /**
     * Check if the current user can assign a specific role.
     */
    protected function canAssignRole(User $currentUser, string $role): bool
    {
        // Owner can assign any role
        if ($currentUser->isOwner()) {
            return true;
        }

        // Admin can assign editor, analyst, developer
        if ($currentUser->isAdmin()) {
            return in_array($role, [User::ROLE_EDITOR, User::ROLE_ANALYST, User::ROLE_DEVELOPER]);
        }

        return false;
    }
}
