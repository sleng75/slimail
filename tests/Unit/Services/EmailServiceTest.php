<?php

namespace Tests\Unit\Services;

use App\Models\ApiKey;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\SentEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Amazon\SESService;
use App\Services\Email\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected EmailService $emailService;
    protected $mockSesService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->mockSesService = Mockery::mock(SESService::class);
        $this->emailService = new EmailService($this->mockSesService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_transactional_email_successfully()
    {
        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'test-message-id-123',
            ]);

        $params = [
            'from_email' => 'sender@example.com',
            'from_name' => 'Test Sender',
            'to_email' => 'recipient@example.com',
            'to_name' => 'Test Recipient',
            'subject' => 'Test Subject',
            'html_content' => '<p>Hello World</p>',
        ];

        $result = $this->emailService->sendTransactional($params, $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('test-message-id-123', $result['message_id']);
        $this->assertDatabaseHas('sent_emails', [
            'tenant_id' => $this->tenant->id,
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'type' => SentEmail::TYPE_TRANSACTIONAL,
        ]);
    }

    /** @test */
    public function it_handles_transactional_email_failure()
    {
        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Invalid email address',
            ]);

        $params = [
            'from_email' => 'sender@example.com',
            'to_email' => 'invalid@example.com',
            'subject' => 'Test Subject',
            'html_content' => '<p>Hello</p>',
        ];

        $result = $this->emailService->sendTransactional($params, $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid email address', $result['error']);
        $this->assertDatabaseHas('sent_emails', [
            'to_email' => 'invalid@example.com',
            'status' => SentEmail::STATUS_FAILED,
        ]);
    }

    /** @test */
    public function it_links_sent_email_to_existing_contact()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'contact@example.com',
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'test-message-id',
            ]);

        $params = [
            'from_email' => 'sender@example.com',
            'to_email' => 'contact@example.com',
            'subject' => 'Test Subject',
            'html_content' => '<p>Hello</p>',
        ];

        $result = $this->emailService->sendTransactional($params, $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('sent_emails', [
            'contact_id' => $contact->id,
            'to_email' => 'contact@example.com',
        ]);
    }

    /** @test */
    public function it_sends_email_with_attachments_via_raw_email()
    {
        $this->mockSesService->shouldReceive('sendRawEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'raw-message-id',
            ]);

        $params = [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'html_content' => '<p>Hello</p>',
            'attachments' => [
                [
                    'filename' => 'test.pdf',
                    'content' => base64_encode('PDF content'),
                    'content_type' => 'application/pdf',
                ],
            ],
        ];

        $result = $this->emailService->sendTransactional($params, $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('raw-message-id', $result['message_id']);
    }

    /** @test */
    public function it_sends_campaign_email_successfully()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'contact@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_email' => 'campaign@example.com',
            'from_name' => 'Campaign Sender',
            'subject' => 'Hello {{first_name}}',
            'html_content' => '<p>Hi {{full_name}}</p>',
            'track_opens' => true,
            'track_clicks' => true,
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'campaign-message-id',
            ]);

        $result = $this->emailService->sendCampaignEmail($campaign, $contact);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('sent_emails', [
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'subject' => 'Hello John',
            'type' => SentEmail::TYPE_CAMPAIGN,
        ]);
    }

    /** @test */
    public function it_processes_personalization_variables()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Acme Inc',
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subject' => 'Hello {{first_name}} from {{company}}',
            'html_content' => '<p>Dear {{full_name}}, email: {{email}}</p>',
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->withArgs(function ($args) {
                return str_contains($args['subject'], 'Hello John from Acme Inc');
            })
            ->andReturn([
                'success' => true,
                'message_id' => 'test-id',
            ]);

        $result = $this->emailService->sendCampaignEmail($campaign, $contact);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_generates_unsubscribe_link()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $link = $this->emailService->generateUnsubscribeLink($contact, $campaign);

        $this->assertStringContainsString('/unsubscribe', $link);
        $this->assertStringContainsString('c=' . $contact->id, $link);
        $this->assertStringContainsString('campaign=' . $campaign->id, $link);
    }

    /** @test */
    public function it_generates_unsubscribe_link_without_campaign()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
        ]);

        $link = $this->emailService->generateUnsubscribeLink($contact);

        $this->assertStringContainsString('/unsubscribe', $link);
        $this->assertStringContainsString('c=' . $contact->id, $link);
        $this->assertStringNotContainsString('campaign=', $link);
    }

    /** @test */
    public function it_creates_email_event_on_successful_send()
    {
        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'event-test-id',
            ]);

        $params = [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ];

        $result = $this->emailService->sendTransactional($params, $this->tenant);

        $this->assertDatabaseHas('email_events', [
            'tenant_id' => $this->tenant->id,
            'event_type' => EmailEvent::TYPE_SEND,
            'message_id' => 'event-test-id',
        ]);
    }

    /** @test */
    #[\PHPUnit\Framework\Attributes\Skip('API key tracking needs refactoring')]
    public function it_tracks_with_api_key()
    {
        $apiKey = ApiKey::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'api-key-test',
            ]);

        $params = [
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Test</p>',
        ];

        $result = $this->emailService->sendTransactional($params, $this->tenant, $apiKey);

        $this->assertDatabaseHas('sent_emails', [
            'api_key_id' => $apiKey->id,
        ]);
    }

    /** @test */
    public function it_increments_campaign_sent_stat()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sent_count' => 0,
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'test-id',
            ]);

        $this->emailService->sendCampaignEmail($campaign, $contact);

        $campaign->refresh();
        $this->assertEquals(1, $campaign->sent_count);
    }
}
