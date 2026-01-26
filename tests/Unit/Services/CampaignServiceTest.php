<?php

namespace Tests\Unit\Services;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\SentEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Amazon\SESService;
use App\Services\Email\CampaignService;
use App\Services\Email\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CampaignServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected CampaignService $campaignService;
    protected $mockEmailService;
    protected $mockSesService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->mockSesService = Mockery::mock(SESService::class);
        $this->mockEmailService = Mockery::mock(EmailService::class);

        $this->campaignService = new CampaignService(
            $this->mockEmailService,
            $this->mockSesService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_email_to_contact()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'contact@example.com',
            'first_name' => 'John',
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'from_email' => 'sender@example.com',
            'from_name' => 'Test Sender',
            'subject' => 'Hello {{first_name}}',
            'html_content' => '<p>Welcome</p>',
            'track_opens' => true,
            'track_clicks' => true,
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'send-to-contact-id',
            ]);

        $result = $this->campaignService->sendToContact($campaign, $contact);

        $this->assertTrue($result['success']);
        $this->assertEquals('send-to-contact-id', $result['message_id']);
        $this->assertDatabaseHas('sent_emails', [
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'subject' => 'Hello John',
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_sends_to_same_contact()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create existing sent email
        SentEmail::factory()->create([
            'tenant_id' => $this->tenant->id,
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
        ]);

        $result = $this->campaignService->sendToContact($campaign, $contact);

        $this->assertFalse($result['success']);
        $this->assertEquals('Email already sent to this contact', $result['error']);
    }

    /** @test */
    public function it_sends_with_variant_content()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
            'subject' => 'Original Subject',
            'html_content' => '<p>Original Content</p>',
        ]);

        $variant = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'subject' => 'Variant Subject',
            'html_content' => '<p>Variant Content</p>',
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->withArgs(function ($args) {
                return $args['subject'] === 'Variant Subject';
            })
            ->andReturn([
                'success' => true,
                'message_id' => 'variant-id',
            ]);

        $result = $this->campaignService->sendToContact($campaign, $contact, $variant);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_processes_content_with_variables()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Acme',
        ]);

        $content = '{{contact.first_name}} {{contact.last_name}} from {{contact.company}}';
        $processed = $this->campaignService->processContent($content, $contact);

        $this->assertEquals('John Doe from Acme', $processed);
    }

    /** @test */
    public function it_processes_legacy_variable_format()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $content = 'Hello {{first_name}} {{last_name}}';
        $processed = $this->campaignService->processContent($content, $contact);

        $this->assertEquals('Hello Jane Smith', $processed);
    }

    /** @test */
    public function it_processes_custom_fields()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'custom_fields' => [
                'membership_level' => 'Gold',
                'points' => '1000',
            ],
        ]);

        $content = 'Level: {{contact.membership_level}}, Points: {{contact.points}}';
        $processed = $this->campaignService->processContent($content, $contact);

        $this->assertEquals('Level: Gold, Points: 1000', $processed);
    }

    /** @test */
    public function it_adds_unsubscribe_link()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->mockEmailService->shouldReceive('generateUnsubscribeLink')
            ->once()
            ->with($contact, $campaign)
            ->andReturn('https://example.com/unsubscribe?c=123');

        $content = 'Click here: {{unsubscribe_url}}';
        $processed = $this->campaignService->processContent($content, $contact, $campaign);

        $this->assertEquals('Click here: https://example.com/unsubscribe?c=123', $processed);
    }

    /** @test */
    public function it_gets_recipients_count()
    {
        $list = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $contacts = Contact::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'subscribed',
        ]);

        foreach ($contacts as $contact) {
            $contact->lists()->attach($list->id);
        }

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'list_ids' => [$list->id],
        ]);

        $count = $this->campaignService->getRecipientsCount($campaign, false);

        $this->assertEquals(5, $count);
    }

    /** @test */
    public function it_excludes_already_sent_contacts()
    {
        $list = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $contacts = Contact::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'subscribed',
        ]);

        foreach ($contacts as $contact) {
            $contact->lists()->attach($list->id);
        }

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'list_ids' => [$list->id],
        ]);

        // Mark 2 as sent
        SentEmail::factory()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[0]->id,
        ]);
        SentEmail::factory()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contacts[1]->id,
        ]);

        $count = $this->campaignService->getRecipientsCount($campaign, true);

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function it_assigns_variant_deterministically()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
        ]);

        CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'A',
            'percentage' => 50,
        ]);

        CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'B',
            'percentage' => 50,
        ]);

        // Same contact should always get same variant
        $variant1 = $this->campaignService->assignVariant($campaign, $contact);
        $variant2 = $this->campaignService->assignVariant($campaign, $contact);

        $this->assertEquals($variant1->id, $variant2->id);
    }

    /** @test */
    public function it_returns_winning_variant_when_set()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
        ]);

        $variantA = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'A',
        ]);

        $variantB = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'B',
        ]);

        $campaign->update(['winning_variant_id' => $variantB->id]);

        $variant = $this->campaignService->assignVariant($campaign, $contact);

        $this->assertEquals($variantB->id, $variant->id);
    }

    /** @test */
    public function it_determines_winner_by_open_rate()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
            'ab_test_config' => ['winning_criteria' => 'opens'],
        ]);

        $variantA = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'A',
            'sent_count' => 100,
            'opened_count' => 20,
        ]);

        $variantB = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'B',
            'sent_count' => 100,
            'opened_count' => 35,
        ]);

        $winner = $this->campaignService->determineWinner($campaign);

        $this->assertEquals($variantB->id, $winner->id);
    }

    /** @test */
    public function it_determines_winner_by_click_rate()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
            'ab_test_config' => ['winning_criteria' => 'clicks'],
        ]);

        $variantA = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'A',
            'sent_count' => 100,
            'clicked_count' => 15,
        ]);

        $variantB = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'B',
            'sent_count' => 100,
            'clicked_count' => 8,
        ]);

        $winner = $this->campaignService->determineWinner($campaign);

        $this->assertEquals($variantA->id, $winner->id);
    }

    /** @test */
    public function it_calculates_progress()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipients_count' => 100,
            'sent_count' => 45,
        ]);

        $progress = $this->campaignService->getProgress($campaign);

        $this->assertEquals(100, $progress['total']);
        $this->assertEquals(45, $progress['sent']);
        $this->assertEquals(55, $progress['remaining']);
        $this->assertEquals(45.0, $progress['percentage']);
    }

    /** @test */
    public function it_checks_if_sending_is_complete()
    {
        $list = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'subscribed',
        ]);
        $contact->lists()->attach($list->id);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'list_ids' => [$list->id],
        ]);

        $this->assertFalse($this->campaignService->isSendingComplete($campaign));

        SentEmail::factory()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($this->campaignService->isSendingComplete($campaign));
    }

    /** @test */
    public function it_should_not_determine_winner_for_non_ab_test()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_REGULAR,
        ]);

        $this->assertFalse($this->campaignService->shouldDetermineWinner($campaign));
    }

    /** @test */
    public function it_should_not_determine_winner_when_already_set()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
            'winning_variant_id' => 1,
        ]);

        $this->assertFalse($this->campaignService->shouldDetermineWinner($campaign));
    }

    /** @test */
    public function it_refreshes_campaign_stats()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sent_count' => 0,
            'delivered_count' => 0,
            'opened_count' => 0,
        ]);

        SentEmail::factory()->count(3)->create([
            'campaign_id' => $campaign->id,
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        SentEmail::factory()->count(2)->create([
            'campaign_id' => $campaign->id,
            'tenant_id' => $this->tenant->id,
            'status' => 'opened',
            'delivered_at' => now(),
            'opened_at' => now(),
        ]);

        $this->campaignService->refreshStats($campaign);

        $campaign->refresh();
        $this->assertEquals(5, $campaign->sent_count);
        $this->assertEquals(5, $campaign->delivered_count);
        $this->assertEquals(2, $campaign->opened_count);
    }

    /** @test */
    public function it_returns_null_variant_for_regular_campaign()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_REGULAR,
        ]);

        $variant = $this->campaignService->assignVariant($campaign, $contact);

        $this->assertNull($variant);
    }

    /** @test */
    public function it_increments_variant_sent_count()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
        ]);

        $variant = CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'sent_count' => 0,
        ]);

        $this->mockSesService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'test-id',
            ]);

        $this->campaignService->sendToContact($campaign, $contact, $variant);

        $variant->refresh();
        $this->assertEquals(1, $variant->sent_count);
    }
}
