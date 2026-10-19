<?php

declare(strict_types=1);

namespace Sevk;

use Sevk\Resources\Activity;
use Sevk\Resources\Audiences;
use Sevk\Resources\Broadcasts;
use Sevk\Resources\Contacts;
use Sevk\Resources\Domains;
use Sevk\Resources\Emails;
use Sevk\Resources\Inbound;
use Sevk\Resources\Outbound;
use Sevk\Resources\Segments;
use Sevk\Resources\Subscriptions;
use Sevk\Resources\Templates;
use Sevk\Resources\Topics;
use Sevk\Resources\Usage;
use Sevk\Resources\Webhooks;
use Sevk\Transport\HttpTransport;
use Sevk\Transport\Transport;

class Sevk
{
    public const VERSION = '1.1.0';

    public readonly Contacts $contacts;
    public readonly Audiences $audiences;
    public readonly Templates $templates;
    public readonly Broadcasts $broadcasts;
    public readonly Domains $domains;
    public readonly Topics $topics;
    public readonly Segments $segments;
    public readonly Subscriptions $subscriptions;
    public readonly Emails $emails;
    public readonly Webhooks $webhooks;
    public readonly Activity $activity;
    public readonly Inbound $inbound;
    public readonly Outbound $outbound;
    public readonly Usage $usage;

    public function __construct(string $apiKey, ?array $options = null, ?Transport $transport = null)
    {
        $client = $transport ?? new HttpTransport($apiKey, $options ?? []);

        $this->contacts = new Contacts($client);
        $this->audiences = new Audiences($client);
        $this->templates = new Templates($client);
        $this->broadcasts = new Broadcasts($client);
        $this->domains = new Domains($client);
        $this->topics = new Topics($client);
        $this->segments = new Segments($client);
        $this->subscriptions = new Subscriptions($client);
        $this->emails = new Emails($client);
        $this->webhooks = new Webhooks($client);
        $this->activity = new Activity($client);
        $this->inbound = new Inbound($client);
        $this->outbound = new Outbound($client);
        $this->usage = new Usage($client);
    }
}
