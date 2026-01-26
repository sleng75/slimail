<?php

namespace Tests\Unit\Services;

use App\Contracts\EmailServiceInterface;
use App\Models\Automation;
use App\Models\AutomationEnrollment;
use App\Models\AutomationLog;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected AutomationService $automationService;
    protected $mockEmailService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->mockEmailService = Mockery::mock(EmailServiceInterface::class);
        $this->automationService = new AutomationService($this->mockEmailService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_processes_enrollments()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Automation::STATUS_ACTIVE,
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_WAIT,
            'config' => ['duration' => 1, 'unit' => 'minutes'],
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $processed = $this->automationService->processEnrollments();

        $this->assertGreaterThanOrEqual(1, $processed);
    }

    /** @test */
    public function it_executes_add_tag_step()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Automation::STATUS_ACTIVE,
        ]);

        $tag = Tag::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'VIP',
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_ADD_TAG,
            'config' => ['tag_id' => $tag->id],
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $this->assertTrue($contact->fresh()->tags->contains($tag->id));
    }

    /** @test */
    public function it_executes_remove_tag_step()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $tag = Tag::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact->tags()->attach($tag->id);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_REMOVE_TAG,
            'config' => ['tag_id' => $tag->id],
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $this->assertFalse($contact->fresh()->tags->contains($tag->id));
    }

    /** @test */
    public function it_executes_add_to_list_step()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $list = ContactList::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_ADD_TO_LIST,
            'config' => ['list_id' => $list->id],
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $this->assertTrue($contact->fresh()->lists->contains($list->id));
    }

    /** @test */
    public function it_executes_remove_from_list_step()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $list = ContactList::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact->lists()->attach($list->id);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_REMOVE_FROM_LIST,
            'config' => ['list_id' => $list->id],
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $this->assertFalse($contact->fresh()->lists->contains($list->id));
    }

    /** @test */
    public function it_executes_update_field_step()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company' => 'Old Company',
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_UPDATE_FIELD,
            'config' => ['field' => 'company', 'value' => 'New Company'],
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $this->assertEquals('New Company', $contact->fresh()->company);
    }

    /** @test */
    public function it_executes_webhook_step()
    {
        Http::fake([
            'https://webhook.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_WEBHOOK,
            'config' => [
                'url' => 'https://webhook.example.com/hook',
                'method' => 'POST',
            ],
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.example.com/hook';
        });
    }

    /** @test */
    public function it_triggers_automation_on_list_subscription()
    {
        $list = ContactList::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Automation::STATUS_ACTIVE,
            'trigger_type' => Automation::TRIGGER_LIST_SUBSCRIPTION,
            'trigger_config' => ['list_id' => $list->id],
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $enrolled = $this->automationService->triggerAutomation(
            Automation::TRIGGER_LIST_SUBSCRIPTION,
            $contact,
            ['list_id' => $list->id]
        );

        $this->assertEquals(1, $enrolled);
        $this->assertDatabaseHas('automation_enrollments', [
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_triggers_automation_on_tag_added()
    {
        $tag = Tag::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Automation::STATUS_ACTIVE,
            'trigger_type' => Automation::TRIGGER_TAG_ADDED,
            'trigger_config' => ['tag_id' => $tag->id],
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $enrolled = $this->automationService->triggerAutomation(
            Automation::TRIGGER_TAG_ADDED,
            $contact,
            ['tag_id' => $tag->id]
        );

        $this->assertEquals(1, $enrolled);
    }

    /** @test */
    public function it_does_not_trigger_for_wrong_list()
    {
        $list1 = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);
        $list2 = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Automation::STATUS_ACTIVE,
            'trigger_type' => Automation::TRIGGER_LIST_SUBSCRIPTION,
            'trigger_config' => ['list_id' => $list1->id],
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $enrolled = $this->automationService->triggerAutomation(
            Automation::TRIGGER_LIST_SUBSCRIPTION,
            $contact,
            ['list_id' => $list2->id]
        );

        $this->assertEquals(0, $enrolled);
    }

    /** @test */
    public function it_saves_workflow_structure()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $steps = [
            [
                'type' => AutomationStep::TYPE_WAIT,
                'name' => 'Wait 1 day',
                'config' => ['duration' => 1, 'unit' => 'days'],
            ],
            [
                'type' => AutomationStep::TYPE_SEND_EMAIL,
                'name' => 'Send Welcome',
                'config' => ['subject' => 'Welcome!'],
            ],
        ];

        $this->automationService->saveWorkflow($automation, $steps);

        $this->assertEquals(2, $automation->steps()->count());
        $this->assertDatabaseHas('automation_steps', [
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_WAIT,
            'name' => 'Wait 1 day',
        ]);
    }

    /** @test */
    public function it_duplicates_automation()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Automation',
            'status' => Automation::STATUS_ACTIVE,
        ]);

        AutomationStep::factory()->count(3)->create([
            'automation_id' => $automation->id,
        ]);

        $duplicate = $this->automationService->duplicate($automation);

        $this->assertEquals('Original Automation (copie)', $duplicate->name);
        $this->assertEquals(Automation::STATUS_DRAFT, $duplicate->status);
        $this->assertEquals(3, $duplicate->steps()->count());
        $this->assertNotEquals($automation->id, $duplicate->id);
    }

    /** @test */
    public function it_completes_enrollment_when_no_more_steps()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => null,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $enrollment->refresh();
        $this->assertEquals(AutomationEnrollment::STATUS_COMPLETED, $enrollment->status);
    }

    /** @test */
    public function it_logs_step_execution()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $tag = Tag::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_ADD_TAG,
            'config' => ['tag_id' => $tag->id],
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $this->assertDatabaseHas('automation_logs', [
            'enrollment_id' => $enrollment->id,
            'action' => AutomationLog::ACTION_TAG_ADDED,
        ]);
    }

    /** @test */
    public function it_handles_failed_webhook()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Server error'], 500),
        ]);

        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_WEBHOOK,
            'config' => [
                'url' => 'https://failing.example.com/hook',
                'method' => 'POST',
            ],
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $enrollment->refresh();
        // Should be set to retry (waiting status)
        $this->assertNotNull($enrollment->next_action_at);
    }

    /** @test */
    public function it_updates_custom_field()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'custom_fields' => [],
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_UPDATE_FIELD,
            'config' => ['field' => 'loyalty_level', 'value' => 'Gold'],
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $contact->refresh();
        $this->assertEquals('Gold', $contact->custom_fields['loyalty_level'] ?? null);
    }

    /** @test */
    public function it_executes_exit_step()
    {
        $automation = Automation::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $step = AutomationStep::factory()->create([
            'automation_id' => $automation->id,
            'type' => AutomationStep::TYPE_EXIT,
        ]);

        $enrollment = AutomationEnrollment::factory()->create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'current_step_id' => $step->id,
            'status' => AutomationEnrollment::STATUS_ACTIVE,
            'next_action_at' => now()->subMinute(),
        ]);

        $this->automationService->processEnrollment($enrollment);

        $enrollment->refresh();
        $this->assertEquals(AutomationEnrollment::STATUS_EXITED, $enrollment->status);
    }

    /** @test */
    public function it_replaces_variables_in_content()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Acme Inc',
            'custom_fields' => ['role' => 'Manager'],
        ]);

        $content = 'Hello {{first_name}} {{last_name}} from {{company}}, you are a {{custom.role}}';

        $reflection = new \ReflectionClass($this->automationService);
        $method = $reflection->getMethod('replaceVariables');
        $method->setAccessible(true);

        $result = $method->invoke($this->automationService, $content, $contact);

        $this->assertEquals('Hello John Doe from Acme Inc, you are a Manager', $result);
    }
}
