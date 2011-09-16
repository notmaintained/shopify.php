<?php


	define('SHOPIFY_APP_API_KEY', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
	define('SHOPIFY_APP_SHARED_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');


	function shopify_app_install_url($shop_domain)
	{
		return "http://$shop_domain/admin/api/auth?api_key=".SHOPIFY_APP_API_KEY;
	}


	function shopify_validate_app_installation($shop, $t, $timestamp, $signature)
	{
		return (md5(SHOPIFY_APP_SHARED_SECRET."shop={$shop}t={$t}timestamp={$timestamp}") === $signature);
	}


	function shopify_api_client($shops_myshopify_domain, $shops_token)
	{
		return function ($method, $path, $params=array(), &$headers=array()) use ($shops_myshopify_domain, $shops_token)
		{
			$url = shopify_api_url_($shops_myshopify_domain, $shops_token, $path);

			switch ($method)
			{
				case 'GET':
				case 'DELETE':
					$response = curl_request_($method, $url, $params, '', '', $headers);
					break;
				case 'POST':
				case 'PUT':
					$response = curl_request_($method, $url, array(), stripslashes(json_encode($params)), 'application/json; charset=utf-8', $headers);
					break;
				default:
					throw new ShopifyInvalidMethodException($method);
			}

			$response = json_decode($response, true);
			if (isset($response['errors']) or ($headers['http_status_code'] >= 400)) throw new ShopifyApiException(array('headers'=>$headers, 'body'=>$response));
			return (is_array($response) and (count($response) > 0)) ? array_shift($response) : $response;
		};
	}

		function shopify_api_url_($shops_myshopify_domain, $shops_token, $path)
		{
			$username = SHOPIFY_APP_API_KEY;
			$password = md5(SHOPIFY_APP_SHARED_SECRET.$shops_token);
			return "https://$username:$password@$shops_myshopify_domain$path";
		}

		function curl_request_($method, $url='', $query_params=array(), $payload='', $content_type='', &$headers=array())
		{
			if (!empty($query_params)) $url .= is_array($query_params) ? '?' . http_build_query($query_params) : $query_params;

			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_USERAGENT, 'HAC');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);

			if ('GET' == $method) curl_setopt($ch, CURLOPT_HTTPGET, true);
			else
			{
				curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, $method);
				if (!empty($content_type)) curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: $content_type"));
				if (!empty($payload))
				{
					if (is_array($payload)) $payload = http_build_query($payload);
					curl_setopt ($ch, CURLOPT_POSTFIELDS, $payload);
				}
			}

			$result = curl_exec($ch);

			if (false === $result)
			{
				$errno = curl_errno($ch);
				$error = curl_error($ch);
				curl_close($ch);
				throw new ShopifyCurlException($error, $errno);
			}

			curl_close($ch);

			list($header_str, $response) = preg_split("/\r\n\r\n|\n\n|\r\r/", $result, 2);
			$header_lines = preg_split("/\r\n|\n|\r/", $header_str);
			$headers = array();
			list(, $headers['http_status_code'], $headers['http_status_message']) = explode(' ', trim(array_shift($header_lines)), 3);
			foreach ($header_lines as $header_line)
			{
				list($name, $value) = explode(':', $header_line, 2);
				$name = strtolower($name);
				if (($name == 'set-cookie') and isset($headers[$name])) $headers[$name] .= ',' . trim($value);
				else $headers[$name] = trim($value);
			}

			return $response;
		}

	class ShopifyCurlException extends Exception { }
	class ShopifyInvalidMethodException extends Exception { }
	class ShopifyApiException extends Exception
	{
		protected $response;

		function __construct($response)
		{
			$this->response = $response;
			 parent::__construct($response['headers']['http_status_message'], $response['headers']['http_status_code']);
		}

		function getResponse() { $this->response; }
	}


?>