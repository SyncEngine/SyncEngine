<?php

namespace App\Webservice;

use App\Model\WebserviceModel;

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
					'pass_cred' => 'Password Credentials',
					'client_cred' => 'Client Credentials',
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
					'grant_type' => ['auth_code', 'auth_code_pkce', 'pass_cred', 'client_cred'],
				]
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
					'grant_type' => ['auth_code', 'auth_code_pkce', 'pass_cred', 'client_cred'],
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
		];
	}

	public function getAuthorizationCode($config)
	{
		$authorization_redirect_url = $config["auth_url"] . "?response_type=code&client_id=" . $config['username'] . "&redirect_uri=" . $config["callback_url"] . "&scope=openid";
		header("Location: " . $authorization_redirect_url);
	}

	public function getAccessToken($config, $authorization_code)
	{
		$authorization = base64_encode($config['username'] . ":" . $config['password']);
		$header = array("Authorization: Basic " . $authorization, "Content-Type: application/x-www-form-urlencoded");
		$content = "grant_type=" . $config['grant_type'] . "&code=$authorization_code&redirect_uri=" . $config["callback_url"];

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $config["access_token_url"],
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $content
		));
		$response = curl_exec($curl);
		curl_close($curl);

		if ($response === false) {
			echo "Failed";
			echo curl_error($curl);
			echo "Failed";
		} elseif (json_decode($response)->error) {
			echo "Error:<br />";
			echo $authorization_code;
			echo $response;
		}

		return json_decode($response)->access_token;
	}

	public function getResource($config, $access_token)
	{
		$header = array("Authorization: Bearer " . $access_token);

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $config['host'],
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true
		));
		$response = curl_exec($curl);
		curl_close($curl);

		return json_decode($response, true);
	}

	public function getRequestUrl(array $config): string
	{
		// TODO: Implement getRequestUrl() method.
	}

	public function retrieve(array $config)
	{
		if ($config["grant_type"] == 'auth_code') {
			//@ToDo authorization_code uit de config van connection halen
			$access_token = $this->getAccessToken($config, $config["authorization_code"]);
			$content = $this->getResource($config, $access_token);
		} elseif ($config["grant_type"] == "auth_code_pkce") {
			//@ToDo code uit de config van connection halen
			$access_token = $this->getAccessToken($config["code"]);
			$content = $this->getResource($access_token);
		} else {
			$this->getAuthorizationCode($config);
		}
		return $this->fromFormat($config['format'], $content);
	}

	public function send(array $config, $data)
	{
		// TODO: Implement send() method.
	}
}
