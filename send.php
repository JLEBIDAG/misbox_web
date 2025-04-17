<?php

use Infobip\Configuration;
use Infobip\Api\SmsApi;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;
use Infobip\Model\SmsAdvancedTextualRequest;
use Twilio\Rest\Client;

require __DIR__ . "/vendor/autoload.php";

$number = $_POST["number"];
$message = $_POST["message"];

if ($_POST["provider"] === "infobip") {

    $base_url = "https://wgmnzq.api.infobip.com";
    $api_key = "b90250528c629c0d00a879a43eb53c9c-90943385-4077-4aab-8f4a-08ed3192f862";

    $configuration = new Configuration(host: $base_url, apiKey: $api_key);

    $api = new SmsApi(config: $configuration);

    $destination = new SmsDestination(to: $number);

    $message = new SmsTextualMessage(
        destinations: [$destination],
        text: $message,
        from: "JIAN"
    );

    $request = new SmsAdvancedTextualRequest(messages: [$message]);

    $response = $api->sendSmsMessage($request);

} else {   // Twilio

    $account_id = "your account SID";
    $auth_token = "your auth token";

    $client = new Client($account_id, $auth_token);

    $twilio_number = "+ your outgoing Twilio phone number";

    $client->messages->create(
        $number,
        [
            "from" => $twilio_number,
            "body" => $message
        ]
    );

}

echo "Message sent.";