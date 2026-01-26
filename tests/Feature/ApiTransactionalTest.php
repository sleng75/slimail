<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Contact;
use App\Models\SentEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Amazon\SESService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ApiTransactionalTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->apiKey = ApiKey::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test API Key',
            'scopes' => ['send:transactional'],
        ]);
    }

    /** @test */
    public function it_sends_transactional_email_via_api()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message_id' => 'ses-message-id-123',
                ]);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'from_name' => 'Test Sender',
            'to_email' => 'recipient@example.com',
            'to_name' => 'Test Recipient',
            'subject' => 'Test Email',
            'html_content' => '<p>Hello World</p>',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message_id' => 'ses-message-id-123',
            ]);

        $this->assertDatabaseHas('sent_emails', [
            'tenant_id' => $this->tenant->id,
            'api_key_id' => $this->apiKey->id,
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'type' => SentEmail::TYPE_TRANSACTIONAL,
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_rejects_invalid_api_key()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-key',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_email', 'to_email', 'subject']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'invalid-email',
            'to_email' => 'also-invalid',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_email', 'to_email']);
    }

    /** @test */
    public function it_sends_email_with_text_content()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->once()
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Text Email',
            'text_content' => 'Plain text content',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_sends_email_with_reply_to()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->once()
                ->withArgs(function ($args) {
                    return $args['reply_to'] === 'reply@example.com';
                })
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
            'reply_to' => 'reply@example.com',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_sends_email_with_attachments()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendRawEmail')
                ->once()
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test with Attachment',
            'html_content' => '<p>See attached</p>',
            'attachments' => [
                [
                    'filename' => 'document.pdf',
                    'content' => base64_encode('PDF content'),
                    'content_type' => 'application/pdf',
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_tracks_api_key_usage()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        $initialUsage = $this->apiKey->requests_count;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $this->apiKey->refresh();
        $this->assertEquals($initialUsage + 1, $this->apiKey->requests_count);
    }

    /** @test */
    public function it_links_to_existing_contact()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'existing@example.com',
        ]);

        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'existing@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sent_emails', [
            'contact_id' => $contact->id,
            'to_email' => 'existing@example.com',
        ]);
    }

    /** @test */
    public function it_handles_send_failure()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => 'Email address is not verified',
                ]);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'unverified@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => 'Email address is not verified',
            ]);
    }

    /** @test */
    public function it_respects_api_key_scopes()
    {
        $limitedKey = ApiKey::factory()->create([
            'tenant_id' => $this->tenant->id,
            'scopes' => ['read:contacts'], // No send scope
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $limitedKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_rejects_disabled_api_key()
    {
        $this->apiKey->update(['is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_includes_metadata_in_sent_email()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
            'metadata' => [
                'order_id' => '12345',
                'user_id' => 'user-abc',
            ],
        ]);

        $response->assertStatus(200);

        $sentEmail = SentEmail::where('to_email', 'recipient@example.com')->first();
        $this->assertEquals('12345', $sentEmail->metadata['order_id'] ?? null);
    }

    /** @test */
    public function it_processes_personalization_variables()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->once()
                ->withArgs(function ($args) {
                    return str_contains($args['html_content'], 'Hello John');
                })
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'john@example.com',
            'subject' => 'Hello {{first_name}}',
            'html_content' => '<p>Hello {{first_name}} {{last_name}}</p>',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_email_id_in_response()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->andReturn(['success' => true, 'message_id' => 'ses-123']);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message_id', 'email_id']);
    }

    /** @test */
    public function it_rate_limits_requests()
    {
        $this->mock(SESService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                ->andReturn(['success' => true, 'message_id' => 'test-id']);
        });

        // Send multiple requests
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey->key,
                'Accept' => 'application/json',
            ])->postJson('/api/v1/send', [
                'from_email' => 'sender@example.com',
                'to_email' => 'recipient@example.com',
                'subject' => 'Test ' . $i,
                'html_content' => '<p>Test</p>',
            ]);
        }

        // Check that all emails were tracked
        $this->assertEquals(5, SentEmail::where('tenant_id', $this->tenant->id)->count());
    }

    /** @test */
    public function it_validates_attachment_content_type()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/send', [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
            'attachments' => [
                [
                    'filename' => 'script.exe',
                    'content' => base64_encode('malicious'),
                    'content_type' => 'application/x-executable',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }
}
