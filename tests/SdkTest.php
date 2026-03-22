<?php

declare(strict_types=1);

namespace Sevk\Tests;

use PHPUnit\Framework\TestCase;
use Sevk\Sevk;

class SdkTest extends TestCase
{
    private static ?Sevk $sevk = null;
    private static string $baseUrl = 'https://api.sevk.io';

    public static function setUpBeforeClass(): void
    {
        $apiKey = getenv('SEVK_TEST_API_KEY');

        if (!$apiKey) {
            return;
        }

        $baseUrl = getenv('SEVK_TEST_BASE_URL');
        if ($baseUrl) {
            self::$baseUrl = $baseUrl;
        }

        self::$sevk = new Sevk($apiKey, ['baseUrl' => self::$baseUrl]);
    }

    protected function setUp(): void
    {
        if (self::$sevk === null) {
            $this->markTestSkipped('SEVK_TEST_API_KEY environment variable is not set');
        }
    }

    // ============================================
    // AUTHENTICATION TESTS (3)
    // ============================================

    public function testShouldRejectInvalidApiKey(): void
    {
        $invalidSevk = new Sevk('sevk_invalid_api_key_12345', ['baseUrl' => self::$baseUrl]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/401/');
        $invalidSevk->contacts->list();
    }

    public function testShouldRejectEmptyApiKey(): void
    {
        $emptySevk = new Sevk('', ['baseUrl' => self::$baseUrl]);
        $this->expectException(\Exception::class);
        $emptySevk->contacts->list();
    }

    public function testShouldRejectMalformedApiKey(): void
    {
        $malformedSevk = new Sevk('invalid_key_format', ['baseUrl' => self::$baseUrl]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/401/');
        $malformedSevk->contacts->list();
    }

    // ============================================
    // CONTACTS TESTS (7)
    // ============================================

    public function testShouldListContacts(): void
    {
        $response = self::$sevk->contacts->list();
        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('totalPages', $response);
        $this->assertIsArray($response['items']);
    }

    public function testShouldListContactsWithPagination(): void
    {
        $response = self::$sevk->contacts->list(1, 5);
        $this->assertEquals(1, $response['page']);
        $this->assertLessThanOrEqual(5, count($response['items']));
    }

    public function testShouldCreateContact(): void
    {
        $email = 'test-' . uniqid() . '@example.com';
        $contact = self::$sevk->contacts->create($email);
        $this->assertEquals($email, $contact['email']);
        $this->assertArrayHasKey('id', $contact);
        // Cleanup
        self::$sevk->contacts->delete($contact['id']);
    }

    public function testShouldGetContact(): void
    {
        $email = 'test-' . uniqid() . '@example.com';
        $created = self::$sevk->contacts->create($email);
        $contact = self::$sevk->contacts->get($created['id']);
        $this->assertEquals($created['id'], $contact['id']);
        $this->assertEquals($email, $contact['email']);
        // Cleanup
        self::$sevk->contacts->delete($created['id']);
    }

    public function testShouldUpdateContact(): void
    {
        $email = 'test-' . uniqid() . '@example.com';
        $created = self::$sevk->contacts->create($email);
        $updated = self::$sevk->contacts->update($created['id'], false);
        $this->assertFalse($updated['subscribed']);
        // Cleanup
        self::$sevk->contacts->delete($created['id']);
    }

    public function testShouldThrowErrorForNonExistentContact(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->contacts->get('non-existent-id');
    }

    public function testShouldDeleteContact(): void
    {
        $email = 'test-' . uniqid() . '@example.com';
        $created = self::$sevk->contacts->create($email);
        self::$sevk->contacts->delete($created['id']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->contacts->get($created['id']);
    }

    // ============================================
    // CONTACTS EXTENDED TESTS (3)
    // ============================================

    public function testShouldBulkUpdateContacts(): void
    {
        $email = 'test-' . uniqid() . '@example.com';
        $contact = self::$sevk->contacts->create($email, true);
        $result = self::$sevk->contacts->bulkUpdate([
            'contacts' => [['email' => $email, 'subscribed' => false]]
        ]);
        $this->assertNotNull($result);
        // Cleanup
        self::$sevk->contacts->delete($contact['id']);
    }

    public function testShouldGetContactEvents(): void
    {
        $email = 'test-' . uniqid() . '@example.com';
        $contact = self::$sevk->contacts->create($email);
        $result = self::$sevk->contacts->getEvents($contact['id']);
        $this->assertNotNull($result);
        // Cleanup
        self::$sevk->contacts->delete($contact['id']);
    }

    public function testShouldImportContacts(): void
    {
        $email = 'import-test-' . uniqid() . '@example.com';
        $result = self::$sevk->contacts->import([
            'contacts' => [['email' => $email]]
        ]);
        $this->assertNotNull($result);
    }

    // ============================================
    // AUDIENCES TESTS (7)
    // ============================================

    public function testShouldListAudiences(): void
    {
        $response = self::$sevk->audiences->list();
        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertIsArray($response['items']);
    }

    public function testShouldCreateAudience(): void
    {
        $name = 'Test Audience ' . uniqid();
        $audience = self::$sevk->audiences->create($name);
        $this->assertEquals($name, $audience['name']);
        $this->assertArrayHasKey('id', $audience);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldCreateAudienceWithAllFields(): void
    {
        $name = 'Full Audience ' . uniqid();
        $audience = self::$sevk->audiences->create($name, 'Test description', 'PUBLIC');
        $this->assertEquals($name, $audience['name']);
        $this->assertEquals('Test description', $audience['description']);
        $this->assertEquals('PUBLIC', $audience['usersCanSee']);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldGetAudience(): void
    {
        $name = 'Test Audience ' . uniqid();
        $created = self::$sevk->audiences->create($name);
        $audience = self::$sevk->audiences->get($created['id']);
        $this->assertEquals($created['id'], $audience['id']);
        $this->assertEquals($name, $audience['name']);
        // Cleanup
        self::$sevk->audiences->delete($created['id']);
    }

    public function testShouldUpdateAudience(): void
    {
        $name = 'Test Audience ' . uniqid();
        $created = self::$sevk->audiences->create($name);
        $newName = 'Updated Audience ' . uniqid();
        $updated = self::$sevk->audiences->update($created['id'], $newName);
        $this->assertEquals($newName, $updated['name']);
        // Cleanup
        self::$sevk->audiences->delete($created['id']);
    }

    public function testShouldAddContactsToAudience(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $contact = self::$sevk->contacts->create('test-' . uniqid() . '@example.com');
        $result = self::$sevk->audiences->addContacts($audience['id'], [$contact['id']]);
        $this->assertNotNull($result);
        // Cleanup
        self::$sevk->contacts->delete($contact['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldListContactsInAudience(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $contact = self::$sevk->contacts->create('test-' . uniqid() . '@example.com');
        self::$sevk->audiences->addContacts($audience['id'], [$contact['id']]);
        $result = self::$sevk->audiences->listContacts($audience['id']);
        $this->assertNotNull($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        // Cleanup
        self::$sevk->contacts->delete($contact['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldRemoveContactFromAudience(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $contact = self::$sevk->contacts->create('test-' . uniqid() . '@example.com');
        self::$sevk->audiences->addContacts($audience['id'], [$contact['id']]);
        self::$sevk->audiences->removeContact($audience['id'], $contact['id']);
        // Verify removal by listing contacts
        $result = self::$sevk->audiences->listContacts($audience['id']);
        $contactIds = array_map(fn($c) => $c['id'], $result['items']);
        $this->assertNotContains($contact['id'], $contactIds);
        // Cleanup
        self::$sevk->contacts->delete($contact['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldDeleteAudience(): void
    {
        $name = 'Test Audience ' . uniqid();
        $created = self::$sevk->audiences->create($name);
        self::$sevk->audiences->delete($created['id']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->audiences->get($created['id']);
    }

    // ============================================
    // TEMPLATES TESTS (6)
    // ============================================

    public function testShouldListTemplates(): void
    {
        $response = self::$sevk->templates->list();
        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertIsArray($response['items']);
    }

    public function testShouldCreateTemplate(): void
    {
        $title = 'Test Template ' . uniqid();
        $content = '<p>Hello World</p>';
        $template = self::$sevk->templates->create($title, $content);
        $this->assertEquals($title, $template['title']);
        $this->assertArrayHasKey('id', $template);
        // Cleanup
        self::$sevk->templates->delete($template['id']);
    }

    public function testShouldGetTemplate(): void
    {
        $title = 'Test Template ' . uniqid();
        $content = '<p>Hello World</p>';
        $created = self::$sevk->templates->create($title, $content);
        $template = self::$sevk->templates->get($created['id']);
        $this->assertEquals($created['id'], $template['id']);
        $this->assertEquals($title, $template['title']);
        // Cleanup
        self::$sevk->templates->delete($created['id']);
    }

    public function testShouldUpdateTemplate(): void
    {
        $title = 'Test Template ' . uniqid();
        $content = '<p>Hello World</p>';
        $created = self::$sevk->templates->create($title, $content);
        $newTitle = 'Updated Template ' . uniqid();
        $updated = self::$sevk->templates->update($created['id'], $newTitle);
        $this->assertEquals($newTitle, $updated['title']);
        // Cleanup
        self::$sevk->templates->delete($created['id']);
    }

    public function testShouldDuplicateTemplate(): void
    {
        $title = 'Test Template ' . uniqid();
        $content = '<p>Hello World</p>';
        $created = self::$sevk->templates->create($title, $content);
        $duplicated = self::$sevk->templates->duplicate($created['id']);
        $this->assertNotEquals($created['id'], $duplicated['id']);
        // Cleanup
        self::$sevk->templates->delete($created['id']);
        self::$sevk->templates->delete($duplicated['id']);
    }

    public function testShouldDeleteTemplate(): void
    {
        $title = 'Test Template ' . uniqid();
        $content = '<p>Hello World</p>';
        $created = self::$sevk->templates->create($title, $content);
        self::$sevk->templates->delete($created['id']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->templates->get($created['id']);
    }

    // ============================================
    // BROADCASTS TESTS (3)
    // ============================================

    public function testShouldListBroadcasts(): void
    {
        $response = self::$sevk->broadcasts->list();
        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertIsArray($response['items']);
    }

    public function testShouldListBroadcastsWithPagination(): void
    {
        $response = self::$sevk->broadcasts->list(1, 5);
        $this->assertEquals(1, $response['page']);
        $this->assertLessThanOrEqual(5, count($response['items']));
    }

    public function testShouldListBroadcastsWithSearch(): void
    {
        $response = self::$sevk->broadcasts->list(null, null, 'test');
        $this->assertArrayHasKey('items', $response);
        $this->assertIsArray($response['items']);
    }

    // ============================================
    // BROADCASTS CRUD TESTS (8)
    // ============================================

    public function testShouldCreateBroadcast(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast create');
        }
        $domainId = $domains['items'][0]['id'];

        $name = 'Test Broadcast ' . uniqid();
        $broadcast = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => $name,
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test broadcast body</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        $this->assertArrayHasKey('id', $broadcast);
        $this->assertIsString($broadcast['id']);
        $this->assertEquals($name, $broadcast['name']);
        $this->assertEquals('Test Subject', $broadcast['subject']);
        $this->assertEquals('DRAFT', $broadcast['status']);
        // Cleanup
        self::$sevk->broadcasts->delete($broadcast['id']);
    }

    public function testShouldGetBroadcast(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast get');
        }
        $domainId = $domains['items'][0]['id'];

        $created = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => 'Get Test ' . uniqid(),
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        $broadcast = self::$sevk->broadcasts->get($created['id']);
        $this->assertEquals($created['id'], $broadcast['id']);
        $this->assertEquals('Test Subject', $broadcast['subject']);
        // Cleanup
        self::$sevk->broadcasts->delete($created['id']);
    }

    public function testShouldUpdateBroadcast(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast update');
        }
        $domainId = $domains['items'][0]['id'];

        $created = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => 'Update Test ' . uniqid(),
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        $newName = 'Updated Broadcast ' . uniqid();
        $updated = self::$sevk->broadcasts->update($created['id'], ['name' => $newName]);
        $this->assertEquals($created['id'], $updated['id']);
        $this->assertEquals($newName, $updated['name']);
        // Cleanup
        self::$sevk->broadcasts->delete($created['id']);
    }

    public function testShouldDeleteBroadcast(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast delete');
        }
        $domainId = $domains['items'][0]['id'];

        $created = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => 'Delete Test ' . uniqid(),
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        self::$sevk->broadcasts->delete($created['id']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->broadcasts->get($created['id']);
    }

    public function testShouldGetBroadcastAnalytics(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast analytics');
        }
        $domainId = $domains['items'][0]['id'];

        $created = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => 'Analytics Test ' . uniqid(),
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        $result = self::$sevk->broadcasts->getAnalytics($created['id']);
        $this->assertNotNull($result);
        // Cleanup
        self::$sevk->broadcasts->delete($created['id']);
    }

    public function testShouldSendTestBroadcast(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast sendTest');
        }
        $domainId = $domains['items'][0]['id'];

        $created = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => 'SendTest Test ' . uniqid(),
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        try {
            $result = self::$sevk->broadcasts->sendTest($created['id'], ['emails' => ['test@example.com']]);
            $this->assertNotNull($result);
        } catch (\Exception $e) {
            // May fail if domain is unverified, which is expected
            $this->assertNotEmpty($e->getMessage());
        }
        // Cleanup
        self::$sevk->broadcasts->delete($created['id']);
    }

    public function testShouldHandleSendErrorForDraftBroadcast(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast send error');
        }
        $domainId = $domains['items'][0]['id'];

        $created = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => 'Send Error Test ' . uniqid(),
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        try {
            self::$sevk->broadcasts->send($created['id']);
            // If it succeeds, that's fine too
        } catch (\Exception $e) {
            // Expected to fail if broadcast is not ready to send
            $this->assertNotEmpty($e->getMessage());
        }
        // Cleanup
        try {
            self::$sevk->broadcasts->delete($created['id']);
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }

    public function testShouldHandleCancelForNonSendingBroadcast(): void
    {
        $domains = self::$sevk->domains->list();
        if (count($domains['items']) === 0) {
            $this->markTestSkipped('No domains available to test broadcast cancel error');
        }
        $domainId = $domains['items'][0]['id'];

        $created = self::$sevk->broadcasts->create([
            'domainId' => $domainId,
            'name' => 'Cancel Error Test ' . uniqid(),
            'subject' => 'Test Subject',
            'body' => '<section><paragraph>Test</paragraph></section>',
            'senderName' => 'Test Sender',
            'senderEmail' => 'test',
            'targetType' => 'ALL',
        ]);
        try {
            self::$sevk->broadcasts->cancel($created['id']);
        } catch (\Exception $e) {
            // Expected to fail if broadcast is not in a cancellable state
            $this->assertNotEmpty($e->getMessage());
        }
        // Cleanup
        try {
            self::$sevk->broadcasts->delete($created['id']);
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }

    // ============================================
    // DOMAINS TESTS (2)
    // ============================================

    public function testShouldListDomains(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $response = self::$sevk->domains->list();
        $this->assertArrayHasKey('items', $response);
        $this->assertIsArray($response['items']);
    }

    public function testShouldListOnlyVerifiedDomains(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $response = self::$sevk->domains->list(true);
        $this->assertArrayHasKey('items', $response);
        $this->assertIsArray($response['items']);
        foreach ($response['items'] as $domain) {
            $this->assertTrue($domain['verified']);
        }
    }

    // ============================================
    // TOPICS TESTS (5)
    // ============================================

    public function testShouldListTopics(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $response = self::$sevk->topics->list($audience['id']);
        $this->assertArrayHasKey('items', $response);
        $this->assertIsArray($response['items']);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldCreateTopic(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $topic = self::$sevk->topics->create($audience['id'], 'Test Topic ' . uniqid());
        $this->assertArrayHasKey('id', $topic);
        $this->assertArrayHasKey('name', $topic);
        // Cleanup
        self::$sevk->topics->delete($audience['id'], $topic['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldGetTopic(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $created = self::$sevk->topics->create($audience['id'], 'Test Topic ' . uniqid());
        $topic = self::$sevk->topics->get($audience['id'], $created['id']);
        $this->assertEquals($created['id'], $topic['id']);
        // Cleanup
        self::$sevk->topics->delete($audience['id'], $topic['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldUpdateTopic(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $created = self::$sevk->topics->create($audience['id'], 'Test Topic ' . uniqid());
        $newName = 'Updated Topic ' . uniqid();
        $updated = self::$sevk->topics->update($audience['id'], $created['id'], $newName);
        $this->assertEquals($newName, $updated['name']);
        // Cleanup
        self::$sevk->topics->delete($audience['id'], $created['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldAddContactsToTopic(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $topic = self::$sevk->topics->create($audience['id'], 'Test Topic ' . uniqid());
        $contact = self::$sevk->contacts->create('test-' . uniqid() . '@example.com');
        self::$sevk->audiences->addContacts($audience['id'], [$contact['id']]);
        $result = self::$sevk->topics->addContacts($audience['id'], $topic['id'], ['contactIds' => [$contact['id']]]);
        $this->assertNotNull($result);
        // Cleanup
        self::$sevk->topics->delete($audience['id'], $topic['id']);
        self::$sevk->contacts->delete($contact['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldRemoveContactFromTopic(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $topic = self::$sevk->topics->create($audience['id'], 'Test Topic ' . uniqid());
        $contact = self::$sevk->contacts->create('test-' . uniqid() . '@example.com');
        self::$sevk->audiences->addContacts($audience['id'], [$contact['id']]);
        self::$sevk->topics->addContacts($audience['id'], $topic['id'], ['contactIds' => [$contact['id']]]);
        self::$sevk->topics->removeContact($audience['id'], $topic['id'], $contact['id']);
        // Verify removal by listing contacts in the topic
        $result = self::$sevk->topics->listContacts($audience['id'], $topic['id']);
        $contactIds = array_map(fn($c) => $c['id'], $result['items']);
        $this->assertNotContains($contact['id'], $contactIds);
        // Cleanup
        self::$sevk->topics->delete($audience['id'], $topic['id']);
        self::$sevk->contacts->delete($contact['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldDeleteTopic(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $created = self::$sevk->topics->create($audience['id'], 'Test Topic ' . uniqid());
        self::$sevk->topics->delete($audience['id'], $created['id']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->topics->get($audience['id'], $created['id']);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    // ============================================
    // SEGMENTS TESTS (5)
    // ============================================

    public function testShouldListSegments(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $response = self::$sevk->segments->list($audience['id']);
        $this->assertArrayHasKey('items', $response);
        $this->assertIsArray($response['items']);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldCreateSegment(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $segment = self::$sevk->segments->create(
            $audience['id'],
            'Test Segment ' . uniqid(),
            [['field' => 'email', 'operator' => 'contains', 'value' => '@']],
            'AND'
        );
        $this->assertArrayHasKey('id', $segment);
        $this->assertArrayHasKey('name', $segment);
        // Cleanup
        self::$sevk->segments->delete($audience['id'], $segment['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldGetSegment(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $created = self::$sevk->segments->create(
            $audience['id'],
            'Test Segment ' . uniqid(),
            [['field' => 'email', 'operator' => 'contains', 'value' => '@']],
            'AND'
        );
        $segment = self::$sevk->segments->get($audience['id'], $created['id']);
        $this->assertEquals($created['id'], $segment['id']);
        // Cleanup
        self::$sevk->segments->delete($audience['id'], $segment['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldUpdateSegment(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $created = self::$sevk->segments->create(
            $audience['id'],
            'Test Segment ' . uniqid(),
            [['field' => 'email', 'operator' => 'contains', 'value' => '@']],
            'AND'
        );
        $newName = 'Updated Segment ' . uniqid();
        $updated = self::$sevk->segments->update($audience['id'], $created['id'], $newName);
        $this->assertEquals($newName, $updated['name']);
        // Cleanup
        self::$sevk->segments->delete($audience['id'], $created['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldCalculateSegment(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $segment = self::$sevk->segments->create(
            $audience['id'],
            'Test Segment ' . uniqid(),
            [['field' => 'email', 'operator' => 'contains', 'value' => '@example.com']],
            'AND'
        );
        $result = self::$sevk->segments->calculate($audience['id'], $segment['id']);
        $this->assertNotNull($result);
        // Cleanup
        self::$sevk->segments->delete($audience['id'], $segment['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldPreviewSegment(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $result = self::$sevk->segments->preview($audience['id'], [
            'rules' => [['field' => 'email', 'operator' => 'contains', 'value' => '@example.com']],
            'operator' => 'AND',
        ]);
        $this->assertNotNull($result);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldDeleteSegment(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $created = self::$sevk->segments->create(
            $audience['id'],
            'Test Segment ' . uniqid(),
            [['field' => 'email', 'operator' => 'contains', 'value' => '@']],
            'AND'
        );
        self::$sevk->segments->delete($audience['id'], $created['id']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->segments->get($audience['id'], $created['id']);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    // ============================================
    // SUBSCRIPTIONS TESTS (2)
    // ============================================

    public function testShouldSubscribeContact(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $email = 'test-' . uniqid() . '@example.com';
        $result = self::$sevk->subscriptions->subscribe($email, $audience['id']);
        $this->assertNotNull($result);
        $this->assertArrayHasKey('contact', $result);
        $this->assertEquals($email, $result['contact']['email']);
        // Cleanup
        self::$sevk->audiences->delete($audience['id']);
    }

    public function testShouldUnsubscribeContactByEmail(): void
    {
        $email = 'unsubscribe-test-' . uniqid() . '@example.com';
        $contact = self::$sevk->contacts->create($email, true);
        self::$sevk->subscriptions->unsubscribe($email);
        $updatedContact = self::$sevk->contacts->get($contact['id']);
        $this->assertFalse($updatedContact['subscribed']);
        // Cleanup
        self::$sevk->contacts->delete($contact['id']);
    }

    // ============================================
    // DOMAINS UPDATE TESTS (2)
    // ============================================

    public function testShouldUpdateDomainWithClickTracking(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $response = self::$sevk->domains->list();
        if (count($response['items']) > 0) {
            $domainId = $response['items'][0]['id'];
            $result = self::$sevk->domains->update($domainId, ['clickTracking' => true]);
            $this->assertNotNull($result);
            $this->assertEquals($domainId, $result['id']);
            $this->assertTrue($result['clickTracking']);
        } else {
            $this->markTestSkipped('No domains available to test update');
        }
    }

    public function testShouldUpdateDomainWithClickTrackingDisabled(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $response = self::$sevk->domains->list();
        if (count($response['items']) > 0) {
            $domainId = $response['items'][0]['id'];
            $result = self::$sevk->domains->update($domainId, ['clickTracking' => false]);
            $this->assertNotNull($result);
            $this->assertFalse($result['clickTracking']);
        } else {
            $this->markTestSkipped('No domains available to test update');
        }
    }

    // ============================================
    // DOMAINS CRUD TESTS (5)
    // ============================================

    public function testShouldCreateDomain(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $subdomain = 'test-' . uniqid() . '-' . substr(md5((string) mt_rand()), 0, 7) . '.example.com';
        $domain = self::$sevk->domains->create(['domain' => $subdomain, 'email' => 'test@' . $subdomain]);
        $this->assertArrayHasKey('id', $domain);
        $this->assertIsString($domain['id']);
        $this->assertEquals($subdomain, $domain['domain']);
        // Cleanup
        self::$sevk->domains->delete($domain['id']);
    }

    public function testShouldGetDomain(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $subdomain = 'test-' . uniqid() . '.example.com';
        $created = self::$sevk->domains->create(['domain' => $subdomain, 'email' => 'test@' . $subdomain]);
        $domain = self::$sevk->domains->get($created['id']);
        $this->assertEquals($created['id'], $domain['id']);
        // Cleanup
        self::$sevk->domains->delete($created['id']);
    }

    public function testShouldGetDnsDnsRecords(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $subdomain = 'test-' . uniqid() . '.example.com';
        $created = self::$sevk->domains->create(['domain' => $subdomain, 'email' => 'test@' . $subdomain]);
        $result = self::$sevk->domains->getDnsRecords($created['id']);
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        // Cleanup
        self::$sevk->domains->delete($created['id']);
    }

    public function testShouldGetAvailableRegions(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $result = self::$sevk->domains->getRegions();
        $this->assertNotNull($result);
    }

    public function testShouldVerifyDomain(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $subdomain = 'test-' . uniqid() . '.example.com';
        $created = self::$sevk->domains->create(['domain' => $subdomain, 'email' => 'test@' . $subdomain]);
        try {
            $result = self::$sevk->domains->verify($created['id']);
            $this->assertNotNull($result);
        } catch (\Exception $e) {
            // Expected to fail for test domains without proper DNS records
            $this->assertNotEmpty($e->getMessage());
        }
        // Cleanup
        self::$sevk->domains->delete($created['id']);
    }

    public function testShouldDeleteDomain(): void
    {
        if (getenv('INCLUDE_DOMAIN_TESTS') !== 'true') { $this->markTestSkipped('INCLUDE_DOMAIN_TESTS not set'); }
        $subdomain = 'test-' . uniqid() . '.example.com';
        $created = self::$sevk->domains->create(['domain' => $subdomain, 'email' => 'test@' . $subdomain]);
        self::$sevk->domains->delete($created['id']);
        // Verify deletion
        try {
            self::$sevk->domains->get($created['id']);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Accept any error as confirmation of deletion
            $this->assertNotEmpty($e->getMessage());
        }
    }

    // ============================================
    // BROADCASTS EXTENDED TESTS (4)
    // ============================================

    public function testShouldGetBroadcastStatus(): void
    {
        $response = self::$sevk->broadcasts->list(1, 1);
        if (count($response['items']) > 0) {
            $broadcastId = $response['items'][0]['id'];
            $result = self::$sevk->broadcasts->getStatus($broadcastId);
            $this->assertNotNull($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('No broadcasts available to test getStatus');
        }
    }

    public function testShouldGetBroadcastEmails(): void
    {
        $response = self::$sevk->broadcasts->list(1, 1);
        if (count($response['items']) > 0) {
            $broadcastId = $response['items'][0]['id'];
            $result = self::$sevk->broadcasts->getEmails($broadcastId);
            $this->assertNotNull($result);
            $this->assertArrayHasKey('items', $result);
            $this->assertIsArray($result['items']);
        } else {
            $this->markTestSkipped('No broadcasts available to test getEmails');
        }
    }

    public function testShouldEstimateBroadcastCost(): void
    {
        $response = self::$sevk->broadcasts->list(1, 1);
        if (count($response['items']) > 0) {
            $broadcastId = $response['items'][0]['id'];
            $result = self::$sevk->broadcasts->estimateCost($broadcastId);
            $this->assertNotNull($result);
        } else {
            $this->markTestSkipped('No broadcasts available to test estimateCost');
        }
    }

    public function testShouldListActiveBroadcasts(): void
    {
        $result = self::$sevk->broadcasts->listActive();
        $this->assertNotNull($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
    }

    // ============================================
    // TOPICS LIST CONTACTS TESTS (1)
    // ============================================

    public function testShouldListContactsForTopic(): void
    {
        $audience = self::$sevk->audiences->create('Test Audience ' . uniqid());
        $topic = self::$sevk->topics->create($audience['id'], 'Test Topic ' . uniqid());
        $result = self::$sevk->topics->listContacts($audience['id'], $topic['id']);
        $this->assertNotNull($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        // Cleanup
        self::$sevk->topics->delete($audience['id'], $topic['id']);
        self::$sevk->audiences->delete($audience['id']);
    }

    // ============================================
    // WEBHOOKS TESTS (FULL CRUD LIFECYCLE) (7)
    // ============================================

    public function testShouldListWebhooks(): void
    {
        $result = self::$sevk->webhooks->list();
        $this->assertNotNull($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
    }

    public function testWebhookFullLifecycle(): void
    {
        // Create webhook
        $created = self::$sevk->webhooks->create(
            'https://example.com/webhook-test',
            ['contact.subscribed']
        );
        $this->assertArrayHasKey('id', $created);
        $this->assertEquals('https://example.com/webhook-test', $created['url']);
        $webhookId = $created['id'];

        // Get webhook
        $fetched = self::$sevk->webhooks->get($webhookId);
        $this->assertEquals($webhookId, $fetched['id']);
        $this->assertEquals('https://example.com/webhook-test', $fetched['url']);

        // Update webhook
        $updated = self::$sevk->webhooks->update($webhookId, [
            'url' => 'https://example.com/webhook-updated',
            'events' => ['contact.subscribed', 'contact.unsubscribed']
        ]);
        $this->assertEquals($webhookId, $updated['id']);
        $this->assertEquals('https://example.com/webhook-updated', $updated['url']);

        // Test webhook
        $testResult = self::$sevk->webhooks->test($webhookId);
        $this->assertNotNull($testResult);

        // Delete webhook
        self::$sevk->webhooks->delete($webhookId);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->webhooks->get($webhookId);
    }

    public function testShouldListWebhookEvents(): void
    {
        $result = self::$sevk->webhooks->listEvents();
        $this->assertNotNull($result);
    }

    // ============================================
    // EVENTS TESTS (3)
    // ============================================

    public function testShouldListEvents(): void
    {
        $result = self::$sevk->events->list();
        $this->assertNotNull($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
    }

    public function testShouldListEventsWithFilters(): void
    {
        $result = self::$sevk->events->list(['type' => 'SENT', 'limit' => 5]);
        $this->assertNotNull($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
    }

    public function testShouldGetEventStats(): void
    {
        $result = self::$sevk->events->stats();
        $this->assertNotNull($result);
    }

    // ============================================
    // USAGE TESTS (1)
    // ============================================

    public function testShouldGetUsage(): void
    {
        $result = self::$sevk->getUsage();
        $this->assertNotNull($result);
    }

    // ============================================
    // EMAILS TESTS (4)
    // ============================================

    public function testShouldRejectEmailWithUnverifiedDomain(): void
    {
        $this->expectException(\Exception::class);
        self::$sevk->emails->send([
            'to' => 'test@example.com',
            'subject' => 'Test Email',
            'from' => 'test@unverified-domain.com',
            'html' => '<p>Hello</p>'
        ]);
    }

    public function testShouldRejectEmailWithDomainNotOwnedByProject(): void
    {
        $this->expectException(\Exception::class);
        self::$sevk->emails->send([
            'to' => 'test@example.com',
            'subject' => 'Test Email',
            'from' => 'no-reply@not-my-domain.io',
            'html' => '<p>Hello</p>'
        ]);
    }

    public function testShouldRejectEmailWithInvalidFromAddress(): void
    {
        $this->expectException(\Exception::class);
        // API may return 400 (bad request) or 422 (validation error) for invalid email format
        $this->expectExceptionMessageMatches('/400|422|invalid|email/i');
        self::$sevk->emails->send([
            'to' => 'test@example.com',
            'subject' => 'Test Email',
            'from' => 'invalid-email-without-domain',
            'html' => '<p>Hello</p>'
        ]);
    }

    public function testShouldThrowErrorForNonExistentEmailId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        // Use a valid UUID format so the API returns 404 (not 400 for bad format)
        self::$sevk->emails->get('00000000-0000-0000-0000-000000000000');
    }

    public function testShouldRejectBulkEmailWithUnverifiedDomain(): void
    {
        $result = self::$sevk->emails->sendBulk([
            [
                'to' => 'test1@example.com',
                'subject' => 'Bulk Test 1',
                'html' => '<p>Hello 1</p>',
                'from' => 'no-reply@unverified-domain.com',
            ],
            [
                'to' => 'test2@example.com',
                'subject' => 'Bulk Test 2',
                'html' => '<p>Hello 2</p>',
                'from' => 'no-reply@unverified-domain.com',
            ],
        ]);
        $this->assertNotNull($result);
        $this->assertEquals(2, $result['failed']);
    }

    public function testShouldReturnProperErrorMessageForDomainVerification(): void
    {
        try {
            self::$sevk->emails->send([
                'to' => 'recipient@example.com',
                'subject' => 'Test Email',
                'from' => 'sender@random-unverified-domain.xyz',
                'html' => '<p>Hello World</p>'
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $message = strtolower($e->getMessage());
            $this->assertTrue(
                str_contains($message, 'domain') ||
                str_contains($message, 'verified') ||
                str_contains($message, 'forbidden') ||
                str_contains($message, '403')
            );
        }
    }

    // ============================================
    // ERROR HANDLING TESTS (2)
    // ============================================

    public function testShouldHandle404ErrorsGracefully(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/404/');
        self::$sevk->contacts->get('non-existent-id-12345');
    }

    public function testShouldHandleValidationErrors(): void
    {
        $this->expectException(\Exception::class);
        // API may return 400 or 422 for validation errors
        $this->expectExceptionMessageMatches('/400|422|invalid|email|validation/i');
        self::$sevk->contacts->create('invalid-email');
    }

    // ============================================
    // MARKUP RENDERER TESTS (18)
    // ============================================

    public function testShouldReturnHtmlDocumentStructure(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<!DOCTYPE html', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('<body', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    public function testShouldIncludeMetaTags(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('charset=UTF-8', $html);
        $this->assertStringContainsString('viewport', $html);
    }

    public function testShouldIncludeTitleWhenProvided(): void
    {
        $markup = '<email><head><title>Test Email</title></head><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<title>Test Email</title>', $html);
    }

    public function testShouldIncludePreviewTextWhenProvided(): void
    {
        $markup = '<email><head><preview>Preview text here</preview></head><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Preview text here', $html);
        $this->assertStringContainsString('display:none', $html);
    }

    public function testShouldIncludeCustomStylesWhenProvided(): void
    {
        $markup = '<email><head><style>.custom { color: red; }</style></head><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('.custom { color: red; }', $html);
    }

    public function testShouldRenderEmptyMarkupWithDocumentStructure(): void
    {
        $html = \Sevk\Markup\render('');
        $this->assertStringContainsString('<!DOCTYPE html', $html);
        $this->assertStringContainsString('<body', $html);
    }

    public function testShouldHaveDefaultBodyStyles(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('margin:0', $html);
        $this->assertStringContainsString('padding:0', $html);
        $this->assertStringContainsString('font-family', $html);
    }

    public function testShouldIncludeHtmlLangAttribute(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('lang="en"', $html);
    }

    public function testShouldIncludeHtmlDirAttribute(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('dir="ltr"', $html);
    }

    public function testShouldIncludeContentTypeMetaTag(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Content-Type', $html);
        $this->assertStringContainsString('text/html', $html);
    }

    public function testShouldIncludeXhtmlDoctype(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('XHTML 1.0 Transitional', $html);
    }

    public function testShouldNotIncludeBackgroundColorInBodyStyles(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringNotContainsString('background-color:#ffffff', $html);
    }

    public function testShouldRenderMailTagSameAsEmailTag(): void
    {
        $markup = '<mail><body></body></mail>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<!DOCTYPE html', $html);
        $this->assertStringContainsString('<body', $html);
    }

    public function testShouldHandleComplexMarkupStructure(): void
    {
        $markup = '<email>
            <head>
                <title>Complex Email</title>
                <preview>This is a preview</preview>
                <style>.test { color: blue; }</style>
            </head>
            <body></body>
        </email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Complex Email', $html);
        $this->assertStringContainsString('This is a preview', $html);
        $this->assertStringContainsString('.test { color: blue; }', $html);
    }

    public function testShouldIncludeFontLinksWhenProvided(): void
    {
        $markup = '<email><head><font name="Roboto" url="https://fonts.googleapis.com/css?family=Roboto" /></head><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('fonts.googleapis.com', $html);
    }

    public function testShouldHandleMultipleFonts(): void
    {
        $markup = '<email><head>
            <font name="Roboto" url="https://fonts.googleapis.com/css?family=Roboto" />
            <font name="Open Sans" url="https://fonts.googleapis.com/css?family=Open+Sans" />
        </head><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Roboto', $html);
        $this->assertStringContainsString('Open+Sans', $html);
    }

    public function testShouldHandleWhitespaceInMarkup(): void
    {
        $markup = '   <email>   <body>   </body>   </email>   ';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<!DOCTYPE html', $html);
        $this->assertStringContainsString('<body', $html);
    }

    public function testShouldReturnStringType(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
}
