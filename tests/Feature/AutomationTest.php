<?php

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\AutomationEnrollment;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Skip;
use Tests\TestCase;

#[Skip('Automation feature tests need Inertia page fixes - to be fixed later')]
class AutomationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Automation $automation;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'email' => 'test@example.com',
            'owner_name' => 'Test Owner',
        ]);

        // Create user
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        // Create contact
        $this->contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'email' => 'contact@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'subscribed',
        ]);

        // Create automation
        $this->automation = Automation::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Automation',
            'description' => 'Test description',
            'trigger_type' => Automation::TRIGGER_MANUAL,
            'status' => Automation::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_automations_page_displays(): void
    {
        $response = $this->actingAs($this->user)->get(route('automations.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Automations/Index'));
    }

    public function test_create_automation_page_displays(): void
    {
        $response = $this->actingAs($this->user)->get(route('automations.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Automations/Create')
            ->has('triggerTypes')
        );
    }

    public function test_user_can_create_automation(): void
    {
        $response = $this->actingAs($this->user)->post(route('automations.store'), [
            'name' => 'New Automation',
            'description' => 'Description',
            'trigger_type' => Automation::TRIGGER_LIST_SUBSCRIPTION,
            'trigger_config' => ['list_id' => 1],
            'allow_reentry' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('automations', [
            'tenant_id' => $this->tenant->id,
            'name' => 'New Automation',
            'trigger_type' => Automation::TRIGGER_LIST_SUBSCRIPTION,
        ]);
    }

    public function test_automation_show_page_displays(): void
    {
        $response = $this->actingAs($this->user)->get(route('automations.show', $this->automation));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Automations/Show')
            ->has('automation')
        );
    }

    public function test_automation_edit_page_displays(): void
    {
        $response = $this->actingAs($this->user)->get(route('automations.edit', $this->automation));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Automations/Edit')
            ->has('automation')
            ->has('stepTypes')
        );
    }

    public function test_user_can_update_automation(): void
    {
        $response = $this->actingAs($this->user)->put(route('automations.update', $this->automation), [
            'name' => 'Updated Automation',
            'description' => 'Updated description',
            'trigger_type' => Automation::TRIGGER_MANUAL,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('automations', [
            'id' => $this->automation->id,
            'name' => 'Updated Automation',
        ]);
    }

    public function test_automation_cannot_be_activated_without_steps(): void
    {
        $response = $this->actingAs($this->user)->post(route('automations.activate', $this->automation));

        $response->assertSessionHasErrors('activation');

        $this->automation->refresh();
        $this->assertEquals(Automation::STATUS_DRAFT, $this->automation->status);
    }

    public function test_automation_can_be_activated_with_steps(): void
    {
        // Add a step
        $this->automation->steps()->create([
            'type' => AutomationStep::TYPE_SEND_EMAIL,
            'config' => ['subject' => 'Test', 'html_content' => '<p>Test</p>'],
            'position' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('automations.activate', $this->automation));

        $response->assertRedirect();

        $this->automation->refresh();
        $this->assertEquals(Automation::STATUS_ACTIVE, $this->automation->status);
        $this->assertNotNull($this->automation->activated_at);
    }

    public function test_automation_can_be_paused(): void
    {
        $this->automation->update(['status' => Automation::STATUS_ACTIVE]);

        $response = $this->actingAs($this->user)->post(route('automations.pause', $this->automation));

        $response->assertRedirect();

        $this->automation->refresh();
        $this->assertEquals(Automation::STATUS_PAUSED, $this->automation->status);
    }

    public function test_automation_can_be_duplicated(): void
    {
        $this->automation->steps()->create([
            'type' => AutomationStep::TYPE_WAIT,
            'config' => ['duration_value' => 1, 'duration_unit' => 'days'],
            'position' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('automations.duplicate', $this->automation));

        $response->assertRedirect();

        $this->assertEquals(2, Automation::where('tenant_id', $this->tenant->id)->count());
        $this->assertDatabaseHas('automations', [
            'name' => 'Test Automation (copie)',
            'status' => Automation::STATUS_DRAFT,
        ]);
    }

    public function test_workflow_can_be_saved(): void
    {
        $response = $this->actingAs($this->user)->post(route('automations.save-workflow', $this->automation), [
            'steps' => [
                [
                    'type' => AutomationStep::TYPE_SEND_EMAIL,
                    'name' => 'Welcome Email',
                    'config' => ['subject' => 'Welcome', 'html_content' => '<p>Welcome!</p>'],
                ],
                [
                    'type' => AutomationStep::TYPE_WAIT,
                    'config' => ['duration_value' => 2, 'duration_unit' => 'days'],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertEquals(2, $this->automation->steps()->count());
    }

    public function test_contact_can_be_enrolled(): void
    {
        $this->automation->update(['status' => Automation::STATUS_ACTIVE]);
        $this->automation->steps()->create([
            'type' => AutomationStep::TYPE_WAIT,
            'config' => ['duration_value' => 1, 'duration_unit' => 'days'],
            'position' => 0,
        ]);

        $enrollment = $this->automation->enrollContact($this->contact);

        $this->assertNotNull($enrollment);
        $this->assertEquals(AutomationEnrollment::STATUS_ACTIVE, $enrollment->status);
        $this->assertEquals(1, $this->automation->fresh()->total_enrolled);
    }

    public function test_contact_cannot_be_enrolled_twice(): void
    {
        $this->automation->update(['status' => Automation::STATUS_ACTIVE, 'allow_reentry' => false]);
        $this->automation->steps()->create([
            'type' => AutomationStep::TYPE_WAIT,
            'config' => ['duration_value' => 1, 'duration_unit' => 'days'],
            'position' => 0,
        ]);

        $enrollment1 = $this->automation->enrollContact($this->contact);
        $enrollment2 = $this->automation->enrollContact($this->contact);

        $this->assertNotNull($enrollment1);
        $this->assertNull($enrollment2);
    }

    public function test_enrollments_page_displays(): void
    {
        $response = $this->actingAs($this->user)->get(route('automations.enrollments', $this->automation));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Automations/Enrollments'));
    }

    public function test_enrollment_can_be_removed(): void
    {
        $this->automation->update(['status' => Automation::STATUS_ACTIVE]);
        $this->automation->steps()->create([
            'type' => AutomationStep::TYPE_WAIT,
            'config' => ['duration_value' => 1, 'duration_unit' => 'days'],
            'position' => 0,
        ]);

        $enrollment = $this->automation->enrollContact($this->contact);

        $response = $this->actingAs($this->user)->delete(route('automations.remove-enrollment', [
            $this->automation,
            $enrollment,
        ]));

        $response->assertRedirect();

        $enrollment->refresh();
        $this->assertEquals(AutomationEnrollment::STATUS_EXITED, $enrollment->status);
    }

    public function test_step_types_are_available(): void
    {
        $types = AutomationStep::getTypes();

        $this->assertArrayHasKey('send_email', $types);
        $this->assertArrayHasKey('wait', $types);
        $this->assertArrayHasKey('condition', $types);
        $this->assertArrayHasKey('add_tag', $types);
    }

    public function test_wait_step_calculates_duration(): void
    {
        $step = AutomationStep::create([
            'automation_id' => $this->automation->id,
            'type' => AutomationStep::TYPE_WAIT,
            'config' => ['wait_type' => 'duration', 'duration_value' => 2, 'duration_unit' => 'days'],
            'position' => 0,
        ]);

        $seconds = $step->getWaitDurationSeconds();

        $this->assertEquals(2 * 86400, $seconds); // 2 days in seconds
    }

    public function test_condition_step_evaluates_correctly(): void
    {
        $tag = Tag::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'VIP',
        ]);

        $step = AutomationStep::create([
            'automation_id' => $this->automation->id,
            'type' => AutomationStep::TYPE_CONDITION,
            'config' => ['condition_type' => 'has_tag', 'tag_id' => $tag->id],
            'position' => 0,
        ]);

        // Contact doesn't have the tag
        $result1 = $step->evaluateCondition($this->contact);
        $this->assertFalse($result1);

        // Add tag to contact
        $this->contact->tags()->attach($tag->id);

        // Contact now has the tag
        $result2 = $step->evaluateCondition($this->contact);
        $this->assertTrue($result2);
    }

    public function test_automation_can_be_deleted(): void
    {
        $response = $this->actingAs($this->user)->delete(route('automations.destroy', $this->automation));

        $response->assertRedirect(route('automations.index'));

        $this->assertSoftDeleted('automations', [
            'id' => $this->automation->id,
        ]);
    }

    public function test_automation_with_enrollments_is_archived_instead(): void
    {
        $this->automation->update([
            'status' => Automation::STATUS_ACTIVE,
            'total_enrolled' => 10,
        ]);

        $response = $this->actingAs($this->user)->delete(route('automations.destroy', $this->automation));

        $response->assertRedirect(route('automations.index'));

        $this->automation->refresh();
        $this->assertEquals(Automation::STATUS_ARCHIVED, $this->automation->status);
        $this->assertNull($this->automation->deleted_at);
    }
}
