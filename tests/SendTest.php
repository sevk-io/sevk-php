<?php

declare(strict_types=1);

namespace Sevk\Tests;

use PHPUnit\Framework\TestCase;
use Sevk\Sevk;

final class SendTest extends TestCase
{
    public function testSendsAMarkupEmailThroughTheSevkApi(): void
    {
        $apiKey = getenv('SEVK_API_KEY');
        $from = getenv('SEVK_TEST_FROM');
        $to = getenv('SEVK_TEST_TO');
        if ($apiKey === false || $apiKey === '' || $from === false || $from === '' || $to === false || $to === '') {
            self::markTestSkipped('SEVK_API_KEY, SEVK_TEST_FROM and SEVK_TEST_TO are required');
        }

        $baseUrl = getenv('SEVK_BASE_URL');
        $client = new Sevk($apiKey, $baseUrl === false ? [] : ['baseUrl' => $baseUrl]);
        $response = $client->emails->send([
            'from' => $from,
            'to' => $to,
            'subject' => 'Sevk SDK send test',
            'markup' => '<section><paragraph>Hello from the Sevk PHP SDK</paragraph></section>',
        ]);

        self::assertIsString($response['id']);
        self::assertNotSame('', $response['id']);
    }
}
