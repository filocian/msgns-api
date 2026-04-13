<?php

declare(strict_types=1);

return [

	/*
	|--------------------------------------------------------------------------
	| Third Party Services
	|--------------------------------------------------------------------------
	|
	| This file is for storing the credentials for third party services such
	| as Mailgun, Postmark, AWS and more. This file provides the de facto
	| location for this type of information, allowing packages to have
	| a conventional file to locate the various service credentials.
	|
	*/

	'postmark' => [
		'token' => env('POSTMARK_TOKEN'),
	],

	'ses' => [
		'key' => env('AWS_ACCESS_KEY_ID'),
		'secret' => env('AWS_SECRET_ACCESS_KEY'),
		'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
	],

	'slack' => [
		'notifications' => [
			'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
			'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
		],
	],
	'dynamodb' => [
		'key' => env('AWS_DYNAMODB_ACCESS_KEY_ID'),
		'secret' => env('AWS_DYNAMODB_SECRET_ACCESS_KEY'),
		'region' => env('AWS_DEFAULT_REGION', 'eu-west-3'),
		'product_usage_table' => env('AWS_DYNAMODB_PRODUCT_USAGE_TABLE'),
		'fancelet_comments_table' => env('AWS_DYNAMODB_FANCELET_COMMENTS_TABLE'),
		'product_config_history_table' => env('AWS_DYNAMODB_PRODUCT_CONFIG_HISTORY_TABLE'),
	],

	'mixpanel' => [
		'token' => env('MIXPANEL_TOKEN', ''),
		'source' => env('MIXPANEL_SOURCE', 'API'),
		'system_alias' => env('MIXPANEL_SYSTEM_ALIAS', 'SYS@API'),
	],

	'google' => [
		'places_api_key'        => env('GOOGLE_PLACES_API_KEY'),
		'client_id'             => env('GOOGLE_CLIENT_ID'),              // also used by GoogleOAuthAdapter (Identity)
		'client_secret'         => env('GOOGLE_CLIENT_SECRET'),          // new — BE-11a
		'business_redirect_uri' => env('GOOGLE_BUSINESS_REDIRECT_URI'),  // new — BE-11a
	],

	'gemini' => [
		'api_key'                   => env('GEMINI_API_KEY'),
		'model'                     => env('GEMINI_MODEL', 'gemini-2.0-flash'),
		'timeout_seconds'           => (int) env('GEMINI_TIMEOUT_SECONDS', 30),
		'rate_limit_per_minute'     => (int) env('AI_RATE_LIMIT_PER_MINUTE', 2),
		'rate_limit_window_seconds' => (int) env('AI_RATE_LIMIT_WINDOW_SECONDS', 60),
	],

	'products' => [
		'front_url' => env('FRONT_URL', 'https://app.msgns.local'),
		'v2_front_url' => env('FRONT_V2_URL', 'https://app.msgns.local'),
		'default_password_length' => (int) env('DEFAULT_PRODUCT_PASSWORD_LENGTH', 12),
	],
];
