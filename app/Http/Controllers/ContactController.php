<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts.
     */
    public function index(Request $request): Response
    {
        $query = Contact::query()
            ->with(['lists', 'tags']);

        // Search
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->status($status);
        }

        // Filter by list
        if ($listId = $request->get('list')) {
            $query->inList($listId);
        }

        // Filter by tag
        if ($tagId = $request->get('tag')) {
            $query->withTag($tagId);
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $contacts = $query->paginate(25)->withQueryString();

        // Get lists and tags for filters
        $lists = ContactList::select('id', 'name', 'color', 'contacts_count')->get();
        $tags = Tag::select('id', 'name', 'color', 'contacts_count')->get();

        // Stats
        $stats = [
            'total' => Contact::count(),
            'subscribed' => Contact::subscribed()->count(),
            'unsubscribed' => Contact::status(Contact::STATUS_UNSUBSCRIBED)->count(),
            'bounced' => Contact::status(Contact::STATUS_BOUNCED)->count(),
        ];

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'lists' => $lists,
            'tags' => $tags,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'list', 'tag', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create(): Response
    {
        $lists = ContactList::select('id', 'name', 'color')->get();
        $tags = Tag::select('id', 'name', 'color')->get();

        return Inertia::render('Contacts/Create', [
            'lists' => $lists,
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created contact.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('contacts')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                }),
            ],
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'custom_fields' => 'nullable|array',
            'status' => 'nullable|in:subscribed,unsubscribed',
            'lists' => 'nullable|array',
            'lists.*' => 'exists:contact_lists,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $contact = Contact::create([
            ...$validated,
            'tenant_id' => auth()->user()->tenant_id,
            'source' => Contact::SOURCE_MANUAL,
            'subscribed_at' => $validated['status'] !== 'unsubscribed' ? now() : null,
        ]);

        // Attach to lists
        if (!empty($validated['lists'])) {
            foreach ($validated['lists'] as $listId) {
                $list = ContactList::find($listId);
                $list?->addContact($contact);
            }
        }

        // Attach tags
        if (!empty($validated['tags'])) {
            $contact->tags()->attach($validated['tags'], ['tagged_at' => now()]);
            Tag::whereIn('id', $validated['tags'])->each(fn($tag) => $tag->updateCount());
        }

        return redirect()->route('contacts.index')
            ->with('success', 'Contact créé avec succès.');
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): Response
    {
        $contact->load(['lists', 'tags']);

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Contact $contact): Response
    {
        $contact->load(['lists', 'tags']);

        $lists = ContactList::select('id', 'name', 'color')->get();
        $tags = Tag::select('id', 'name', 'color')->get();

        return Inertia::render('Contacts/Edit', [
            'contact' => $contact,
            'lists' => $lists,
            'tags' => $tags,
        ]);
    }

    /**
     * Update the specified contact.
     */
    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('contacts')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                })->ignore($contact->id),
            ],
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'custom_fields' => 'nullable|array',
            'status' => 'nullable|in:subscribed,unsubscribed,bounced,complained',
            'lists' => 'nullable|array',
            'lists.*' => 'exists:contact_lists,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        // Handle status change
        if (isset($validated['status']) && $validated['status'] !== $contact->status) {
            if ($validated['status'] === Contact::STATUS_UNSUBSCRIBED) {
                $validated['unsubscribed_at'] = now();
            } elseif ($contact->status === Contact::STATUS_UNSUBSCRIBED && $validated['status'] === Contact::STATUS_SUBSCRIBED) {
                $validated['subscribed_at'] = now();
                $validated['unsubscribed_at'] = null;
                $validated['unsubscribe_reason'] = null;
            }
        }

        $contact->update($validated);

        // Sync lists
        if (isset($validated['lists'])) {
            $currentLists = $contact->lists()->pluck('contact_lists.id')->toArray();
            $newLists = $validated['lists'];

            // Remove from old lists
            $toRemove = array_diff($currentLists, $newLists);
            foreach ($toRemove as $listId) {
                $list = ContactList::find($listId);
                $list?->removeContact($contact);
            }

            // Add to new lists
            $toAdd = array_diff($newLists, $currentLists);
            foreach ($toAdd as $listId) {
                $list = ContactList::find($listId);
                $list?->addContact($contact);
            }
        }

        // Sync tags
        if (isset($validated['tags'])) {
            $currentTags = $contact->tags()->pluck('tags.id')->toArray();
            $newTags = $validated['tags'];

            // Remove old tags
            $tagsToRemove = array_diff($currentTags, $newTags);
            if (!empty($tagsToRemove)) {
                $contact->tags()->detach($tagsToRemove);
                Tag::whereIn('id', $tagsToRemove)->each(fn($tag) => $tag->updateCount());
            }

            // Add new tags
            $tagsToAdd = array_diff($newTags, $currentTags);
            if (!empty($tagsToAdd)) {
                $contact->tags()->attach($tagsToAdd, ['tagged_at' => now()]);
                Tag::whereIn('id', $tagsToAdd)->each(fn($tag) => $tag->updateCount());
            }
        }

        return redirect()->route('contacts.index')
            ->with('success', 'Contact mis à jour avec succès.');
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        // Update list counts
        $listIds = $contact->lists()->pluck('contact_lists.id')->toArray();
        $tagIds = $contact->tags()->pluck('tags.id')->toArray();

        $contact->delete();

        // Update counts
        ContactList::whereIn('id', $listIds)->each(fn($list) => $list->updateCounts());
        Tag::whereIn('id', $tagIds)->each(fn($tag) => $tag->updateCount());

        return redirect()->route('contacts.index')
            ->with('success', 'Contact supprimé avec succès.');
    }

    /**
     * Bulk delete contacts.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
        ]);

        $contacts = Contact::whereIn('id', $validated['ids'])->get();

        // Collect list and tag IDs for updating counts
        $listIds = [];
        $tagIds = [];

        foreach ($contacts as $contact) {
            $listIds = array_merge($listIds, $contact->lists()->pluck('contact_lists.id')->toArray());
            $tagIds = array_merge($tagIds, $contact->tags()->pluck('tags.id')->toArray());
        }

        Contact::whereIn('id', $validated['ids'])->delete();

        // Update counts
        ContactList::whereIn('id', array_unique($listIds))->each(fn($list) => $list->updateCounts());
        Tag::whereIn('id', array_unique($tagIds))->each(fn($tag) => $tag->updateCount());

        return redirect()->route('contacts.index')
            ->with('success', count($validated['ids']) . ' contacts supprimés avec succès.');
    }

    /**
     * Bulk add contacts to a list.
     */
    public function bulkAddToList(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
            'list_id' => 'required|exists:contact_lists,id',
        ]);

        $list = ContactList::findOrFail($validated['list_id']);
        $list->addContacts($validated['ids']);

        return redirect()->back()
            ->with('success', count($validated['ids']) . ' contacts ajoutés à la liste.');
    }

    /**
     * Bulk remove contacts from a list.
     */
    public function bulkRemoveFromList(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
            'list_id' => 'required|exists:contact_lists,id',
        ]);

        $list = ContactList::findOrFail($validated['list_id']);
        $list->removeContacts($validated['ids']);

        return redirect()->back()
            ->with('success', count($validated['ids']) . ' contacts retirés de la liste.');
    }

    /**
     * Bulk add tags to contacts.
     */
    public function bulkAddTags(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        foreach ($validated['tag_ids'] as $tagId) {
            $tag = Tag::find($tagId);
            $tag?->addToContacts($validated['ids']);
        }

        return redirect()->back()
            ->with('success', 'Tags ajoutés aux contacts sélectionnés.');
    }

    /**
     * Bulk remove tags from contacts.
     */
    public function bulkRemoveTags(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contacts,id',
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        foreach ($validated['tag_ids'] as $tagId) {
            $tag = Tag::find($tagId);
            $tag?->removeFromContacts($validated['ids']);
        }

        return redirect()->back()
            ->with('success', 'Tags retirés des contacts sélectionnés.');
    }
}
