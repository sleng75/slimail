<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\Contact;
use App\Services\AutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationApiController extends Controller
{
    public function __construct(
        protected AutomationService $automationService
    ) {}

    /**
     * Trigger an automation via webhook.
     * POST /api/v1/automations/{automation}/trigger
     */
    public function trigger(Request $request, Automation $automation): JsonResponse
    {
        // Check if automation is active
        if (!$automation->isActive()) {
            return response()->json([
                'success' => false,
                'error' => 'Automation is not active',
            ], 400);
        }

        // Validate that this automation accepts webhook triggers
        if ($automation->trigger_type !== Automation::TRIGGER_WEBHOOK) {
            return response()->json([
                'success' => false,
                'error' => 'This automation does not accept webhook triggers',
            ], 400);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'data' => 'nullable|array',
        ]);

        // Find or create contact
        $contact = Contact::where('tenant_id', $automation->tenant_id)
            ->where('email', $validated['email'])
            ->first();

        if (!$contact) {
            // Optionally create contact
            if ($request->boolean('create_contact', true)) {
                $contact = Contact::create([
                    'tenant_id' => $automation->tenant_id,
                    'email' => $validated['email'],
                    'status' => Contact::STATUS_SUBSCRIBED,
                    'source' => Contact::SOURCE_API,
                    'source_details' => 'automation_webhook',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Contact not found',
                ], 404);
            }
        }

        // Enroll contact
        $enrollment = $automation->enrollContact($contact, [
            'source' => 'webhook',
            'webhook_data' => $validated['data'] ?? [],
        ]);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'error' => 'Contact could not be enrolled (may already be in automation)',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact enrolled in automation',
            'enrollment_id' => $enrollment->id,
            'contact_id' => $contact->id,
        ]);
    }

    /**
     * List automations for the tenant.
     * GET /api/v1/automations
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $automations = Automation::where('tenant_id', $tenant->id)
            ->select('id', 'name', 'trigger_type', 'status', 'total_enrolled', 'currently_active', 'completed')
            ->get();

        return response()->json([
            'success' => true,
            'automations' => $automations,
        ]);
    }

    /**
     * Get automation details.
     * GET /api/v1/automations/{automation}
     */
    public function show(Request $request, Automation $automation): JsonResponse
    {
        return response()->json([
            'success' => true,
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
                'description' => $automation->description,
                'trigger_type' => $automation->trigger_type,
                'status' => $automation->status,
                'total_enrolled' => $automation->total_enrolled,
                'currently_active' => $automation->currently_active,
                'completed' => $automation->completed,
                'exited' => $automation->exited,
                'conversion_rate' => $automation->conversion_rate,
                'created_at' => $automation->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Manually enroll a contact.
     * POST /api/v1/automations/{automation}/enroll
     */
    public function enroll(Request $request, Automation $automation): JsonResponse
    {
        if (!$automation->isActive()) {
            return response()->json([
                'success' => false,
                'error' => 'Automation is not active',
            ], 400);
        }

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $contact = Contact::where('tenant_id', $automation->tenant_id)
            ->where('email', $validated['email'])
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'error' => 'Contact not found',
            ], 404);
        }

        $enrollment = $automation->enrollContact($contact, ['source' => 'api']);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'error' => 'Contact could not be enrolled',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'enrollment_id' => $enrollment->id,
        ]);
    }

    /**
     * Get enrollment status.
     * GET /api/v1/automations/{automation}/enrollments/{email}
     */
    public function enrollmentStatus(Request $request, Automation $automation, string $email): JsonResponse
    {
        $contact = Contact::where('tenant_id', $automation->tenant_id)
            ->where('email', $email)
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'error' => 'Contact not found',
            ], 404);
        }

        $enrollment = $automation->enrollments()
            ->where('contact_id', $contact->id)
            ->latest()
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'error' => 'Contact not enrolled in this automation',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'enrollment' => [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'status_label' => $enrollment->status_label,
                'current_step' => $enrollment->currentStep?->name ?? $enrollment->currentStep?->type_label,
                'enrolled_at' => $enrollment->enrolled_at->toIso8601String(),
                'completed_at' => $enrollment->completed_at?->toIso8601String(),
                'exited_at' => $enrollment->exited_at?->toIso8601String(),
                'exit_reason' => $enrollment->exit_reason_label,
            ],
        ]);
    }
}
