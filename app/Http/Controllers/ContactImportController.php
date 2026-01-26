<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactImportController extends Controller
{
    /**
     * Show the import form.
     */
    public function showImportForm(): Response
    {
        $lists = ContactList::select('id', 'name', 'color')->get();
        $tags = Tag::select('id', 'name', 'color')->get();

        return Inertia::render('Contacts/Import', [
            'lists' => $lists,
            'tags' => $tags,
        ]);
    }

    /**
     * Handle CSV import.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'has_header' => 'boolean',
            'mapping' => 'required|array',
            'mapping.email' => 'required|integer|min:0',
            'lists' => 'nullable|array',
            'lists.*' => 'exists:contact_lists,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'update_existing' => 'boolean',
        ]);

        $file = $request->file('file');
        $hasHeader = $request->boolean('has_header', true);
        $mapping = $request->input('mapping');
        $listIds = $request->input('lists', []);
        $tagIds = $request->input('tags', []);
        $updateExisting = $request->boolean('update_existing', false);

        $tenantId = auth()->user()->tenant_id;
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        // Parse CSV
        $handle = fopen($file->getPathname(), 'r');
        $lineNumber = 0;

        // Map of column names to database fields
        $fieldMap = [
            'email' => 'email',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone',
            'company' => 'company',
            'job_title' => 'job_title',
            'address' => 'address',
            'city' => 'city',
            'country' => 'country',
            'postal_code' => 'postal_code',
        ];

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                // Skip header row
                if ($hasHeader && $lineNumber === 1) {
                    continue;
                }

                // Build contact data from mapping
                $contactData = [
                    'tenant_id' => $tenantId,
                    'source' => Contact::SOURCE_IMPORT,
                    'source_details' => $file->getClientOriginalName(),
                ];

                foreach ($mapping as $field => $columnIndex) {
                    if (isset($fieldMap[$field]) && isset($row[$columnIndex]) && $row[$columnIndex] !== '') {
                        $contactData[$fieldMap[$field]] = trim($row[$columnIndex]);
                    }
                }

                // Validate email
                if (empty($contactData['email'])) {
                    $skipped++;
                    $errors[] = "Ligne {$lineNumber}: Email manquant";
                    continue;
                }

                $validator = Validator::make($contactData, [
                    'email' => 'required|email',
                ]);

                if ($validator->fails()) {
                    $skipped++;
                    $errors[] = "Ligne {$lineNumber}: Email invalide ({$contactData['email']})";
                    continue;
                }

                // Check if contact exists
                $existingContact = Contact::where('tenant_id', $tenantId)
                    ->where('email', $contactData['email'])
                    ->first();

                if ($existingContact) {
                    if ($updateExisting) {
                        // Update existing contact
                        unset($contactData['tenant_id'], $contactData['source'], $contactData['source_details']);
                        $existingContact->update($contactData);
                        $contact = $existingContact;
                        $updated++;
                    } else {
                        $skipped++;
                        continue;
                    }
                } else {
                    // Create new contact
                    $contactData['status'] = Contact::STATUS_SUBSCRIBED;
                    $contactData['subscribed_at'] = now();
                    $contact = Contact::create($contactData);
                    $imported++;
                }

                // Add to lists
                if (!empty($listIds)) {
                    foreach ($listIds as $listId) {
                        $list = ContactList::find($listId);
                        $list?->addContact($contact);
                    }
                }

                // Add tags
                if (!empty($tagIds)) {
                    $existingTagIds = $contact->tags()->pluck('tags.id')->toArray();
                    $newTagIds = array_diff($tagIds, $existingTagIds);
                    if (!empty($newTagIds)) {
                        $contact->tags()->attach($newTagIds, ['tagged_at' => now()]);
                    }
                }
            }

            fclose($handle);

            // Update tag counts
            if (!empty($tagIds)) {
                Tag::whereIn('id', $tagIds)->each(fn($tag) => $tag->updateCount());
            }

            DB::commit();

            $message = "{$imported} contact(s) importé(s)";
            if ($updated > 0) {
                $message .= ", {$updated} mis à jour";
            }
            if ($skipped > 0) {
                $message .= ", {$skipped} ignoré(s)";
            }

            return redirect()->route('contacts.index')
                ->with('success', $message)
                ->with('import_errors', array_slice($errors, 0, 10)); // Limit to 10 errors

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'import: ' . $e->getMessage());
        }
    }

    /**
     * Preview CSV file and get column headers.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'has_header' => 'boolean',
        ]);

        $file = $request->file('file');
        $hasHeader = $request->boolean('has_header', true);

        $handle = fopen($file->getPathname(), 'r');
        $headers = [];
        $preview = [];
        $lineNumber = 0;

        while (($row = fgetcsv($handle)) !== false && $lineNumber < 6) {
            $lineNumber++;

            if ($hasHeader && $lineNumber === 1) {
                $headers = $row;
                continue;
            }

            $preview[] = $row;
        }

        fclose($handle);

        // If no headers, generate column numbers
        if (empty($headers) && !empty($preview)) {
            for ($i = 0; $i < count($preview[0]); $i++) {
                $headers[] = "Colonne " . ($i + 1);
            }
        }

        return response()->json([
            'headers' => $headers,
            'preview' => $preview,
            'total_columns' => count($headers),
        ]);
    }

    /**
     * Export contacts to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'list_id' => 'nullable|exists:contact_lists,id',
            'tag_id' => 'nullable|exists:tags,id',
            'status' => 'nullable|in:subscribed,unsubscribed,bounced,complained',
        ]);

        $query = Contact::query();

        // Apply filters
        if ($listId = $request->input('list_id')) {
            $query->inList($listId);
        }

        if ($tagId = $request->input('tag_id')) {
            $query->withTag($tagId);
        }

        if ($status = $request->input('status')) {
            $query->status($status);
        }

        $contacts = $query->get();

        $filename = 'contacts_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($handle, [
                'Email',
                'Prénom',
                'Nom',
                'Téléphone',
                'Entreprise',
                'Poste',
                'Adresse',
                'Ville',
                'Pays',
                'Code postal',
                'Statut',
                'Date d\'inscription',
                'Score d\'engagement',
            ]);

            // Data
            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->email,
                    $contact->first_name,
                    $contact->last_name,
                    $contact->phone,
                    $contact->company,
                    $contact->job_title,
                    $contact->address,
                    $contact->city,
                    $contact->country,
                    $contact->postal_code,
                    $contact->status,
                    $contact->subscribed_at?->format('Y-m-d H:i:s'),
                    $contact->engagement_score,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Download sample CSV template.
     */
    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($handle, [
                'email',
                'first_name',
                'last_name',
                'phone',
                'company',
                'job_title',
                'address',
                'city',
                'country',
                'postal_code',
            ]);

            // Sample data
            fputcsv($handle, [
                'jean.dupont@exemple.com',
                'Jean',
                'Dupont',
                '+226 70 00 00 00',
                'Entreprise SARL',
                'Directeur',
                '123 Rue Principale',
                'Ouagadougou',
                'Burkina Faso',
                '01 BP 1234',
            ]);

            fputcsv($handle, [
                'marie.diallo@exemple.com',
                'Marie',
                'Diallo',
                '+226 71 11 11 11',
                'Commerce Plus',
                'Responsable Marketing',
                '45 Avenue de la Liberté',
                'Bobo-Dioulasso',
                'Burkina Faso',
                '01 BP 5678',
            ]);

            fclose($handle);
        }, 'template_import_contacts.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
