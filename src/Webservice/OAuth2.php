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

	public function getAuthorizationCode($config)
	{
		$redirect = (isset($config["callback_url"])) ? "&redirect_uri=" . $config["callback_url"] : "";
		$custom_keys="";
		foreach ($config['auth_query'] as $custom)
		{
			$custom_keys .= "&".$custom['key']."=".$custom['value'];
		}
		$authorization_redirect_url = $config["auth_url"] . "?response_type=code&client_id=" . $config['key'] . "&scope=openid" . $redirect.$custom_keys;
		return $authorization_redirect_url;
	}

	public function getAccessToken($config, $authorization_code)
	{
		$redirect = (isset($config["callback_url"])) ? "&redirect_uri=" . $config["callback_url"] : "";
		$custom_keys="";
		foreach ($config['auth_query'] as $custom)
		{
			$custom_keys .= "&".$custom['key']."=".$custom['value'];
		}
		$authorization = base64_encode($config['key'] . ":" . $config['secret']);
		$header = array("Authorization: Basic " . $authorization, "Content-Type: application/x-www-form-urlencoded");
		$content = "grant_type=" . $config['grant_type'] . "&code=$authorization_code&redirect_uri=" . $redirect.$custom_keys;

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
		} elseif (json_decode($response)) {
			echo "Error:<br />";
			echo $authorization_code;
			echo $response;
		}

		var_dump($response);die();

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
		if (!isset($config["authorization_code"])) {
			$config["authorization_code"] = $this->getAuthorizationCode($config);
		}

		if ($config["grant_type"] == 'auth_code') {
			$access_token = $this->getAccessToken($config, $config["authorization_code"]);
			echo "Access token: <br>";
			var_dump($access_token);die;
			$content = $this->getResource($config, $access_token);
		} elseif ($config["grant_type"] == "auth_code_pkce") {
			//@ToDo code uit de config van connection halen
			$access_token = $this->getAccessToken($config["code"]);
			$content = $this->getResource($access_token);
		}
		return $this->fromFormat($config['format'], $content);
	}

	public function send(array $config, $data)
	{
		// TODO: Implement send() method.
	}
}
