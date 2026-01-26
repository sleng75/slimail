<?php

namespace App\Services\Contact;

use App\Models\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DuplicateDetectionService
{
    /**
     * Find all duplicate contacts for a tenant.
     */
    public function findDuplicates(int $tenantId): Collection
    {
        // Find duplicates by email (exact match)
        $emailDuplicates = DB::table('contacts')
            ->select('email', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();

        $duplicateGroups = [];

        foreach ($emailDuplicates as $group) {
            $ids = explode(',', $group->ids);
            $contacts = Contact::whereIn('id', $ids)
                ->orderBy('created_at', 'asc')
                ->get();

            $duplicateGroups[] = [
                'type' => 'email',
                'match_value' => $group->email,
                'count' => $group->count,
                'contacts' => $contacts,
                'suggested_keep' => $contacts->first(), // Keep the oldest
            ];
        }

        // Find duplicates by phone (if not empty)
        $phoneDuplicates = DB::table('contacts')
            ->select('phone', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy('phone')
            ->having('count', '>', 1)
            ->get();

        foreach ($phoneDuplicates as $group) {
            $ids = explode(',', $group->ids);
            $contacts = Contact::whereIn('id', $ids)
                ->orderBy('created_at', 'asc')
                ->get();

            // Check if this group is not already in email duplicates
            $existingEmails = collect($duplicateGroups)
                ->where('type', 'email')
                ->pluck('contacts')
                ->flatten()
                ->pluck('id')
                ->toArray();

            $newIds = array_diff($ids, $existingEmails);

            if (count($newIds) > 1) {
                $duplicateGroups[] = [
                    'type' => 'phone',
                    'match_value' => $group->phone,
                    'count' => count($newIds),
                    'contacts' => $contacts,
                    'suggested_keep' => $contacts->first(),
                ];
            }
        }

        return collect($duplicateGroups);
    }

    /**
     * Check if a contact is a duplicate before creation.
     */
    public function checkForDuplicate(int $tenantId, string $email, ?string $phone = null): ?Contact
    {
        // Check by email first
        $duplicate = Contact::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        if ($duplicate) {
            return $duplicate;
        }

        // Check by phone if provided
        if ($phone) {
            $duplicate = Contact::where('tenant_id', $tenantId)
                ->where('phone', $phone)
                ->first();

            if ($duplicate) {
                return $duplicate;
            }
        }

        return null;
    }

    /**
     * Find potential duplicates for a list of emails (for import).
     */
    public function findDuplicatesInList(int $tenantId, array $emails): array
    {
        $existingContacts = Contact::where('tenant_id', $tenantId)
            ->whereIn('email', $emails)
            ->pluck('email')
            ->toArray();

        return $existingContacts;
    }

    /**
     * Merge duplicate contacts.
     */
    public function mergeContacts(Contact $keepContact, array $mergeContactIds): Contact
    {
        $mergeContacts = Contact::whereIn('id', $mergeContactIds)
            ->where('tenant_id', $keepContact->tenant_id)
            ->where('id', '!=', $keepContact->id)
            ->get();

        foreach ($mergeContacts as $contact) {
            // Merge tags
            $contactTags = $contact->tags()->pluck('tags.id')->toArray();
            $keepContact->tags()->syncWithoutDetaching($contactTags);

            // Merge lists
            foreach ($contact->lists as $list) {
                if (!$keepContact->lists()->where('contact_lists.id', $list->id)->exists()) {
                    $keepContact->lists()->attach($list->id, [
                        'status' => $list->pivot->status,
                        'subscribed_at' => $list->pivot->subscribed_at,
                    ]);
                }
            }

            // Merge custom fields (keep existing, add new)
            $keepFields = $keepContact->custom_fields ?? [];
            $mergeFields = $contact->custom_fields ?? [];

            foreach ($mergeFields as $key => $value) {
                if (!isset($keepFields[$key]) || empty($keepFields[$key])) {
                    $keepFields[$key] = $value;
                }
            }
            $keepContact->custom_fields = $keepFields;

            // Merge engagement stats
            $keepContact->emails_sent += $contact->emails_sent;
            $keepContact->emails_opened += $contact->emails_opened;
            $keepContact->emails_clicked += $contact->emails_clicked;

            // Fill empty fields from merged contact
            $fieldsToMerge = ['first_name', 'last_name', 'phone', 'company', 'job_title', 'address', 'city', 'country', 'postal_code'];

            foreach ($fieldsToMerge as $field) {
                if (empty($keepContact->$field) && !empty($contact->$field)) {
                    $keepContact->$field = $contact->$field;
                }
            }

            // Soft delete merged contact
            $contact->delete();
        }

        // Recalculate engagement
        $keepContact->recalculateEngagement();
        $keepContact->save();

        return $keepContact;
    }

    /**
     * Get duplicate statistics for a tenant.
     */
    public function getDuplicateStats(int $tenantId): array
    {
        $emailDuplicateCount = DB::table('contacts')
            ->select('email', DB::raw('COUNT(*) as count'))
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->groupBy('email')
            ->having('count', '>', 1)
            ->count();

        $totalDuplicateContacts = DB::table('contacts as c1')
            ->join(DB::raw('(SELECT email FROM contacts WHERE tenant_id = ' . $tenantId . ' AND deleted_at IS NULL GROUP BY email HAVING COUNT(*) > 1) as c2'), 'c1.email', '=', 'c2.email')
            ->where('c1.tenant_id', $tenantId)
            ->whereNull('c1.deleted_at')
            ->count();

        return [
            'duplicate_groups' => $emailDuplicateCount,
            'total_duplicate_contacts' => $totalDuplicateContacts,
            'potential_savings' => $totalDuplicateContacts - $emailDuplicateCount, // Contacts that could be merged
        ];
    }

    /**
     * Fuzzy match for names (simple Levenshtein distance).
     */
    public function findSimilarNames(int $tenantId, string $firstName, string $lastName, int $threshold = 3): Collection
    {
        $fullName = strtolower(trim("{$firstName} {$lastName}"));

        return Contact::where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->get()
            ->filter(function ($contact) use ($fullName, $threshold) {
                $contactName = strtolower(trim("{$contact->first_name} {$contact->last_name}"));
                return levenshtein($fullName, $contactName) <= $threshold;
            });
    }
}
