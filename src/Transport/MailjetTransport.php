<?php

namespace Sboo\Laravel5Mailjet\Transport;

use Swift_Mime_Message;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\Transport;

class MailjetTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The Mailjet API key.
     *
     * @var string
     */
    protected $key;

    /**
     * The Mailjet API secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * THe Mailjet API end-point.
     *
     * @var string
     */
    protected $url;

    /**
     * Create a new Mailjet transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @param  string  $domain
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->client = $client;
        $this->url = 'https://api.mailjet.com/v3/send/message';
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $options = [
            'auth' => [$this->key, $this->secret],
        ];

        $to = $this->getTo($message);
        $from = $this->getFrom($message);
        $subject = $message->getSubject();
        $body = $this->getBody($message);

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $options['multipart'] = array_merge($this->getBody($message), [
                ['name' => 'to', 'contents' => $to],
                ['name' => 'from', 'contents' => $from],
                ['name' => 'subject', 'contents' => $subject],
            ]);
        } else {
            $formated_body = [];
            foreach($body as $part)
            {
                $formated_body[$part['name']] = $part['contents'];
            }

            $options['body'] = [
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'message' => $formated_body,
            ];
        }

        $this->client->post($this->url, $options);

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_Message  $message
     * @return array
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        $formatted = [];

        $contacts = array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );

        foreach ($contacts as $address => $display) {
            $formatted[] = $display ? $display." <{$address}>" : $address;
        }

        return implode(',', $formatted);
    }

    /**
     * Get the "from" payload field for the API request.
     *
     * @param  \Swift_Mime_Message  $message
     * @return array
     */
    protected function getFrom(Swift_Mime_Message $message)
    {
        $formatted = [];
        foreach ($message->getFrom() as $address => $display) {
            $formatted[] = $display ? $display." <$address>" : $address;
        }

        return $formatted[0];
    }

    /**
     * Get the "body" payload field for the Guzzle request.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    protected function getBody(Swift_Mime_Message $message) {
        $body = array();

        $htmlMessage = $message->getBody();
        if($message->getChildren()) {
            foreach($message->getChildren() as $child) {

                if(str_contains($htmlMessage, $child->getId())) {
                    $htmlMessage = str_replace($child->getId(), $child->getFilename(), $htmlMessage);
                    $body[] = [
                        'name' => 'inlineattachment',
                        'contents' => $child->getBody(),
                        'filename' => $child->getFilename(),
                        'headers' => ['Content-Type' => $child->getContentType()],
                    ];
                } else {
                    switch(get_class($child)) {
                        case 'Swift_Attachment':
                        case 'Swift_Image':
                            $body[] = [
                                'name' => 'attachment',
                                'contents' => $child->getBody(),
                                'filename' => $child->getFilename(),
                                'headers' => ['Content-Type' => $child->getContentType()],
                            ];
                            break;
                        case 'Swift_MimePart':
                            switch($child->getContentType()){
                                case 'text/plain':
                                    $body[] = [
                                        'name' => 'text',
                                        'contents' => $child->getBody(),
                                    ];
                                    break;
                            }
                            break;
                    }
                }
            }
        }
        $body[] = [
            'name' => 'html',
            'contents' => $htmlMessage,
        ];

        return $body;
    }

    /**
     * Get the API key being used by the transport.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * @param  string  $key
     * @return string
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }

    /**
     * Get the API secret being used by the transport.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set the API secret being used by the transport.
     *
     * @param  string  $secret
     * @return void
     */
    public function setSecret($secret)
    {
        return $this->secret = $secret;
    }
}
