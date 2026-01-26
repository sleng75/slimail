<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of templates.
     */
    public function index(Request $request): Response
    {
        $query = EmailTemplate::query()
            ->with('creator:id,name');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($category = $request->get('category')) {
            $query->category($category);
        }

        // Filter by status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $templates = $query->paginate(12)->withQueryString();

        // Get categories for filter
        $categories = EmailTemplate::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category');

        // Stats
        $stats = [
            'total' => EmailTemplate::count(),
            'active' => EmailTemplate::active()->count(),
            'system' => EmailTemplate::where('is_system', true)->count(),
        ];

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'active', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): Response
    {
        return Inertia::render('Templates/Create', [
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'html_content' => 'nullable|string',
            'text_content' => 'nullable|string',
            'design_json' => 'nullable|array',
            'default_subject' => 'nullable|string|max:255',
            'default_from_name' => 'nullable|string|max:255',
            'default_from_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $template = EmailTemplate::create([
            ...$validated,
            'tenant_id' => auth()->user()->tenant_id,
            'created_by' => auth()->id(),
            'is_system' => false,
        ]);

        return redirect()->route('templates.edit', $template)
            ->with('success', 'Template créé avec succès.');
    }

    /**
     * Display the specified template.
     */
    public function show(EmailTemplate $template): Response
    {
        $template->load('creator:id,name');

        return Inertia::render('Templates/Show', [
            'template' => $template,
        ]);
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(EmailTemplate $template): Response
    {
        $template->load('creator:id,name');

        return Inertia::render('Templates/Edit', [
            'template' => $template,
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, EmailTemplate $template): RedirectResponse|JsonResponse
    {
        // Prevent editing system templates
        if ($template->is_system) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Les templates système ne peuvent pas être modifiés.'], 403);
            }
            return redirect()->back()
                ->with('error', 'Les templates système ne peuvent pas être modifiés.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'html_content' => 'nullable|string',
            'text_content' => 'nullable|string',
            'design_json' => 'nullable|array',
            'default_subject' => 'nullable|string|max:255',
            'default_from_name' => 'nullable|string|max:255',
            'default_from_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template sauvegardé.',
                'template' => $template,
            ]);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template mis à jour avec succès.');
    }

    /**
     * Auto-save the template content.
     */
    public function autosave(Request $request, EmailTemplate $template): JsonResponse
    {
        if ($template->is_system) {
            return response()->json(['error' => 'Les templates système ne peuvent pas être modifiés.'], 403);
        }

        $validated = $request->validate([
            'html_content' => 'nullable|string',
            'design_json' => 'nullable|array',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Remove the specified template.
     */
    public function destroy(EmailTemplate $template): RedirectResponse
    {
        // Prevent deleting system templates
        if ($template->is_system) {
            return redirect()->back()
                ->with('error', 'Les templates système ne peuvent pas être supprimés.');
        }

        // Check if template is used in campaigns
        if ($template->campaigns()->exists()) {
            return redirect()->back()
                ->with('error', 'Ce template est utilisé dans des campagnes et ne peut pas être supprimé.');
        }

        $template->delete();

        return redirect()->route('templates.index')
            ->with('success', 'Template supprimé avec succès.');
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(EmailTemplate $template): RedirectResponse
    {
        $newTemplate = $template->replicate([
            'usage_count',
            'is_system',
        ]);

        $newTemplate->name = $template->name . ' (copie)';
        $newTemplate->created_by = auth()->id();
        $newTemplate->is_system = false;
        $newTemplate->usage_count = 0;
        $newTemplate->save();

        return redirect()->route('templates.edit', $newTemplate)
            ->with('success', 'Template dupliqué avec succès.');
    }

    /**
     * Preview template with sample data.
     */
    public function preview(Request $request, EmailTemplate $template): JsonResponse
    {
        $sampleData = [
            'contact.first_name' => 'Jean',
            'contact.last_name' => 'Dupont',
            'contact.email' => 'jean.dupont@example.com',
            'contact.company' => 'Exemple SARL',
            'unsubscribe_url' => url('/unsubscribe/sample'),
            'view_in_browser_url' => url('/view/sample'),
            'current_year' => date('Y'),
        ];

        // Merge with custom data if provided
        if ($request->has('data')) {
            $sampleData = array_merge($sampleData, $request->get('data', []));
        }

        $renderedHtml = $template->render($sampleData);

        return response()->json([
            'html' => $renderedHtml,
            'subject' => $this->renderString($template->default_subject ?? '', $sampleData),
        ]);
    }

    /**
     * Get available template categories.
     */
    private function getCategories(): array
    {
        return [
            'newsletter' => 'Newsletter',
            'promotional' => 'Promotionnel',
            'transactional' => 'Transactionnel',
            'notification' => 'Notification',
            'welcome' => 'Bienvenue',
            'abandoned_cart' => 'Panier abandonné',
            'event' => 'Événement',
            'survey' => 'Enquête',
            'other' => 'Autre',
        ];
    }

    /**
     * Render variables in a string.
     */
    private function renderString(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }

        return $content;
    }

    /**
     * Get template library (pre-built templates).
     */
    public function library(): Response
    {
        $templates = EmailTemplate::where('is_system', true)
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $groupedTemplates = $templates->groupBy('category');

        return Inertia::render('Templates/Library', [
            'templates' => $groupedTemplates,
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Use a template from the library.
     */
    public function useFromLibrary(EmailTemplate $template): RedirectResponse
    {
        if (!$template->is_system) {
            return redirect()->back()
                ->with('error', 'Ce template n\'est pas dans la bibliothèque.');
        }

        $newTemplate = $template->replicate([
            'usage_count',
            'is_system',
        ]);

        $newTemplate->name = $template->name;
        $newTemplate->created_by = auth()->id();
        $newTemplate->is_system = false;
        $newTemplate->usage_count = 0;
        $newTemplate->save();

        // Increment usage count on original
        $template->incrementUsage();

        return redirect()->route('templates.edit', $newTemplate)
            ->with('success', 'Template ajouté à votre collection.');
    }
}
