<?php

/**
 * This code was generated by
 * ___ _ _ _ _ _    _ ____    ____ ____ _    ____ ____ _  _ ____ ____ ____ ___ __   __
 *  |  | | | | |    | |  | __ |  | |__| | __ | __ |___ |\ | |___ |__/ |__|  | |  | |__/
 *  |  |_|_| | |___ | |__|    |__| |  | |    |__] |___ | \| |___ |  \ |  |  | |__| |  \
 *
 * Twilio - Preview
 * This is the public Twilio REST API.
 *
 * NOTE: This class is auto generated by OpenAPI Generator.
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */


namespace Twilio\Rest\Preview\HostedNumbers;

use Twilio\Exceptions\TwilioException;
use Twilio\Options;
use Twilio\Values;
use Twilio\Version;
use Twilio\InstanceContext;
use Twilio\Serialize;


class HostedNumberOrderContext extends InstanceContext
    {
    /**
     * Initialize the HostedNumberOrderContext
     *
     * @param Version $version Version that contains the resource
     * @param string $sid A 34 character string that uniquely identifies this HostedNumberOrder.
     */
    public function __construct(
        Version $version,
        $sid
    ) {
        parent::__construct($version);

        // Path Solution
        $this->solution = [
        'sid' =>
            $sid,
        ];

        $this->uri = '/HostedNumberOrders/' . \rawurlencode($sid)
        .'';
    }

    /**
     * Delete the HostedNumberOrderInstance
     *
     * @return bool True if delete succeeds, false otherwise
     * @throws TwilioException When an HTTP error occurs.
     */
    public function delete(): bool
    {

        $headers = Values::of(['Content-Type' => 'application/x-www-form-urlencoded' ]);
        return $this->version->delete('DELETE', $this->uri, [], [], $headers);
    }


    /**
     * Fetch the HostedNumberOrderInstance
     *
     * @return HostedNumberOrderInstance Fetched HostedNumberOrderInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch(): HostedNumberOrderInstance
    {

        $headers = Values::of(['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json' ]);
        $payload = $this->version->fetch('GET', $this->uri, [], [], $headers);

        return new HostedNumberOrderInstance(
            $this->version,
            $payload,
            $this->solution['sid']
        );
    }


    /**
     * Update the HostedNumberOrderInstance
     *
     * @param array|Options $options Optional Arguments
     * @return HostedNumberOrderInstance Updated HostedNumberOrderInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function update(array $options = []): HostedNumberOrderInstance
    {

        $options = new Values($options);

        $data = Values::of([
            'FriendlyName' =>
                $options['friendlyName'],
            'UniqueName' =>
                $options['uniqueName'],
            'Email' =>
                $options['email'],
            'CcEmails' =>
                Serialize::map($options['ccEmails'], function ($e) { return $e; }),
            'Status' =>
                $options['status'],
            'VerificationCode' =>
                $options['verificationCode'],
            'VerificationType' =>
                $options['verificationType'],
            'VerificationDocumentSid' =>
                $options['verificationDocumentSid'],
            'Extension' =>
                $options['extension'],
            'CallDelay' =>
                $options['callDelay'],
        ]);

        $headers = Values::of(['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json' ]);
        $payload = $this->version->update('POST', $this->uri, [], $data, $headers);

        return new HostedNumberOrderInstance(
            $this->version,
            $payload,
            $this->solution['sid']
        );
    }


    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string
    {
        $context = [];
        foreach ($this->solution as $key => $value) {
            $context[] = "$key=$value";
        }
        return '[Twilio.Preview.HostedNumbers.HostedNumberOrderContext ' . \implode(' ', $context) . ']';
    }
}
