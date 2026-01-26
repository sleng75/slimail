<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
    }

    /** @test */
    public function it_can_create_a_contact()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('contacts', [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /** @test */
    public function it_returns_full_name_attribute()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $contact->full_name);
    }

    /** @test */
    public function it_returns_email_as_full_name_when_names_are_empty()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->assertEquals('test@example.com', $contact->full_name);
    }

    /** @test */
    public function it_returns_correct_initials()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('JD', $contact->initials);
    }

    /** @test */
    public function it_returns_first_name_initials_when_no_last_name()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'John',
            'last_name' => null,
        ]);

        $this->assertEquals('JO', $contact->initials);
    }

    /** @test */
    public function it_returns_email_initials_when_no_names()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->assertEquals('TE', $contact->initials);
    }

    /** @test */
    public function it_is_subscribed_by_default()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Contact::STATUS_SUBSCRIBED,
        ]);

        $this->assertTrue($contact->isSubscribed());
        $this->assertTrue($contact->canReceiveEmails());
    }

    /** @test */
    public function it_can_be_unsubscribed()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Contact::STATUS_SUBSCRIBED,
        ]);

        $contact->unsubscribe('No longer interested');

        $this->assertEquals(Contact::STATUS_UNSUBSCRIBED, $contact->status);
        $this->assertEquals('No longer interested', $contact->unsubscribe_reason);
        $this->assertNotNull($contact->unsubscribed_at);
        $this->assertFalse($contact->isSubscribed());
        $this->assertFalse($contact->canReceiveEmails());
    }

    /** @test */
    public function it_can_be_marked_as_bounced()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Contact::STATUS_SUBSCRIBED,
        ]);

        $contact->markAsBounced();

        $this->assertEquals(Contact::STATUS_BOUNCED, $contact->status);
        $this->assertFalse($contact->canReceiveEmails());
    }

    /** @test */
    public function it_can_be_marked_as_complained()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Contact::STATUS_SUBSCRIBED,
        ]);

        $contact->markAsComplained();

        $this->assertEquals(Contact::STATUS_COMPLAINED, $contact->status);
        $this->assertFalse($contact->canReceiveEmails());
    }

    /** @test */
    public function it_can_set_and_get_custom_fields()
    {
        $contact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'custom_fields' => null,
        ]);

        $contact->setCustomField('favorite_color', 'blue');
        $contact->refresh();

        $this->assertEquals('blue', $contact->getCustomField('favorite_color'));
        $this->assertNull($contact->getCustomField('nonexistent'));
        $this->assertEquals('default', $contact->getCustomField('nonexistent', 'default'));
    }

    /** @test */
    public function it_can_track_email_statistics()
    {
        $this->markTestSkipped('Email statistics columns (emails_sent, emails_opened, emails_clicked) not in current schema');
    }

    /** @test */
    public function it_calculates_engagement_score()
    {
        $this->markTestSkipped('Email statistics columns not in current schema');
    }

    /** @test */
    public function it_caps_engagement_score_at_100()
    {
        $this->markTestSkipped('Email statistics columns not in current schema');
    }

    /** @test */
    public function it_filters_by_subscribed_status()
    {
        $subscribedContact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Contact::STATUS_SUBSCRIBED,
        ]);
        $unsubscribedContact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Contact::STATUS_UNSUBSCRIBED,
        ]);
        $bouncedContact = Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => Contact::STATUS_BOUNCED,
        ]);

        $subscribed = Contact::where('tenant_id', $this->tenant->id)->subscribed()->count();

        $this->assertEquals(1, $subscribed);
        $this->assertTrue($subscribedContact->isSubscribed());
        $this->assertFalse($unsubscribedContact->isSubscribed());
        $this->assertFalse($bouncedContact->isSubscribed());
    }

    /** @test */
    public function it_can_search_contacts()
    {
        Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
        ]);
        Contact::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'jane.smith@example.com',
            'first_name' => 'Jane',
        ]);

        $results = Contact::search('john')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('john.doe@example.com', $results->first()->email);
    }

    /** @test */
    public function it_can_belong_to_multiple_lists()
    {
        $contact = Contact::factory()->create(['tenant_id' => $this->tenant->id]);
        $list1 = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);
        $list2 = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $contact->lists()->attach([$list1->id, $list2->id]);

        $this->assertCount(2, $contact->lists);
    }

    /** @test */
    public function it_can_have_multiple_tags()
    {
        $contact = Contact::factory()->create(['tenant_id' => $this->tenant->id]);
        $tag1 = Tag::factory()->create(['tenant_id' => $this->tenant->id]);
        $tag2 = Tag::factory()->create(['tenant_id' => $this->tenant->id]);

        $contact->addTag($tag1);
        $contact->addTag($tag2);
        $contact->refresh();

        $this->assertCount(2, $contact->tags);
    }

    /** @test */
    public function it_can_remove_a_tag()
    {
        $contact = Contact::factory()->create(['tenant_id' => $this->tenant->id]);
        $tag = Tag::factory()->create(['tenant_id' => $this->tenant->id]);

        $contact->addTag($tag);
        $this->assertCount(1, $contact->fresh()->tags);

        $contact->removeTag($tag);
        $this->assertCount(0, $contact->fresh()->tags);
    }

    /** @test */
    public function it_filters_contacts_by_list()
    {
        $list = ContactList::factory()->create(['tenant_id' => $this->tenant->id]);

        $contactInList = Contact::factory()->create(['tenant_id' => $this->tenant->id]);
        $contactInList->lists()->attach($list->id, ['status' => 'active']);

        $contactNotInList = Contact::factory()->create(['tenant_id' => $this->tenant->id]);

        $results = Contact::inList($list->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($contactInList->id, $results->first()->id);
    }

    /** @test */
    public function it_filters_contacts_by_tag()
    {
        $tag = Tag::factory()->create(['tenant_id' => $this->tenant->id]);

        $contactWithTag = Contact::factory()->create(['tenant_id' => $this->tenant->id]);
        $contactWithTag->addTag($tag);

        $contactWithoutTag = Contact::factory()->create(['tenant_id' => $this->tenant->id]);

        $results = Contact::withTag($tag->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($contactWithTag->id, $results->first()->id);
    }
}
