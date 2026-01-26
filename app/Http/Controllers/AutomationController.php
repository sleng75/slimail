<?php

namespace App\Http\Controllers;

use App\Models\Automation;
use App\Models\AutomationEnrollment;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailTemplate;
use App\Models\Tag;
use App\Services\AutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AutomationController extends Controller
{
    public function __construct(
        protected AutomationService $automationService
    ) {}

    /**
     * Display a listing of automations.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        $query = Automation::where('tenant_id', $tenant->id)
            ->with('creator:id,name')
            ->withCount(['enrollments', 'activeEnrollments', 'steps']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by trigger
        if ($request->filled('trigger')) {
            $query->where('trigger_type', $request->trigger);
        }

        // Sort
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $automations = $query->paginate(15)->through(fn ($automation) => [
            'id' => $automation->id,
            'name' => $automation->name,
            'description' => $automation->description,
            'trigger_type' => $automation->trigger_type,
            'trigger_label' => $automation->trigger_label,
            'status' => $automation->status,
            'status_label' => $automation->status_label,
            'total_enrolled' => $automation->total_enrolled,
            'currently_active' => $automation->currently_active,
            'completed' => $automation->completed,
            'conversion_rate' => $automation->conversion_rate,
            'steps_count' => $automation->steps_count,
            'enrollments_count' => $automation->enrollments_count,
            'active_enrollments_count' => $automation->active_enrollments_count,
            'created_by' => $automation->creator?->name,
            'activated_at' => $automation->activated_at?->format('d/m/Y H:i'),
            'created_at' => $automation->created_at->format('d/m/Y'),
        ]);

        return Inertia::render('Automations/Index', [
            'automations' => $automations,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'trigger' => $request->trigger,
            ],
            'triggerTypes' => Automation::getTriggerTypes(),
            'statuses' => Automation::getStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new automation.
     */
    public function create(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Automations/Create', [
            'triggerTypes' => Automation::getTriggerTypes(),
            'lists' => ContactList::where('tenant_id', $tenant->id)
                ->select('id', 'name', 'contact_count')
                ->orderBy('name')
                ->get(),
            'tags' => Tag::where('tenant_id', $tenant->id)
                ->select('id', 'name', 'color')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created automation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|string|in:' . implode(',', array_keys(Automation::getTriggerTypes())),
            'trigger_config' => 'nullable|array',
            'allow_reentry' => 'boolean',
            'reentry_delay_days' => 'nullable|integer|min:1|max:365',
            'exit_on_goal' => 'boolean',
            'goal_config' => 'nullable|array',
            'schedule' => 'nullable|array',
            'timezone' => 'nullable|string',
        ]);

        $tenant = $request->user()->tenant;

        $automation = Automation::create([
            'tenant_id' => $tenant->id,
            'created_by' => $request->user()->id,
            'status' => Automation::STATUS_DRAFT,
            ...$validated,
        ]);

        return redirect()->route('automations.edit', $automation)
            ->with('success', 'Automatisation créée. Configurez maintenant les étapes.');
    }

    /**
     * Display the specified automation.
     */
    public function show(Request $request, Automation $automation): Response
    {
        $this->authorize('view', $automation);

        $automation->load(['creator:id,name', 'steps']);

        // Get recent enrollments
        $recentEnrollments = $automation->enrollments()
            ->with(['contact:id,email,first_name,last_name', 'currentStep:id,type,name'])
            ->latest('enrolled_at')
            ->limit(10)
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'contact' => [
                    'id' => $enrollment->contact->id,
                    'email' => $enrollment->contact->email,
                    'name' => $enrollment->contact->full_name,
                ],
                'status' => $enrollment->status,
                'status_label' => $enrollment->status_label,
                'current_step' => $enrollment->currentStep ? [
                    'id' => $enrollment->currentStep->id,
                    'type' => $enrollment->currentStep->type,
                    'name' => $enrollment->currentStep->name ?? $enrollment->currentStep->type_label,
                ] : null,
                'enrolled_at' => $enrollment->enrolled_at->format('d/m/Y H:i'),
                'duration' => $enrollment->duration,
            ]);

        // Get step statistics
        $stepStats = $automation->steps->map(fn ($step) => [
            'id' => $step->id,
            'type' => $step->type,
            'type_label' => $step->type_label,
            'name' => $step->name,
            'entered_count' => $step->entered_count,
            'completed_count' => $step->completed_count,
            'failed_count' => $step->failed_count,
            'completion_rate' => $step->completion_rate,
            'emails_sent' => $step->emails_sent,
            'open_rate' => $step->open_rate,
            'click_rate' => $step->click_rate,
        ]);

        return Inertia::render('Automations/Show', [
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
                'description' => $automation->description,
                'trigger_type' => $automation->trigger_type,
                'trigger_label' => $automation->trigger_label,
                'trigger_config' => $automation->trigger_config,
                'status' => $automation->status,
                'status_label' => $automation->status_label,
                'total_enrolled' => $automation->total_enrolled,
                'currently_active' => $automation->currently_active,
                'completed' => $automation->completed,
                'exited' => $automation->exited,
                'conversion_rate' => $automation->conversion_rate,
                'exit_rate' => $automation->exit_rate,
                'allow_reentry' => $automation->allow_reentry,
                'exit_on_goal' => $automation->exit_on_goal,
                'activated_at' => $automation->activated_at?->format('d/m/Y H:i'),
                'created_at' => $automation->created_at->format('d/m/Y H:i'),
                'created_by' => $automation->creator?->name,
                'workflow' => $automation->getWorkflowStructure(),
            ],
            'recentEnrollments' => $recentEnrollments,
            'stepStats' => $stepStats,
        ]);
    }

    /**
     * Show the form for editing the specified automation.
     */
    public function edit(Request $request, Automation $automation): Response
    {
        $this->authorize('update', $automation);

        $tenant = $request->user()->tenant;

        $automation->load('steps');

        return Inertia::render('Automations/Edit', [
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
                'description' => $automation->description,
                'trigger_type' => $automation->trigger_type,
                'trigger_config' => $automation->trigger_config,
                'status' => $automation->status,
                'allow_reentry' => $automation->allow_reentry,
                'reentry_delay_days' => $automation->reentry_delay_days,
                'exit_on_goal' => $automation->exit_on_goal,
                'goal_config' => $automation->goal_config,
                'schedule' => $automation->schedule,
                'timezone' => $automation->timezone,
                'workflow' => $automation->getWorkflowStructure(),
            ],
            'triggerTypes' => Automation::getTriggerTypes(),
            'stepTypes' => AutomationStep::getTypes(),
            'conditionTypes' => AutomationStep::getConditionTypes(),
            'lists' => ContactList::where('tenant_id', $tenant->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'tags' => Tag::where('tenant_id', $tenant->id)
                ->select('id', 'name', 'color')
                ->orderBy('name')
                ->get(),
            'templates' => EmailTemplate::where('tenant_id', $tenant->id)
                ->select('id', 'name', 'subject')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update the specified automation.
     */
    public function update(Request $request, Automation $automation)
    {
        $this->authorize('update', $automation);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|string|in:' . implode(',', array_keys(Automation::getTriggerTypes())),
            'trigger_config' => 'nullable|array',
            'allow_reentry' => 'boolean',
            'reentry_delay_days' => 'nullable|integer|min:1|max:365',
            'exit_on_goal' => 'boolean',
            'goal_config' => 'nullable|array',
            'schedule' => 'nullable|array',
            'timezone' => 'nullable|string',
        ]);

        $automation->update($validated);

        return back()->with('success', 'Automatisation mise à jour.');
    }

    /**
     * Save the workflow steps.
     */
    public function saveWorkflow(Request $request, Automation $automation)
    {
        $this->authorize('update', $automation);

        $validated = $request->validate([
            'steps' => 'required|array',
            'steps.*.type' => 'required|string',
            'steps.*.name' => 'nullable|string|max:255',
            'steps.*.config' => 'nullable|array',
            'steps.*.yes_branch' => 'nullable|array',
            'steps.*.no_branch' => 'nullable|array',
            'steps.*.children' => 'nullable|array',
        ]);

        $this->automationService->saveWorkflow($automation, $validated['steps']);

        return back()->with('success', 'Workflow sauvegardé.');
    }

    /**
     * Remove the specified automation.
     */
    public function destroy(Automation $automation)
    {
        $this->authorize('delete', $automation);

        // Archive instead of delete if has enrollments
        if ($automation->total_enrolled > 0) {
            $automation->archive();
            return redirect()->route('automations.index')
                ->with('success', 'Automatisation archivée.');
        }

        $automation->delete();

        return redirect()->route('automations.index')
            ->with('success', 'Automatisation supprimée.');
    }

    /**
     * Activate the automation.
     */
    public function activate(Automation $automation)
    {
        $this->authorize('update', $automation);

        if (!$automation->canBeActivated()) {
            return back()->withErrors([
                'activation' => 'L\'automatisation doit avoir au moins une étape pour être activée.',
            ]);
        }

        $automation->activate();

        return back()->with('success', 'Automatisation activée.');
    }

    /**
     * Pause the automation.
     */
    public function pause(Automation $automation)
    {
        $this->authorize('update', $automation);

        $automation->pause();

        return back()->with('success', 'Automatisation mise en pause.');
    }

    /**
     * Duplicate the automation.
     */
    public function duplicate(Automation $automation)
    {
        $this->authorize('view', $automation);

        $newAutomation = $this->automationService->duplicate($automation);

        return redirect()->route('automations.edit', $newAutomation)
            ->with('success', 'Automatisation dupliquée.');
    }

    /**
     * Show enrollments for an automation.
     */
    public function enrollments(Request $request, Automation $automation): Response
    {
        $this->authorize('view', $automation);

        $query = $automation->enrollments()
            ->with(['contact:id,email,first_name,last_name', 'currentStep:id,type,name']);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by contact
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('contact', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->latest('enrolled_at')
            ->paginate(20)
            ->through(fn ($enrollment) => [
                'id' => $enrollment->id,
                'contact' => [
                    'id' => $enrollment->contact->id,
                    'email' => $enrollment->contact->email,
                    'name' => $enrollment->contact->full_name,
                ],
                'status' => $enrollment->status,
                'status_label' => $enrollment->status_label,
                'exit_reason' => $enrollment->exit_reason_label,
                'current_step' => $enrollment->currentStep ? [
                    'type' => $enrollment->currentStep->type,
                    'name' => $enrollment->currentStep->name ?? $enrollment->currentStep->type_label,
                ] : null,
                'enrolled_at' => $enrollment->enrolled_at->format('d/m/Y H:i'),
                'completed_at' => $enrollment->completed_at?->format('d/m/Y H:i'),
                'exited_at' => $enrollment->exited_at?->format('d/m/Y H:i'),
                'duration' => $enrollment->duration,
                'step_history' => $enrollment->detailed_history,
            ]);

        return Inertia::render('Automations/Enrollments', [
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
                'status' => $automation->status,
            ],
            'enrollments' => $enrollments,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
            'statuses' => AutomationEnrollment::getStatuses(),
        ]);
    }

    /**
     * Manually enroll contacts.
     */
    public function enrollContacts(Request $request, Automation $automation)
    {
        $this->authorize('update', $automation);

        $validated = $request->validate([
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        if (!$automation->isActive()) {
            return back()->withErrors([
                'enrollment' => 'L\'automatisation doit être active pour inscrire des contacts.',
            ]);
        }

        $enrolled = 0;
        $skipped = 0;

        foreach ($validated['contact_ids'] as $contactId) {
            $contact = Contact::find($contactId);
            if ($contact && $automation->enrollContact($contact, ['source' => 'manual'])) {
                $enrolled++;
            } else {
                $skipped++;
            }
        }

        $message = "{$enrolled} contact(s) inscrit(s) à l'automatisation.";
        if ($skipped > 0) {
            $message .= " {$skipped} contact(s) ignoré(s) (déjà inscrits ou inéligibles).";
        }

        return back()->with('success', $message);
    }

    /**
     * Remove enrollment.
     */
    public function removeEnrollment(Automation $automation, AutomationEnrollment $enrollment)
    {
        $this->authorize('update', $automation);

        if ($enrollment->automation_id !== $automation->id) {
            abort(404);
        }

        $enrollment->exit(AutomationEnrollment::EXIT_MANUAL);

        return back()->with('success', 'Contact retiré de l\'automatisation.');
    }

    /**
     * View activity logs for an automation.
     */
    public function logs(Request $request, Automation $automation): Response
    {
        $this->authorize('view', $automation);

        $query = $automation->logs()
            ->with(['contact:id,email', 'step:id,type,name']);

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->latest()
            ->paginate(50)
            ->through(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->action_label,
                'status' => $log->status,
                'message' => $log->message,
                'contact_email' => $log->contact?->email,
                'step_name' => $log->step?->name ?? $log->step?->type_label,
                'data' => $log->data,
                'created_at' => $log->created_at->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('Automations/Logs', [
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
            ],
            'logs' => $logs,
            'filters' => [
                'action' => $request->action,
                'status' => $request->status,
            ],
            'actionLabels' => \App\Models\AutomationLog::getActionLabels(),
        ]);
    }
}
