<?php

/**
 * This code was generated by
 * ___ _ _ _ _ _    _ ____    ____ ____ _    ____ ____ _  _ ____ ____ ____ ___ __   __
 *  |  | | | | |    | |  | __ |  | |__| | __ | __ |___ |\ | |___ |__/ |__|  | |  | |__/
 *  |  |_|_| | |___ | |__|    |__| |  | |    |__] |___ | \| |___ |  \ |  |  | |__| |  \
 *
 * Twilio - Conversations
 * This is the public Twilio REST API.
 *
 * NOTE: This class is auto generated by OpenAPI Generator.
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace Twilio\Rest\Conversations\V1\Service\Conversation;

use Twilio\Exceptions\TwilioException;
use Twilio\ListResource;
use Twilio\Options;
use Twilio\Stream;
use Twilio\Values;
use Twilio\Version;
use Twilio\Serialize;


class MessageList extends ListResource
    {
    /**
     * Construct the MessageList
     *
     * @param Version $version Version that contains the resource
     * @param string $chatServiceSid The SID of the [Conversation Service](https://www.twilio.com/docs/conversations/api/service-resource) the Participant resource is associated with.
     * @param string $conversationSid The unique ID of the [Conversation](https://www.twilio.com/docs/conversations/api/conversation-resource) for this message.
     */
    public function __construct(
        Version $version,
        string $chatServiceSid,
        string $conversationSid
    ) {
        parent::__construct($version);

        // Path Solution
        $this->solution = [
        'chatServiceSid' =>
            $chatServiceSid,
        
        'conversationSid' =>
            $conversationSid,
        
        ];

        $this->uri = '/Services/' . \rawurlencode($chatServiceSid)
        .'/Conversations/' . \rawurlencode($conversationSid)
        .'/Messages';
    }

    /**
     * Create the MessageInstance
     *
     * @param array|Options $options Optional Arguments
     * @return MessageInstance Created MessageInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function create(array $options = []): MessageInstance
    {

        $options = new Values($options);

        $data = Values::of([
            'Author' =>
                $options['author'],
            'Body' =>
                $options['body'],
            'DateCreated' =>
                Serialize::iso8601DateTime($options['dateCreated']),
            'DateUpdated' =>
                Serialize::iso8601DateTime($options['dateUpdated']),
            'Attributes' =>
                $options['attributes'],
            'MediaSid' =>
                $options['mediaSid'],
            'ContentSid' =>
                $options['contentSid'],
            'ContentVariables' =>
                $options['contentVariables'],
            'Subject' =>
                $options['subject'],
        ]);

        $headers = Values::of(['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json' , 'X-Twilio-Webhook-Enabled' => $options['xTwilioWebhookEnabled']]);
        $payload = $this->version->create('POST', $this->uri, [], $data, $headers);

        return new MessageInstance(
            $this->version,
            $payload,
            $this->solution['chatServiceSid'],
            $this->solution['conversationSid']
        );
    }


    /**
     * Reads MessageInstance records from the API as a list.
     * Unlike stream(), this operation is eager and will load `limit` records into
     * memory before returning.
     *
     * @param array|Options $options Optional Arguments
     * @param int $limit Upper limit for the number of records to return. read()
     *                   guarantees to never return more than limit.  Default is no
     *                   limit
     * @param mixed $pageSize Number of records to fetch per request, when not set
     *                        will use the default value of 50 records.  If no
     *                        page_size is defined but a limit is defined, read()
     *                        will attempt to read the limit with the most
     *                        efficient page size, i.e. min(limit, 1000)
     * @return MessageInstance[] Array of results
     */
    public function read(array $options = [], int $limit = null, $pageSize = null): array
    {
        return \iterator_to_array($this->stream($options, $limit, $pageSize), false);
    }

    /**
     * Streams MessageInstance records from the API as a generator stream.
     * This operation lazily loads records as efficiently as possible until the
     * limit
     * is reached.
     * The results are returned as a generator, so this operation is memory
     * efficient.
     *
     * @param array|Options $options Optional Arguments
     * @param int $limit Upper limit for the number of records to return. stream()
     *                   guarantees to never return more than limit.  Default is no
     *                   limit
     * @param mixed $pageSize Number of records to fetch per request, when not set
     *                        will use the default value of 50 records.  If no
     *                        page_size is defined but a limit is defined, stream()
     *                        will attempt to read the limit with the most
     *                        efficient page size, i.e. min(limit, 1000)
     * @return Stream stream of results
     */
    public function stream(array $options = [], int $limit = null, $pageSize = null): Stream
    {
        $limits = $this->version->readLimits($limit, $pageSize);

        $page = $this->page($options, $limits['pageSize']);

        return $this->version->stream($page, $limits['limit'], $limits['pageLimit']);
    }

    /**
     * Retrieve a single page of MessageInstance records from the API.
     * Request is executed immediately
     *
     * @param mixed $pageSize Number of records to return, defaults to 50
     * @param string $pageToken PageToken provided by the API
     * @param mixed $pageNumber Page Number, this value is simply for client state
     * @return MessagePage Page of MessageInstance
     */
    public function page(
        array $options = [],
        $pageSize = Values::NONE,
        string $pageToken = Values::NONE,
        $pageNumber = Values::NONE
    ): MessagePage
    {
        $options = new Values($options);

        $params = Values::of([
            'Order' =>
                $options['order'],
            'PageToken' => $pageToken,
            'Page' => $pageNumber,
            'PageSize' => $pageSize,
        ]);

        $headers = Values::of(['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json']);
        $response = $this->version->page('GET', $this->uri, $params, [], $headers);

        return new MessagePage($this->version, $response, $this->solution);
    }

    /**
     * Retrieve a specific page of MessageInstance records from the API.
     * Request is executed immediately
     *
     * @param string $targetUrl API-generated URL for the requested results page
     * @return MessagePage Page of MessageInstance
     */
    public function getPage(string $targetUrl): MessagePage
    {
        $response = $this->version->getDomain()->getClient()->request(
            'GET',
            $targetUrl
        );

        return new MessagePage($this->version, $response, $this->solution);
    }


    /**
     * Constructs a MessageContext
     *
     * @param string $sid A 34 character string that uniquely identifies this resource.
     */
    public function getContext(
        string $sid
        
    ): MessageContext
    {
        return new MessageContext(
            $this->version,
            $this->solution['chatServiceSid'],
            $this->solution['conversationSid'],
            $sid
        );
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string
    {
        return '[Twilio.Conversations.V1.MessageList]';
    }
}
