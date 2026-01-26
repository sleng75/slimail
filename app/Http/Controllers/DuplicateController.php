<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\Contact\DuplicateDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DuplicateController extends Controller
{
    public function __construct(
        protected DuplicateDetectionService $duplicateService
    ) {}

    /**
     * Display duplicate contacts.
     */
    public function index(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $duplicates = $this->duplicateService->findDuplicates($tenantId);
        $stats = $this->duplicateService->getDuplicateStats($tenantId);

        return Inertia::render('Contacts/Duplicates', [
            'duplicates' => $duplicates->values()->toArray(),
            'stats' => $stats,
        ]);
    }

    /**
     * Check for duplicates before import.
     */
    public function checkImport(Request $request)
    {
        $validated = $request->validate([
            'emails' => 'required|array',
            'emails.*' => 'email',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $duplicates = $this->duplicateService->findDuplicatesInList($tenantId, $validated['emails']);

        return response()->json([
            'total_checked' => count($validated['emails']),
            'duplicates_found' => count($duplicates),
            'duplicate_emails' => $duplicates,
        ]);
    }

    /**
     * Merge duplicate contacts.
     */
    public function merge(Request $request)
    {
        $validated = $request->validate([
            'keep_id' => 'required|integer|exists:contacts,id',
            'merge_ids' => 'required|array|min:1',
            'merge_ids.*' => 'integer|exists:contacts,id',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Verify all contacts belong to the tenant
        $keepContact = Contact::where('id', $validated['keep_id'])
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $mergeContacts = Contact::whereIn('id', $validated['merge_ids'])
            ->where('tenant_id', $tenantId)
            ->get();

        if ($mergeContacts->count() !== count($validated['merge_ids'])) {
            return back()->with('error', 'Certains contacts n\'ont pas été trouvés.');
        }

        // Perform merge
        $mergedContact = $this->duplicateService->mergeContacts($keepContact, $validated['merge_ids']);

        return redirect()->route('contacts.duplicates')
            ->with('success', 'Contacts fusionnés avec succès. ' . count($validated['merge_ids']) . ' doublon(s) supprimé(s).');
    }

    /**
     * Get duplicate stats (API).
     */
    public function stats()
    {
        $tenantId = Auth::user()->tenant_id;
        $stats = $this->duplicateService->getDuplicateStats($tenantId);

        return response()->json($stats);
    }

    /**
     * Check single contact for duplicates.
     */
    public function checkSingle(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $query = Contact::where('tenant_id', $tenantId)
            ->where('email', $validated['email']);

        if ($validated['exclude_id'] ?? null) {
            $query->where('id', '!=', $validated['exclude_id']);
        }

        $duplicate = $query->first();

        if ($duplicate) {
            return response()->json([
                'is_duplicate' => true,
                'duplicate' => [
                    'id' => $duplicate->id,
                    'email' => $duplicate->email,
                    'full_name' => $duplicate->full_name,
                    'status' => $duplicate->status,
                ],
            ]);
        }

        // Check phone if provided
        if ($validated['phone'] ?? null) {
            $phoneQuery = Contact::where('tenant_id', $tenantId)
                ->where('phone', $validated['phone']);

            if ($validated['exclude_id'] ?? null) {
                $phoneQuery->where('id', '!=', $validated['exclude_id']);
            }

            $phoneDuplicate = $phoneQuery->first();

            if ($phoneDuplicate) {
                return response()->json([
                    'is_duplicate' => true,
                    'match_type' => 'phone',
                    'duplicate' => [
                        'id' => $phoneDuplicate->id,
                        'email' => $phoneDuplicate->email,
                        'full_name' => $phoneDuplicate->full_name,
                        'status' => $phoneDuplicate->status,
                    ],
                ]);
            }
        }

        return response()->json([
            'is_duplicate' => false,
        ]);
    }
}
