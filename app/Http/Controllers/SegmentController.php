<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Segment;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SegmentController extends Controller
{
    /**
     * Display a listing of segments.
     */
    public function index(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Segment::where('tenant_id', $tenantId);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $segments = $query->paginate(15)->withQueryString();

        // Refresh counts for displayed segments
        foreach ($segments as $segment) {
            if (!$segment->count_cached_at || $segment->count_cached_at->lt(now()->subMinutes(5))) {
                $segment->refreshCount();
            }
        }

        return Inertia::render('Segments/Index', [
            'segments' => $segments,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new segment.
     */
    public function create(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        return Inertia::render('Segments/Create', [
            'availableFields' => Segment::getAvailableFields($tenantId),
            'operators' => Segment::OPERATORS,
            'matchTypes' => [
                ['value' => 'all', 'label' => 'Tous les critères (ET)'],
                ['value' => 'any', 'label' => 'Au moins un critère (OU)'],
            ],
        ]);
    }

    /**
     * Store a newly created segment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'match_type' => 'required|in:all,any',
            'criteria' => 'required|array|min:1',
            'criteria.*.field' => 'required|string',
            'criteria.*.operator' => 'required|string',
            'criteria.*.value' => 'nullable',
        ]);

        $segment = Segment::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'match_type' => $validated['match_type'],
            'criteria' => $validated['criteria'],
            'is_active' => true,
        ]);

        // Calculate initial count
        $segment->refreshCount();

        return redirect()->route('segments.show', $segment)
            ->with('success', 'Segment créé avec succès.');
    }

    /**
     * Display the specified segment.
     */
    public function show(Segment $segment): Response
    {
        $this->authorize('view', $segment);

        // Refresh count
        $segment->refreshCount();

        // Get sample contacts
        $sampleContacts = $segment->getContactsQuery()
            ->select(['id', 'email', 'first_name', 'last_name', 'status', 'created_at'])
            ->limit(10)
            ->get();

        // Get field labels for criteria display
        $tenantId = Auth::user()->tenant_id;
        $availableFields = collect(Segment::getAvailableFields($tenantId))
            ->keyBy('value')
            ->toArray();

        return Inertia::render('Segments/Show', [
            'segment' => $segment,
            'sampleContacts' => $sampleContacts,
            'availableFields' => $availableFields,
            'operators' => Segment::OPERATORS,
        ]);
    }

    /**
     * Show the form for editing the specified segment.
     */
    public function edit(Segment $segment): Response
    {
        $this->authorize('update', $segment);

        $tenantId = Auth::user()->tenant_id;

        return Inertia::render('Segments/Edit', [
            'segment' => $segment,
            'availableFields' => Segment::getAvailableFields($tenantId),
            'operators' => Segment::OPERATORS,
            'matchTypes' => [
                ['value' => 'all', 'label' => 'Tous les critères (ET)'],
                ['value' => 'any', 'label' => 'Au moins un critère (OU)'],
            ],
        ]);
    }

    /**
     * Update the specified segment.
     */
    public function update(Request $request, Segment $segment)
    {
        $this->authorize('update', $segment);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'match_type' => 'required|in:all,any',
            'criteria' => 'required|array|min:1',
            'criteria.*.field' => 'required|string',
            'criteria.*.operator' => 'required|string',
            'criteria.*.value' => 'nullable',
            'is_active' => 'boolean',
        ]);

        $segment->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'match_type' => $validated['match_type'],
            'criteria' => $validated['criteria'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Refresh count
        $segment->refreshCount();

        return redirect()->route('segments.show', $segment)
            ->with('success', 'Segment mis à jour avec succès.');
    }

    /**
     * Remove the specified segment.
     */
    public function destroy(Segment $segment)
    {
        $this->authorize('delete', $segment);

        // Check if segment is used in campaigns
        if ($segment->campaigns()->exists()) {
            return back()->with('error', 'Ce segment est utilisé dans des campagnes et ne peut pas être supprimé.');
        }

        $segment->delete();

        return redirect()->route('segments.index')
            ->with('success', 'Segment supprimé avec succès.');
    }

    /**
     * Preview contacts matching criteria without saving.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'match_type' => 'required|in:all,any',
            'criteria' => 'required|array|min:1',
            'criteria.*.field' => 'required|string',
            'criteria.*.operator' => 'required|string',
            'criteria.*.value' => 'nullable',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Create temporary segment for preview
        $tempSegment = new Segment([
            'tenant_id' => $tenantId,
            'match_type' => $validated['match_type'],
            'criteria' => $validated['criteria'],
        ]);

        $query = $tempSegment->getContactsQuery();
        $count = $query->count();

        $contacts = $query
            ->select(['id', 'email', 'first_name', 'last_name', 'status'])
            ->limit(20)
            ->get();

        return response()->json([
            'count' => $count,
            'contacts' => $contacts,
        ]);
    }

    /**
     * Refresh segment count.
     */
    public function refreshCount(Segment $segment)
    {
        $this->authorize('view', $segment);

        $count = $segment->refreshCount();

        return response()->json([
            'count' => $count,
            'cached_at' => $segment->count_cached_at->toIso8601String(),
        ]);
    }

    /**
     * Export segment contacts.
     */
    public function export(Segment $segment)
    {
        $this->authorize('view', $segment);

        $contacts = $segment->getContactsQuery()
            ->select(['email', 'first_name', 'last_name', 'phone', 'company', 'city', 'country', 'status', 'created_at'])
            ->get();

        $filename = 'segment_' . str_replace(' ', '_', $segment->name) . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($contacts) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Email', 'Prénom', 'Nom', 'Téléphone', 'Entreprise', 'Ville', 'Pays', 'Statut', 'Date création']);

            // Data rows
            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->email,
                    $contact->first_name,
                    $contact->last_name,
                    $contact->phone,
                    $contact->company,
                    $contact->city,
                    $contact->country,
                    $contact->status,
                    $contact->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get operators for a field type (API).
     */
    public function getOperators(Request $request)
    {
        $type = $request->get('type', 'string');

        return response()->json([
            'operators' => Segment::getOperatorsForType($type),
        ]);
    }

    /**
     * Duplicate a segment.
     */
    public function duplicate(Segment $segment)
    {
        $this->authorize('view', $segment);

        $newSegment = $segment->replicate();
        $newSegment->name = $segment->name . ' (copie)';
        $newSegment->cached_count = 0;
        $newSegment->count_cached_at = null;
        $newSegment->save();

        $newSegment->refreshCount();

        return redirect()->route('segments.edit', $newSegment)
            ->with('success', 'Segment dupliqué avec succès.');
    }
}
