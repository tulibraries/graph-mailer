<?php
namespace APP\plugins\generic\graphMailer;

use PKP\plugins\GenericPlugin;
use PKP\config\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\MailManager;
use InnoGE\LaravelMsGraphMail\Services\MicrosoftGraphApiService;
use InnoGE\LaravelMsGraphMail\MicrosoftGraphTransport;

require_once(dirname(__FILE__) . '/vendor/autoload.php');

class GraphMailerPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = NULL)
    {
        $success = parent::register($category, $path);

	if ($success) {
		$this->switchToGraphMail();
	}
        return $success;
    }

    /**
     * Provide a name for this plugin
     *
     * The name will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDisplayName()
	{
        return 'Graph Mailer';
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDescription()
	{
        return 'Switch the default mail driver to Microsoft Graph.';
    }


    private function switchToGraphMail() {
        // Retrieve config from OJS’s config.inc.php (or env)
        $tenantId     = Config::getVar('email', 'azure_tenant_id');
        $clientId     = Config::getVar('email', 'azure_client_id');
        $clientSecret = Config::getVar('email', 'azure_client_secret');
        $accessTokenTtl = Config::getVar('email', 'azure_access_token_ttl', 3000);

        /** @var MailManager $manager */
        $manager = Mail::getFacadeRoot();

        $service = new MicrosoftGraphApiService(
            tenantId: $tenantId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            accessTokenTtl: $accessTokenTtl);

        $manager->extend('microsoft-graph', function($config) use ($service) {
            return new MicrosoftGraphTransport($service);
        });

	// Inject mailer into configuration.
	$container = \PKP\core\PKPApplication::get()->getContainer();
	$config = $container->get('config');
	$mailers = $config->get('mail.mailers', []);
	$mailers['microsoft-graph'] = [
		'transport' => 'microsoft-graph',
	];
	$config->set('mail.mailers', $mailers);

        // Swap the default mailer
        $manager->setDefaultDriver('microsoft-graph');
    }
}
