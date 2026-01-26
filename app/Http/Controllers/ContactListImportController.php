<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactListImportController extends Controller
{
    /**
     * Show the import form for a specific list.
     */
    public function showImportForm(ContactList $contactList): Response
    {
        return Inertia::render('Contacts/Lists/Import', [
            'list' => $contactList,
        ]);
    }

    /**
     * Preview the CSV file for import.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'has_header' => 'required|boolean',
        ]);

        $file = $request->file('file');
        $hasHeader = (bool) $request->input('has_header');

        $handle = fopen($file->getPathname(), 'r');
        $headers = [];
        $preview = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false && $rowCount < 10) {
            if ($rowCount === 0 && $hasHeader) {
                $headers = $row;
            } else {
                if (empty($headers)) {
                    $headers = array_map(fn($i) => "Colonne " . ($i + 1), array_keys($row));
                }
                $preview[] = $row;
            }
            $rowCount++;
        }

        fclose($handle);

        return response()->json([
            'headers' => $headers,
            'preview' => $preview,
        ]);
    }

    /**
     * Import contacts into a list from CSV.
     */
    public function import(Request $request, ContactList $contactList): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'has_header' => 'required|boolean',
            'mapping' => 'required|array',
            'mapping.email' => 'required|integer',
            'update_existing' => 'boolean',
        ]);

        $file = $request->file('file');
        $hasHeader = (bool) $request->input('has_header');
        $mapping = $request->input('mapping');
        $updateExisting = $request->boolean('update_existing', false);
        $tenantId = auth()->user()->tenant_id;

        $handle = fopen($file->getPathname(), 'r');
        $rowCount = 0;
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            // Skip header row
            if ($rowCount === 0 && $hasHeader) {
                $rowCount++;
                continue;
            }

            $email = $this->getValueFromMapping($row, $mapping, 'email');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $rowCount++;
                continue;
            }

            // Find or create contact
            $contact = Contact::where('email', $email)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($contact) {
                if ($updateExisting) {
                    $this->updateContactFromRow($contact, $row, $mapping);
                    $updated++;
                }
            } else {
                $contact = $this->createContactFromRow($row, $mapping, $tenantId);
                $imported++;
            }

            // Add to list if not already in it
            if ($contact) {
                $contactList->addContact($contact);
            }

            $rowCount++;
        }

        fclose($handle);

        $message = "{$imported} contacts importés";
        if ($updated > 0) {
            $message .= ", {$updated} mis à jour";
        }
        if ($skipped > 0) {
            $message .= ", {$skipped} ignorés";
        }

        return redirect()->route('contact-lists.show', $contactList)
            ->with('success', $message);
    }

    /**
     * Export contacts from a list to CSV.
     */
    public function export(ContactList $contactList): StreamedResponse
    {
        $filename = 'liste_' . str_replace(' ', '_', strtolower($contactList->name)) . '_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($contactList) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
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
                'Score engagement',
                'Date inscription',
            ]);

            // Data
            $contactList->contacts()->chunk(500, function ($contacts) use ($handle) {
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
                        $contact->engagement_score,
                        $contact->subscribed_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Download a CSV template for list import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($handle, [
                'email',
                'prenom',
                'nom',
                'telephone',
                'entreprise',
                'poste',
                'adresse',
                'ville',
                'pays',
                'code_postal',
            ]);

            // Example rows
            fputcsv($handle, [
                'jean.dupont@exemple.com',
                'Jean',
                'Dupont',
                '+225 07 00 00 00 00',
                'Entreprise SA',
                'Directeur',
                '123 Rue Exemple',
                'Abidjan',
                'Côte d\'Ivoire',
                '00000',
            ]);

            fputcsv($handle, [
                'marie.konan@exemple.com',
                'Marie',
                'Konan',
                '+225 05 00 00 00 00',
                'Startup SAS',
                'Manager',
                '456 Avenue Test',
                'Dakar',
                'Sénégal',
                '00000',
            ]);

            fclose($handle);
        }, 'template_import_liste.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Get value from row using mapping.
     */
    private function getValueFromMapping(array $row, array $mapping, string $field): ?string
    {
        $index = $mapping[$field] ?? null;
        if ($index === null || !isset($row[$index])) {
            return null;
        }
        return trim($row[$index]) ?: null;
    }

    /**
     * Create a contact from CSV row.
     */
    private function createContactFromRow(array $row, array $mapping, int $tenantId): Contact
    {
        return Contact::create([
            'tenant_id' => $tenantId,
            'email' => $this->getValueFromMapping($row, $mapping, 'email'),
            'first_name' => $this->getValueFromMapping($row, $mapping, 'first_name'),
            'last_name' => $this->getValueFromMapping($row, $mapping, 'last_name'),
            'phone' => $this->getValueFromMapping($row, $mapping, 'phone'),
            'company' => $this->getValueFromMapping($row, $mapping, 'company'),
            'job_title' => $this->getValueFromMapping($row, $mapping, 'job_title'),
            'address' => $this->getValueFromMapping($row, $mapping, 'address'),
            'city' => $this->getValueFromMapping($row, $mapping, 'city'),
            'country' => $this->getValueFromMapping($row, $mapping, 'country'),
            'postal_code' => $this->getValueFromMapping($row, $mapping, 'postal_code'),
            'status' => Contact::STATUS_SUBSCRIBED,
            'source' => Contact::SOURCE_IMPORT,
            'subscribed_at' => now(),
        ]);
    }

    /**
     * Update a contact from CSV row.
     */
    private function updateContactFromRow(Contact $contact, array $row, array $mapping): void
    {
        $updates = [];

        foreach (['first_name', 'last_name', 'phone', 'company', 'job_title', 'address', 'city', 'country', 'postal_code'] as $field) {
            $value = $this->getValueFromMapping($row, $mapping, $field);
            if ($value !== null) {
                $updates[$field] = $value;
            }
        }

        if (!empty($updates)) {
            $contact->update($updates);
        }
    }
}
