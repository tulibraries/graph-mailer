<?php
namespace Tulibraries\GraphMailer;

use PKP\plugins\GenericPlugin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\MailManager;
use InnoGE\LaravelMsGraphMail\Services;

class GraphMailerPlugin extends GenericPlugin
{
    public function register($category, $path)
    {
        parent::register($category, $path);

        // Retrieve config from OJS’s config.inc.php (or env)
        $tenantId     = $this->getSetting('azure_tenant_id');
        $clientId     = $this->getSetting('azure_client_id');
        $clientSecret = $this->getSetting('azure_client_secret');
        $accessTokenTtl = $this->getSetting('azure_access_token_ttl') ?? 3000;

        /** @var MailManager $manager */
        $manager = Mail::getFacadeRoot();

        $service = MicrosoftGraphApiService(
            tenantId: $tenantId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            accessTokenTtl: $accessTokenTtl);

        $manager->extend('microsoft-graph', function() use ($tenantId, $clientId, $clientSecret) {
            return new \Innoge\MSGraphMail\Transport\MicrosoftGraphTransport($service);
        });

        // Swap the default mailer
        $manager->setDefaultDriver('microsoft-graph');

        return true;
    }
}

