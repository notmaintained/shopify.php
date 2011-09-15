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
		return function ($method, $path, $params=array()) use ($shops_myshopify_domain, $shops_token)
		{
			$url = shopify_api_url_($shops_myshopify_domain, $shops_token, $path);

			if ('GET' == $method) $response = curl_request_($method, $url, $params);
			else $response = curl_request_('POST', $url, array(), stripslashes(json_encode($params)), 'application/json; charset=utf-8');
			if (!$response['error']) $response['body'] = json_decode($response['body'], true);
			return $response;
		};
	}

		function shopify_api_url_($shops_myshopify_domain, $shops_token, $path)
		{
			$username = SHOPIFY_APP_API_KEY;
			$password = md5(SHOPIFY_APP_SHARED_SECRET.$shops_token);
			return "https://$username:$password@$shops_myshopify_domain$path";
		}

		function curl_request_($method, $url='', $query_params=array(), $payload='', $content_type='')
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
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
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

			$response = array();

			if (curl_errno($ch))
			{
				$response['error'] = array('error_code'=>curl_errno($ch), 'error_message'=>curl_error($ch));
				curl_close($ch);
				return $response;
			}
			else
			{
				$response['error'] = false;
				curl_close($ch);
			}


			list($header_str, $response['body']) = preg_split("/\r\n\r\n|\n\n|\r\r/", $result, 2);
			$header_lines = preg_split("/\r\n|\n|\r/", $header_str);
			list($response['protocol'], $response['status_code'], $response['status_message']) = explode(' ', trim(array_shift($header_lines)), 3);

			$response['headers'] = array();
			foreach ($header_lines as $header_line)
			{
				list($name, $value) = explode(':', $header_line, 2);
				$name = strtolower($name);
				if (($name == 'set-cookie') and isset($headers[$name])) $response['headers'][$name] .= ',' . trim($value);
				else $response['headers'][$name] = trim($value);
			}

			return $response;
		}


	function shopify_curl_error($response)
	{
		return $response['error'];
	}


	function shopify_error($response)
	{
		if (is_array($response['body']) and isset($response['body']['error'])) return $response['body']['error'];
	}

?>