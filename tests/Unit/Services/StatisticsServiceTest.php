<?php

namespace Tests\Unit\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailEvent;
use App\Models\SentEmail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Statistics\StatisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected StatisticsService $statisticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->statisticsService = new StatisticsService();
    }

    /** @test */
    public function it_gets_overview_statistics()
    {
        // Create some sent emails
        SentEmail::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now(),
            'created_at' => now()->subDays(5),
        ]);

        SentEmail::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'opened',
            'delivered_at' => now(),
            'opened_at' => now(),
            'created_at' => now()->subDays(3),
        ]);

        $overview = $this->statisticsService->getOverview($this->tenant, '30d');

        $this->assertArrayHasKey('period', $overview);
        $this->assertArrayHasKey('stats', $overview);
        $this->assertArrayHasKey('previous', $overview);
        $this->assertArrayHasKey('changes', $overview);
        $this->assertEquals('30d', $overview['period']);
        $this->assertEquals(15, $overview['stats']['sent']);
    }

    /** @test */
    public function it_calculates_correct_rates()
    {
        SentEmail::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now(),
            'created_at' => now()->subDays(5),
        ]);

        SentEmail::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'opened',
            'delivered_at' => now(),
            'opened_at' => now(),
            'created_at' => now()->subDays(5),
        ]);

        SentEmail::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'clicked',
            'delivered_at' => now(),
            'opened_at' => now(),
            'clicked_at' => now(),
            'created_at' => now()->subDays(5),
        ]);

        SentEmail::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'bounced',
            'created_at' => now()->subDays(5),
        ]);

        $overview = $this->statisticsService->getOverview($this->tenant, '30d');

        $this->assertEquals(140, $overview['stats']['sent']);
        $this->assertEquals(35, $overview['stats']['opened']);
        $this->assertEquals(10, $overview['stats']['clicked']);
        $this->assertEquals(5, $overview['stats']['bounced']);
    }

    /** @test */
    public function it_calculates_changes_between_periods()
    {
        // Previous period emails (31-60 days ago)
        SentEmail::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now()->subDays(45),
            'created_at' => now()->subDays(45),
        ]);

        // Current period emails (0-30 days ago)
        SentEmail::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now()->subDays(15),
            'created_at' => now()->subDays(15),
        ]);

        $overview = $this->statisticsService->getOverview($this->tenant, '30d');

        $this->assertEquals(100, $overview['stats']['sent']);
        $this->assertEquals(50, $overview['previous']['sent']);
        $this->assertEquals(100, $overview['changes']['sent']); // 100% increase
    }

    /** @test */
    public function it_gets_time_series_data()
    {
        SentEmail::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(5),
        ]);

        SentEmail::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(3),
        ]);

        $timeSeries = $this->statisticsService->getTimeSeries($this->tenant, '7d', 'sent');

        $this->assertArrayHasKey('labels', $timeSeries);
        $this->assertArrayHasKey('data', $timeSeries);
        $this->assertCount(count($timeSeries['data']), $timeSeries['labels']);
    }

    /** @test */
    public function it_gets_multiple_time_series()
    {
        SentEmail::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(5),
        ]);

        $multiple = $this->statisticsService->getMultipleTimeSeries($this->tenant, '7d');

        $this->assertArrayHasKey('sent', $multiple);
        $this->assertArrayHasKey('opened', $multiple);
        $this->assertArrayHasKey('clicked', $multiple);
    }

    /** @test */
    public function it_gets_top_campaigns()
    {
        Campaign::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENT,
            'sent_count' => 100,
            'delivered_count' => 95,
            'opened_count' => rand(10, 50),
        ]);

        $topCampaigns = $this->statisticsService->getTopCampaigns($this->tenant, 5);

        $this->assertCount(3, $topCampaigns);
        $this->assertArrayHasKey('id', $topCampaigns->first());
        $this->assertArrayHasKey('name', $topCampaigns->first());
        $this->assertArrayHasKey('open_rate', $topCampaigns->first());
    }

    /** @test */
    public function it_sorts_top_campaigns_by_open_rate()
    {
        Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENT,
            'sent_count' => 100,
            'delivered_count' => 100,
            'opened_count' => 20,
        ]);

        Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENT,
            'sent_count' => 100,
            'delivered_count' => 100,
            'opened_count' => 50,
        ]);

        $topCampaigns = $this->statisticsService->getTopCampaigns($this->tenant, 5, 'open_rate');

        $this->assertEquals(50.0, $topCampaigns->first()['open_rate']);
    }

    /** @test */
    public function it_gets_device_breakdown()
    {
        EmailEvent::factory()->count(60)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'open',
            'device_type' => 'desktop',
            'event_at' => now()->subDays(5),
        ]);

        EmailEvent::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'open',
            'device_type' => 'mobile',
            'event_at' => now()->subDays(5),
        ]);

        EmailEvent::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'open',
            'device_type' => 'tablet',
            'event_at' => now()->subDays(5),
        ]);

        $breakdown = $this->statisticsService->getDeviceBreakdown($this->tenant, '30d');

        $this->assertEquals(60, $breakdown['desktop']['count']);
        $this->assertEquals(30, $breakdown['mobile']['count']);
        $this->assertEquals(10, $breakdown['tablet']['count']);
        $this->assertEquals(60.0, $breakdown['desktop']['percentage']);
        $this->assertEquals(30.0, $breakdown['mobile']['percentage']);
        $this->assertEquals(10.0, $breakdown['tablet']['percentage']);
    }

    /** @test */
    public function it_gets_email_client_breakdown()
    {
        EmailEvent::factory()->count(40)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'open',
            'client_name' => 'Gmail',
            'event_at' => now()->subDays(5),
        ]);

        EmailEvent::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'open',
            'client_name' => 'Outlook',
            'event_at' => now()->subDays(5),
        ]);

        $breakdown = $this->statisticsService->getEmailClientBreakdown($this->tenant, '30d');

        $this->assertCount(2, $breakdown);
        $this->assertEquals('Gmail', $breakdown[0]['name']);
        $this->assertEquals(40, $breakdown[0]['count']);
    }

    /** @test */
    public function it_gets_hourly_distribution()
    {
        // Create events at different hours
        for ($hour = 9; $hour <= 17; $hour++) {
            EmailEvent::factory()->count(rand(5, 20))->create([
                'tenant_id' => $this->tenant->id,
                'event_type' => 'open',
                'event_at' => now()->subDays(5)->setHour($hour),
            ]);
        }

        $distribution = $this->statisticsService->getHourlyDistribution($this->tenant, '30d');

        $this->assertArrayHasKey('labels', $distribution);
        $this->assertArrayHasKey('data', $distribution);
        $this->assertCount(24, $distribution['labels']);
        $this->assertCount(24, $distribution['data']);
    }

    /** @test */
    public function it_gets_campaign_stats()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Campaign::STATUS_SENT,
            'sent_count' => 100,
            'delivered_count' => 95,
            'opened_count' => 30,
            'clicked_count' => 10,
            'bounced_count' => 5,
        ]);

        $stats = $this->statisticsService->getCampaignStats($campaign);

        $this->assertArrayHasKey('stats', $stats);
        $this->assertArrayHasKey('timeline', $stats);
        $this->assertArrayHasKey('links', $stats);
        $this->assertArrayHasKey('devices', $stats);
        $this->assertEquals(100, $stats['stats']['sent']);
        $this->assertEquals(95, $stats['stats']['delivered']);
    }

    /** @test */
    public function it_gets_link_click_breakdown()
    {
        $campaign = Campaign::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $sentEmail = SentEmail::factory()->create([
            'campaign_id' => $campaign->id,
            'tenant_id' => $this->tenant->id,
        ]);

        EmailEvent::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'sent_email_id' => $sentEmail->id,
            'event_type' => 'click',
            'link_url' => 'https://example.com/link1',
        ]);

        EmailEvent::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'sent_email_id' => $sentEmail->id,
            'event_type' => 'click',
            'link_url' => 'https://example.com/link2',
        ]);

        $breakdown = $this->statisticsService->getLinkClickBreakdown($campaign);

        $this->assertCount(2, $breakdown);
        $this->assertEquals('https://example.com/link1', $breakdown[0]['url']);
        $this->assertEquals(10, $breakdown[0]['clicks']);
    }

    /** @test */
    public function it_gets_bounce_alerts_for_high_rate()
    {
        // Create emails with high bounce rate
        SentEmail::factory()->count(80)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now(),
            'created_at' => now()->subDays(3),
        ]);

        SentEmail::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'bounced',
            'created_at' => now()->subDays(3),
        ]);

        $alerts = $this->statisticsService->getBounceAlerts($this->tenant);

        $this->assertNotEmpty($alerts);
        $this->assertEquals('danger', $alerts[0]['type']);
        $this->assertStringContainsString('rebond', strtolower($alerts[0]['title']));
    }

    /** @test */
    public function it_returns_no_alerts_for_low_bounce_rate()
    {
        SentEmail::factory()->count(99)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now(),
            'created_at' => now()->subDays(3),
        ]);

        SentEmail::factory()->count(1)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'bounced',
            'created_at' => now()->subDays(3),
        ]);

        $alerts = $this->statisticsService->getBounceAlerts($this->tenant);

        $this->assertEmpty($alerts);
    }

    /** @test */
    public function it_handles_different_periods()
    {
        SentEmail::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(5),
        ]);

        $overview7d = $this->statisticsService->getOverview($this->tenant, '7d');
        $overview30d = $this->statisticsService->getOverview($this->tenant, '30d');
        $overview90d = $this->statisticsService->getOverview($this->tenant, '90d');

        $this->assertEquals('7d', $overview7d['period']);
        $this->assertEquals('30d', $overview30d['period']);
        $this->assertEquals('90d', $overview90d['period']);
    }

    /** @test */
    public function it_returns_zero_for_empty_data()
    {
        $overview = $this->statisticsService->getOverview($this->tenant, '30d');

        $this->assertEquals(0, $overview['stats']['sent']);
        $this->assertEquals(0, $overview['stats']['opened']);
        $this->assertEquals(0, $overview['stats']['delivery_rate']);
        $this->assertEquals(0, $overview['stats']['open_rate']);
    }

    /** @test */
    public function it_filters_by_tenant()
    {
        $otherTenant = Tenant::factory()->create();

        SentEmail::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(5),
        ]);

        SentEmail::factory()->count(20)->create([
            'tenant_id' => $otherTenant->id,
            'created_at' => now()->subDays(5),
        ]);

        $overview = $this->statisticsService->getOverview($this->tenant, '30d');

        $this->assertEquals(10, $overview['stats']['sent']);
    }

    /** @test */
    public function it_handles_complaint_alerts()
    {
        SentEmail::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'delivered',
            'delivered_at' => now(),
            'created_at' => now()->subDays(3),
        ]);

        SentEmail::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'complained',
            'created_at' => now()->subDays(3),
        ]);

        $alerts = $this->statisticsService->getBounceAlerts($this->tenant);

        $hasComplaintAlert = collect($alerts)->contains(function ($alert) {
            return str_contains(strtolower($alert['title']), 'plainte');
        });

        $this->assertTrue($hasComplaintAlert);
    }
}
