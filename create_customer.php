<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logfile = new Logger('create_customer_log');
$logfile->pushHandler(new StreamHandler('customer_import.log', Logger::INFO));

$accessToken = 'Enter-access-token-here';
$shopUrl = 'https://dev-store.myshopify.com/admin/api/2021-07/graphql.json';

$client = new Client([
    'base_uri' => $shopUrl,
    'headers' => [
        'Content-Type' => 'application/json',
        'X-Shopify-Access-Token' => $accessToken,
    ],
]);
$query = <<<QUERY
    mutation customerCreate(\$input: CustomerInput!) {
        customerCreate(input: \$input) {
            userErrors {
                field
                message
            }
            customer {
                id
                email
                phone
                taxExempt
                acceptsMarketing
                firstName
                lastName
                addresses {
                    address1
                    city
					province
                    country
                    phone
                    zip
					lastName
					firstName
                }
            }
        }
    }
QUERY;


$variables = [
    'input' => [
        'email' => 'steve@example.com',
        'phone' => '+16465555559',
        'firstName' => 'Steve',
        'lastName' => 'Lastname',
        'acceptsMarketing' => true,
		"addresses" => [
			[
				"address1"=>"412 fake st",
				"city"=>"Ottawa",
				"province"=>"ON",
				"country"=>"CA",				
				"phone"=>"+16465555559", 
				"zip"=>"A1A 4A1", 
				"lastName"=>"Lastname", 
				"firstName"=>"Steve"				
			]
		],
    ]
];

$requestData = [
    'json' => [
        'query' => $query,
        'variables' => $variables,
    ],
];

$response = $client->request('POST', '', $requestData);

$body = $response->getBody();
$data = json_decode($body, true);
$email = $variables['input']['email'];
if (isset($data['errors'])) {
	
	$error_message = "";
    $errors = $data['errors'];
	foreach($errors as $error){
		$error_message .= $error['message'];
	}
	$logfile->error("error for  $email: $error_message \n");
	
} else {
	
	$field_errors = $data['data']['customerCreate']['userErrors'];
	if(count($field_errors)>0){
		$field_error_message = "";		
		foreach($field_errors as $error){
			$field_error_message .= $error['message'];
		}
		$logfile->error("error for  $email: $field_error_message \n");
		echo "<div style='color:red;'>error for  $email: ".$field_error_message. "</div>\n";
	}else{
		
		$id = $data['data']['customerCreate']['customer']['id'];
		$logfile->info("Customer created successfully $email: $id");
		echo $success_message = "<div style='color:green;'>Customer created: ".$id. "</div>\n";
		
	}	
	
}
