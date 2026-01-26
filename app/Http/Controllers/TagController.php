<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request): Response
    {
        $query = Tag::query();

        // Search
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        $tags = $query->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Contacts/Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                }),
            ],
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        // Explicitly set tenant_id
        $validated['tenant_id'] = auth()->user()->tenant_id;

        Tag::create($validated);

        return redirect()->back()
            ->with('success', 'Tag créé avec succès.');
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags')->where(function ($query) {
                    return $query->where('tenant_id', auth()->user()->tenant_id);
                })->ignore($tag->id),
            ],
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        $tag->update($validated);

        return redirect()->back()
            ->with('success', 'Tag mis à jour avec succès.');
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()->back()
            ->with('success', 'Tag supprimé avec succès.');
    }
}
