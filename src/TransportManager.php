<?php namespace Sboo\Laravel5MailjetFix;

use Sboo\Laravel5MailjetFix\Transport\MailjetTransport;
use Illuminate\Mail\TransportManager as BaseTransportManager;

class TransportManager extends BaseTransportManager {

    /**
     * Create an instance of the Mailjet Swift Transport driver.
     *
     * @return MailjetTransport
     */
    protected function createMailjetDriver()
    {
        $config = $this->app['config']->get('services.mailjet', []);

        return new MailjetTransport(
            $this->getHttpClient($config),
            $config['key'], $config['secret']
        );
    }

}