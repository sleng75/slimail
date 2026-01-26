<?php

namespace Tests\Unit\Models;

use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    /** @test */
    public function it_can_create_a_campaign()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'name' => 'Test Campaign',
            'subject' => 'Hello World',
        ]);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'subject' => 'Hello World',
        ]);
    }

    /** @test */
    public function it_has_correct_status_labels()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $this->assertEquals('Brouillon', $campaign->status_label);

        $campaign->status = Campaign::STATUS_SCHEDULED;
        $this->assertEquals('Programmée', $campaign->status_label);

        $campaign->status = Campaign::STATUS_SENDING;
        $this->assertEquals('En cours', $campaign->status_label);

        $campaign->status = Campaign::STATUS_SENT;
        $this->assertEquals('Envoyée', $campaign->status_label);
    }

    /** @test */
    public function it_has_correct_status_colors()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $this->assertEquals('gray', $campaign->status_color);

        $campaign->status = Campaign::STATUS_SENT;
        $this->assertEquals('green', $campaign->status_color);
    }

    /** @test */
    public function it_calculates_open_rate()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'delivered_count' => 100,
            'opened_count' => 25,
        ]);

        $this->assertEquals(25.0, $campaign->open_rate);
    }

    /** @test */
    public function it_returns_zero_open_rate_when_no_delivered()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'delivered_count' => 0,
            'opened_count' => 0,
        ]);

        $this->assertEquals(0, $campaign->open_rate);
    }

    /** @test */
    public function it_calculates_click_rate()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'delivered_count' => 100,
            'clicked_count' => 10,
        ]);

        $this->assertEquals(10.0, $campaign->click_rate);
    }

    /** @test */
    public function it_calculates_bounce_rate()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sent_count' => 100,
            'bounced_count' => 5,
        ]);

        $this->assertEquals(5.0, $campaign->bounce_rate);
    }

    /** @test */
    public function it_calculates_delivery_rate()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sent_count' => 100,
            'delivered_count' => 95,
        ]);

        $this->assertEquals(95.0, $campaign->delivery_rate);
    }

    /** @test */
    public function draft_campaign_is_editable()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $this->assertTrue($campaign->isEditable());
    }

    /** @test */
    public function scheduled_campaign_is_editable()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SCHEDULED,
        ]);

        $this->assertTrue($campaign->isEditable());
    }

    /** @test */
    public function sent_campaign_is_not_editable()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENT,
        ]);

        $this->assertFalse($campaign->isEditable());
    }

    /** @test */
    public function sending_campaign_is_not_editable()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENDING,
        ]);

        $this->assertFalse($campaign->isEditable());
    }

    /** @test */
    public function it_checks_if_campaign_can_be_sent()
    {
        $list = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'subject' => 'Test Subject',
            'html_content' => '<p>Hello</p>',
            'list_ids' => [$list->id],
        ]);

        $this->assertTrue($campaign->canBeSent());
    }

    /** @test */
    public function it_cannot_be_sent_without_subject()
    {
        $list = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
            'from_email' => 'test@example.com',
            'subject' => null,
            'html_content' => '<p>Hello</p>',
            'list_ids' => [$list->id],
        ]);

        $this->assertFalse($campaign->canBeSent());
    }

    /** @test */
    public function it_cannot_be_sent_without_lists()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
            'from_email' => 'test@example.com',
            'subject' => 'Test',
            'html_content' => '<p>Hello</p>',
            'list_ids' => [],
        ]);

        $this->assertFalse($campaign->canBeSent());
    }

    /** @test */
    public function it_can_be_scheduled()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $scheduledAt = now()->addDay();
        $campaign->schedule($scheduledAt);

        $this->assertEquals(Campaign::STATUS_SCHEDULED, $campaign->status);
        $this->assertEquals($scheduledAt->format('Y-m-d H:i'), $campaign->scheduled_at->format('Y-m-d H:i'));
    }

    /** @test */
    public function it_can_start_sending()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SCHEDULED,
        ]);

        $campaign->startSending();

        $this->assertEquals(Campaign::STATUS_SENDING, $campaign->status);
        $this->assertNotNull($campaign->started_at);
    }

    /** @test */
    public function it_can_be_marked_as_sent()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENDING,
        ]);

        $campaign->markAsSent();

        $this->assertEquals(Campaign::STATUS_SENT, $campaign->status);
        $this->assertNotNull($campaign->completed_at);
    }

    /** @test */
    public function it_can_be_paused()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENDING,
        ]);

        $campaign->pause();

        $this->assertEquals(Campaign::STATUS_PAUSED, $campaign->status);
    }

    /** @test */
    public function it_can_be_resumed()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_PAUSED,
        ]);

        $campaign->resume();

        $this->assertEquals(Campaign::STATUS_SENDING, $campaign->status);
    }

    /** @test */
    public function it_can_be_cancelled()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SCHEDULED,
        ]);

        $campaign->cancel();

        $this->assertEquals(Campaign::STATUS_CANCELLED, $campaign->status);
    }

    /** @test */
    public function it_can_increment_stats()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'opened_count' => 0,
        ]);

        $campaign->incrementStat('opened');

        $this->assertEquals(1, $campaign->fresh()->opened_count);
    }

    /** @test */
    public function it_can_be_duplicated()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Campaign',
            'status' => Campaign::STATUS_SENT,
            'sent_count' => 100,
        ]);

        $duplicate = $campaign->duplicate();

        $this->assertEquals('Original Campaign (copie)', $duplicate->name);
        $this->assertEquals(Campaign::STATUS_DRAFT, $duplicate->status);
        $this->assertEquals(0, $duplicate->sent_count);
    }

    /** @test */
    public function ab_test_campaign_has_variants()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => Campaign::TYPE_AB_TEST,
        ]);

        CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'A',
        ]);
        CampaignVariant::factory()->create([
            'campaign_id' => $campaign->id,
            'variant_key' => 'B',
        ]);

        $this->assertTrue($campaign->isAbTest());
        $this->assertCount(2, $campaign->variants);
    }

    /** @test */
    public function it_scopes_by_status()
    {
        Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);
        Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENT,
        ]);

        $this->assertEquals(1, Campaign::draft()->count());
        $this->assertEquals(1, Campaign::sent()->count());
    }

    /** @test */
    public function it_can_search_campaigns()
    {
        Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Newsletter January',
        ]);
        Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Promo February',
        ]);

        $results = Campaign::search('Newsletter')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Newsletter January', $results->first()->name);
    }
}
