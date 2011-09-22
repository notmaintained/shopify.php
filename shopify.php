<?php


	define('SHOPIFY_APP_API_KEY', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
	define('SHOPIFY_APP_SHARED_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
	define('SHOPIFY_APP_PASSWORD', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'); // for private apps


	function shopify_app_install_url($shop_domain)
	{
		return "http://$shop_domain/admin/api/auth?api_key=".SHOPIFY_APP_API_KEY;
	}


	function shopify_app_installed($shop, $t, $timestamp, $signature)
	{
		return (md5(SHOPIFY_APP_SHARED_SECRET."shop={$shop}t={$t}timestamp={$timestamp}") === $signature);
	}


	function shopify_api_client($shops_myshopify_domain, $shops_token, $private_app = FALSE)
	{
		return function ($method, $path, $params=array(), &$headers=array()) use ($shops_myshopify_domain, $shops_token, $private_app)
		{
			$url = shopify_api_url_($shops_myshopify_domain, $shops_token, $path, $private_app);

			switch ($method)
			{
				case 'GET':
				case 'DELETE':
					$response = curl_http_api_request_($method, $url, $params, '', '', $headers);
					break;
				case 'POST':
				case 'PUT':
					$response = curl_http_api_request_($method, $url, array(), stripslashes(json_encode($params)), 'application/json; charset=utf-8', $headers);
					break;
				default:
					throw new ShopifyInvalidMethodException($method);
			}

			$response = json_decode($response, true);

			if (isset($response['errors']) or ($headers['http_status_code'] >= 400))
				throw new ShopifyApiException(compact('method', 'path', 'params', 'headers', 'response', 'shops_myshopify_domain', 'shops_token'));

			return (is_array($response) and (count($response) > 0)) ? array_shift($response) : $response;
		};
	}

		function shopify_api_url_($shops_myshopify_domain, $shops_token, $path, $private_app)
		{
			$username = SHOPIFY_APP_API_KEY;

            if ($private_app) {
                $password = SHOPIFY_APP_PASSWORD;
            } else {
                $password = md5(SHOPIFY_APP_SHARED_SECRET.$shops_token);
            }

			$path = ltrim($path, '/');
			return "https://$username:$password@$shops_myshopify_domain/$path";
		}

		function curl_http_api_request_($method, $url, $query='', $payload='', $content_type='', &$headers=array())
		{
			$url = curl_append_query_($url, $query);
			$ch = curl_init($url);
			curl_setopts_($ch, $method, $payload, $content_type);
			$response = curl_exec($ch);
			$errno = curl_errno($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($errno) throw new ShopifyCurlException($error, $errno);

			list($message_headers, $message_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
			$headers = curl_parse_headers_($message_headers);

			return $message_body;
		}

			function curl_append_query_($url, $query)
			{
				if (empty($query)) return $url;
				if (is_array($query)) return "$url?".http_build_query($query);
				else return "$url?$query";
			}

			function curl_setopts_($ch, $method, $payload, $content_type)
			{
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_USERAGENT, 'HAC');
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);

				if ('GET' == $method)
				{
					curl_setopt($ch, CURLOPT_HTTPGET, true);
				}
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
			}

			function curl_parse_headers_($message_headers)
			{
				$header_lines = preg_split("/\r\n|\n|\r/", $message_headers);
				$headers = array();
				list(, $headers['http_status_code'], $headers['http_status_message']) = explode(' ', trim(array_shift($header_lines)), 3);
				foreach ($header_lines as $header_line)
				{
					list($name, $value) = explode(':', $header_line, 2);
					$name = strtolower($name);
					$headers[$name] = trim($value);
				}

				return $headers;
			}


	function shopify_calls_made($headers)
	{
		return shopify_shop_api_call_limit_param_(0, $headers);
	}

	function shopify_call_limit($headers)
	{
		return shopify_shop_api_call_limit_param_(1, $headers);
	}

	function shopify_calls_left($headers)
	{
		return shopify_call_limit($headers) - shopify_calls_made($headers);
	}

		function shopify_shop_api_call_limit_param_($index, $headers)
		{
			$params = explode('/', $headers['http_x_shopify_shop_api_call_limit']);
			return (int) $params[$index];
		}


	class ShopifyCurlException extends Exception { }
	class ShopifyInvalidMethodException extends Exception { }
	class ShopifyApiException extends Exception
	{
		protected $info;

		function __construct($info)
		{
			$this->info = $info;
			parent::__construct($info['headers']['http_status_message'], $info['headers']['http_status_code']);
		}

		function getInfo() { $this->info; }
	}

?>
