<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactImportTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function user_can_access_import_page()
    {
        $response = $this->actingAs($this->user)
            ->get('/contacts/import');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_import_csv_file()
    {
        Storage::fake('local');

        $csvContent = "email,first_name,last_name,company\n";
        $csvContent .= "john@example.com,John,Doe,Acme Inc\n";
        $csvContent .= "jane@example.com,Jane,Smith,Tech Corp\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->post('/contacts/import/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'headers',
            'preview',
            'total_rows',
            'file_path',
        ]);
    }

    /** @test */
    public function user_can_map_columns_and_process_import()
    {
        Storage::fake('local');

        $csvContent = "email,first_name,last_name\n";
        $csvContent .= "test1@example.com,Test,One\n";
        $csvContent .= "test2@example.com,Test,Two\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        // First upload
        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        // Then process with mapping
        $response = $this->actingAs($this->user)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => [
                    'email' => 'email',
                    'first_name' => 'first_name',
                    'last_name' => 'last_name',
                ],
                'list_ids' => [],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('contacts', [
            'tenant_id' => $this->tenant->id,
            'email' => 'test1@example.com',
            'first_name' => 'Test',
        ]);

        $this->assertDatabaseHas('contacts', [
            'email' => 'test2@example.com',
        ]);
    }

    /** @test */
    public function import_validates_email_addresses()
    {
        Storage::fake('local');

        $csvContent = "email,first_name\n";
        $csvContent .= "valid@example.com,Valid\n";
        $csvContent .= "invalid-email,Invalid\n";
        $csvContent .= "another@test.com,Another\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        $response = $this->actingAs($this->user)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => [
                    'email' => 'email',
                    'first_name' => 'first_name',
                ],
            ]);

        // Valid emails should be imported
        $this->assertDatabaseHas('contacts', ['email' => 'valid@example.com']);
        $this->assertDatabaseHas('contacts', ['email' => 'another@test.com']);

        // Invalid email should not be imported
        $this->assertDatabaseMissing('contacts', ['first_name' => 'Invalid']);
    }

    /** @test */
    public function import_handles_duplicates()
    {
        // Create existing contact
        Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
        ]);

        Storage::fake('local');

        $csvContent = "email,first_name\n";
        $csvContent .= "existing@example.com,Updated\n";
        $csvContent .= "new@example.com,New\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        $response = $this->actingAs($this->user)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => [
                    'email' => 'email',
                    'first_name' => 'first_name',
                ],
                'update_existing' => true,
            ]);

        // Existing contact should be updated
        $this->assertDatabaseHas('contacts', [
            'email' => 'existing@example.com',
            'first_name' => 'Updated',
        ]);

        // New contact should be created
        $this->assertDatabaseHas('contacts', [
            'email' => 'new@example.com',
        ]);

        // Should have 2 contacts total
        $this->assertEquals(2, Contact::where('tenant_id', $this->tenant->id)->count());
    }

    /** @test */
    public function import_adds_contacts_to_list()
    {
        $list = ContactList::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Newsletter',
        ]);

        Storage::fake('local');

        $csvContent = "email,first_name\n";
        $csvContent .= "subscriber1@example.com,Sub One\n";
        $csvContent .= "subscriber2@example.com,Sub Two\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        $response = $this->actingAs($this->user)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => [
                    'email' => 'email',
                    'first_name' => 'first_name',
                ],
                'list_ids' => [$list->id],
            ]);

        $contact1 = Contact::where('email', 'subscriber1@example.com')->first();
        $contact2 = Contact::where('email', 'subscriber2@example.com')->first();

        $this->assertTrue($contact1->lists->contains($list->id));
        $this->assertTrue($contact2->lists->contains($list->id));
    }

    /** @test */
    public function import_handles_custom_fields()
    {
        Storage::fake('local');

        $csvContent = "email,first_name,loyalty_level,points\n";
        $csvContent .= "member@example.com,Member,Gold,1500\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        $response = $this->actingAs($this->user)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => [
                    'email' => 'email',
                    'first_name' => 'first_name',
                    'loyalty_level' => 'custom:loyalty_level',
                    'points' => 'custom:points',
                ],
            ]);

        $contact = Contact::where('email', 'member@example.com')->first();

        $this->assertNotNull($contact);
        $this->assertEquals('Gold', $contact->custom_fields['loyalty_level'] ?? null);
        $this->assertEquals('1500', $contact->custom_fields['points'] ?? null);
    }

    /** @test */
    public function import_rejects_invalid_file_type()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->post('/contacts/import/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function import_requires_email_mapping()
    {
        Storage::fake('local');

        $csvContent = "name,company\n";
        $csvContent .= "John Doe,Acme\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        $response = $this->actingAs($this->user)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => [
                    'name' => 'first_name',
                    'company' => 'company',
                ],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function import_respects_tenant_isolation()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        Storage::fake('local');

        $csvContent = "email,first_name\n";
        $csvContent .= "other@example.com,Other\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($otherUser)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        $this->actingAs($otherUser)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => ['email' => 'email', 'first_name' => 'first_name'],
            ]);

        // Contact should belong to other tenant
        $this->assertDatabaseHas('contacts', [
            'tenant_id' => $otherTenant->id,
            'email' => 'other@example.com',
        ]);

        // Should not be visible to original tenant
        $this->assertEquals(0, Contact::where('tenant_id', $this->tenant->id)->count());
    }

    /** @test */
    public function import_handles_large_file_with_queue()
    {
        Storage::fake('local');

        // Create a larger CSV
        $csvContent = "email,first_name,last_name\n";
        for ($i = 0; $i < 500; $i++) {
            $csvContent .= "contact{$i}@example.com,First{$i},Last{$i}\n";
        }

        $file = UploadedFile::fake()->createWithContent('large_contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $this->assertEquals(500, $uploadResponse->json('total_rows'));
    }

    /** @test */
    public function import_tracks_import_source()
    {
        Storage::fake('local');

        $csvContent = "email,first_name\n";
        $csvContent .= "tracked@example.com,Tracked\n";

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $uploadResponse = $this->actingAs($this->user)
            ->post('/contacts/import/upload', ['file' => $file]);

        $uploadData = $uploadResponse->json();

        $this->actingAs($this->user)
            ->post('/contacts/import/process', [
                'file_path' => $uploadData['file_path'],
                'mapping' => ['email' => 'email', 'first_name' => 'first_name'],
            ]);

        $contact = Contact::where('email', 'tracked@example.com')->first();
        $this->assertEquals('import', $contact->source);
    }
}
