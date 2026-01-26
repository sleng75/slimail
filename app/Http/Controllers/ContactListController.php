<?php

namespace App\Http\Controllers;

use App\Models\ContactList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactListController extends Controller
{
    /**
     * Display a listing of contact lists.
     */
    public function index(Request $request): Response
    {
        $query = ContactList::query();

        // Search
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $lists = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Contacts/Lists/Index', [
            'lists' => $lists,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Show the form for creating a new list.
     */
    public function create(): Response
    {
        return Inertia::render('Contacts/Lists/Create', [
            'colors' => ContactList::COLORS,
        ]);
    }

    /**
     * Store a newly created list.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'type' => 'required|in:static,dynamic',
            'segment_criteria' => 'nullable|array',
            'double_optin' => 'boolean',
            'welcome_email_content' => 'nullable|string',
            'default_from_name' => 'nullable|string|max:255',
            'default_from_email' => 'nullable|email|max:255',
        ]);

        // Explicitly set tenant_id
        $validated['tenant_id'] = auth()->user()->tenant_id;

        ContactList::create($validated);

        return redirect()->route('contact-lists.index')
            ->with('success', 'Liste créée avec succès.');
    }

    /**
     * Display the specified list with its contacts.
     */
    public function show(Request $request, ContactList $contactList): Response
    {
        $query = $contactList->contacts()->with('tags');

        // Search within list
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('contacts.status', $status);
        }

        $contacts = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Contacts/Lists/Show', [
            'list' => $contactList,
            'contacts' => $contacts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for editing the specified list.
     */
    public function edit(ContactList $contactList): Response
    {
        return Inertia::render('Contacts/Lists/Edit', [
            'list' => $contactList,
            'colors' => ContactList::COLORS,
        ]);
    }

    /**
     * Update the specified list.
     */
    public function update(Request $request, ContactList $contactList): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'type' => 'required|in:static,dynamic',
            'segment_criteria' => 'nullable|array',
            'double_optin' => 'boolean',
            'welcome_email_content' => 'nullable|string',
            'default_from_name' => 'nullable|string|max:255',
            'default_from_email' => 'nullable|email|max:255',
        ]);

        $contactList->update($validated);

        return redirect()->route('contact-lists.index')
            ->with('success', 'Liste mise à jour avec succès.');
    }

    /**
     * Remove the specified list.
     */
    public function destroy(ContactList $contactList): RedirectResponse
    {
        $contactList->delete();

        return redirect()->route('contact-lists.index')
            ->with('success', 'Liste supprimée avec succès.');
    }
}
