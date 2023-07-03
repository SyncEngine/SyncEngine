<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class OAuth2 extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'oauth2';
		$this->name = 'OAuth 2.0';
		$this->description = 'Connect with OAuth 2.0 authorization';
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
				'help' => 'Final url for the actually call with correct auth'
			],
			'header_prefix' => [
				'label' => 'Header Prefix',
				'placeholder' => 'Bearer',
				'type' => 'text',
			],
			'grant_type' => [
				'label' => 'Grant type',
				'type' => 'select',
				'choices' => [
					'auth_code' => 'Authorization Code',
					'auth_code_pkce' => 'Authorization Code with PKCE',
					'implicit' => 'Implicit',
					'password_credentials' => 'Password Credentials',
					'refresh_token' => 'Refresh Token',
					'client_credentials' => 'Client Credentials',
				]
			],
			'callback_url' => [
				'label' => 'Callback/Redirect URL',
				'type' => 'text',
				'help' => 'This is the callback URL that you will be redirected to, after your application is authorized. It is used to extract the authorization code or access token. The Callback URL should match the one your use during the application registration process',
				'conditionals' => [
					'grant_type' => ['auth_code', 'auth_code_pkce', 'implicit'],
				]
			],
			'auth_url' => [
				'label' => 'Authorization URL',
				'type' => 'text',
				'help' => 'The endpoint for the authorization server. This is used to get the authorization code.',
				'conditionals' => [
					'grant_type' => ['auth_code', 'auth_code_pkce', 'implicit'],
				]
			],
			'access_token_url' => [
				'label' => 'Access Token URL',
				'type' => 'text',
				'help' => 'The endpoint for the authentication server. This is used to exchange the authorization code for an access token.',
				'conditionals' => [
					'grant_type' => ['auth_code', 'auth_code_pkce', 'pass_cred', 'client_credentials'],
				]
			],
			'refresh_token' => [
				'label' => 'Refresh token',
				'type' => 'text',
				'help' => 'Token used to refresh a token'
			],
			'key' => [
				'label' => 'Client Key (ID)',
				'help' => 'The client identifier issued to the client during the Application registration process.',
				'type' => 'text',
			],
			'secret' => [
				'label' => 'Client Secret',
				'type' => 'text',
				'help' => 'The client secret issued to the client during the Application registration process.',
				'conditionals' => [
					'grant_type' => ['auth_code', 'auth_code_pkce', 'pass_cred', 'client_credentials'],
				]
			],
			'username' => [
				'label' => 'Username',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => 'pass_cred',
				]
			],
			'password' => [
				'label' => 'Password',
				'type' => 'password',
				'conditionals' => [
					'grant_type' => 'pass_cred',
				]
			],
			'code' => [
				'label' => 'Auth code',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => 'auth_code',
				]
			],
			'scope' => [
				'label' => 'Scope',
				'type' => 'text',
				'placeholder' => 'e.g. read:org',
				'help' => 'The scope of the access request. It may have multiple space-delimited values.',
			],
			'state' => [
				'label' => 'State',
				'type' => 'text',
				'help' => 'An opaque value that is used for preventing cross-site request forgery.',
				'conditionals' => [
					'grant_type' => ['auth_code', 'auth_code_pkce', 'implicit'],
				]
			],
			'client_authentication' => [
				'label' => 'Client Authentication',
				'type' => 'select',
				//'help' => 'The client authentication method will be ignored if you have added a client_id or client_secret in the advanced options.',
				'choices' => [
					'basic_auth' => 'Send as Basic Auth Header',
					'body_cred' => 'Send client credentials in body',
				]
			],
			// Advanced.
			'refresh_token_url' => [
				'label' => 'Refresh Token URL',
				'type' => 'text',
				'help' => 'Used to exchange the refresh token for and access token. If empty, the Access Token URL will be used instead.',
			],
			'auth_query' =>[
				'label' => 'Auth Query',
				'type' => 'params',
			],
			'token_query' =>[
				'label' => 'Token Query',
				'type' => 'params',
			],
			'refresh_query' => [
				'label' => 'Refresh Query',
				'type' => 'params',
			]
		];
	}

	public function getRequestUrl(array $config): string
	{
		// TODO: Implement getRequestUrl() method.
	}

	private function extractTokens(ResponseInterface $response): Tokens
	{
		try {
			$responseBody = $response->getContent();
		} catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
			throw new \Exception('Error with calling token endpoint.', 0, $e);
		}

		try {
			$token = json_decode($responseBody, true, 512, \JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			throw new \Exception(
				'Error parsing token endpoint JSON response. JSON error is: ' . $e->getMessage(),
				0,
				$e,
			);
		}

		if ($token === null && $responseBody !== 'null') {
			throw new \Exception('Error parsing token endpoint JSON response.');
		}

		if (!is_array($token) || !array_key_exists('access_token', $token) || !is_string($token['access_token'])) {
			throw new \Exception('Access token not found in token endpoint response.');
		}

		return [$token['access_token'], isset($token['refresh_token']) && is_string($token['refresh_token']) ? $token['refresh_token'] : null];
	}

	public function refresh_token_getTokens(array $config)
	{
		$parameters = [
			'grant_type' => $config["grant_type"],
			'client_id' => $config["key"],
			'client_secret' => $config["secret"],
			'refresh_token' => $config["refresh_token"],
		];
		$parameters = $this->addParams($parameters, $config, "token_query");

		$client = $this->getClient();
		$response = $client->request('POST', $config["access_token_url"], [
			'body' => http_build_query($parameters),
		]);

		return $this->extractTokens($response);
	}

	public function client_credentials_getTokens($config)
	{
		$parameters = [
			'grant_type' => $config["grant_type"],
		];
		$parameters = $this->addParams($parameters, $config, "token_query");

		$client = $this->getClient();
		$response = $client->request('POST', $config["access_token_url"], [
			'headers' => ['Authorization' => sprintf('Basic %s', base64_encode("{$config["key"]}:{$config["secret"]}"))],
			'body' => http_build_query($parameters),
		]);

		return $this->extractTokens($response);
	}

	public function password_credentials_getTokens($config)
	{
		$parameters = [
			'grant_type' => $config["grant_type"],
			'username' => $config["username"],
			'password' => $config["password"],
		];
		$parameters = $this->addParams($parameters, $config, "token_query");

		if ($config["key"] !== null && $config["secret"] !== null) {
			$parameters['client_id'] = $config["key"];
			$parameters['client_secret'] = $config["secret"];
		}

		$client = $this->getClient();
		$response = $client->request('POST', $config["access_token_url"], [
			'body' => http_build_query($parameters),
		]);

		return $this->extractTokens($response);
	}

	public function auth_code_getTokens($config)
	{
		$parameters = [
			'grant_type' => $config["grant_type"],
			'client_id' => $config["key"],
			'client_secret' => $config["secret"],
			'code' => $config["code"],
		];
		$parameters = $this->addParams($parameters, $config, "token_query");
		$client = $this->getClient();
		$response = $client->request('POST', $config["access_token_url"], [
			'body' => http_build_query($parameters),
		]);

		return $this->extractTokens($response);
	}

	public function addParams($parameters, $config, $query)
	{
		if(isset($config[$query]))
		{
			foreach ($config['$query'] as $tokenQuery){
				$parameters[$tokenQuery['key']] = $tokenQuery['value'];
			}
		}
		return $parameters;
	}

	public function retrieve(array $config)
	{
		switch ($config["grant_type"])
		{
			case "client_credentials":
				$tokens = $this->client_credentials_getTokens($config);
			case "refresh_token":
				$tokens = $this->refresh_token_getTokens($config);
			case "password_credentials":
				$tokens = $this->password_credentials_getTokens($config);
			case "auth_code":
				$tokens = $this->auth_code_getTokens($config);
		}
		//@toDo use the tokens to actually send a API call
	}

	public function send(array $config, $data)
	{
		// TODO: Implement send() method.
	}
}
