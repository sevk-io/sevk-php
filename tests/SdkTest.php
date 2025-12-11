<?php

declare(strict_types=1);

namespace Sevk\Tests;

use PHPUnit\Framework\TestCase;
use Sevk\Sevk;
use GuzzleHttp\Client;

class SdkTest extends TestCase
{
    private static ?Sevk $sevk = null;
    private static string $baseUrl = 'http://localhost:4000';

    public static function setUpBeforeClass(): void
    {
        $apiKey = self::setupTestEnvironment();
        self::$sevk = new Sevk($apiKey, ['baseUrl' => self::$baseUrl]);
    }

    private static function setupTestEnvironment(): string
    {
        $client = new Client(['base_uri' => 'http://localhost:4000', 'http_errors' => false]);

        // Register a new test user
        $testEmail = 'sdk-test-' . time() . '-' . mt_rand(1000, 9999) . '@test.example.com';
        $response = $client->post('/auth/register', [
            'json' => [
                'email' => $testEmail,
                'password' => 'TestPassword123!'
            ]
        ]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['token'])) {
            throw new \RuntimeException('Failed to register user: ' . json_encode($data));
        }
        $token = $data['token'];

        // Create Project
        $response = $client->post('/projects', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => [
                'name' => 'Test Project',
                'slug' => 'test-project-' . time(),
                'supportEmail' => 'support@test.com'
            ]
        ]);
        $projectData = json_decode($response->getBody()->getContents(), true);

        if (!isset($projectData['project']['id'])) {
            throw new \RuntimeException('Failed to create project: ' . json_encode($projectData));
        }
        $projectId = $projectData['project']['id'];

        // Create API Key
        $response = $client->post("/projects/{$projectId}/api-keys", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => ['title' => 'Test Key', 'fullAccess' => true]
        ]);
        $apiKeyData = json_decode($response->getBody()->getContents(), true);

        if (!isset($apiKeyData['apiKey']['key'])) {
            throw new \RuntimeException('Failed to create API key: ' . json_encode($apiKeyData));
        }

        return $apiKeyData['apiKey']['key'];
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
    // DOMAINS TESTS (2)
    // ============================================

    public function testShouldListDomains(): void
    {
        $response = self::$sevk->domains->list();
        $this->assertArrayHasKey('domains', $response);
        $this->assertIsArray($response['domains']);
    }

    public function testShouldListOnlyVerifiedDomains(): void
    {
        $response = self::$sevk->domains->list(true);
        $this->assertArrayHasKey('domains', $response);
        $this->assertIsArray($response['domains']);
        foreach ($response['domains'] as $domain) {
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

    public function testShouldIncludeBackgroundColorInBodyStyles(): void
    {
        $markup = '<email><body></body></email>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('background-color', $html);
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
