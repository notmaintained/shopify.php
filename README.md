
**NOTE**: This only works with legacy authentication. For OAuth2 use [shopify_api](https://github.com/sandeepshetty/shopify_api).


# shopify.php

Lightweight multi-paradigm PHP (JSON) client for the [Shopify API](http://api.shopify.com/).


## Requirements

* PHP 5.3 with [cURL support](http://php.net/manual/en/book.curl.php).


## Getting Started

### Download
Download the [latest version of shopify.php](https://github.com/sandeepshetty/shopify.php/archives/master):

```shell
$ curl -L http://github.com/sandeepshetty/shopify.php/tarball/master | tar xvz
$ mv sandeepshetty-shopify.php-* shopify.php
```

### Require

```php
<?php

	require 'path/to/shopify.php/shopify.php';

?>
```

### Usage
Generating the app's installation URL for a given store:

```php
<?php

	$url = shopify_app_install_url($shop_domain, $api_key);

?>
```

Validate the installation when Shopify redirects the shop owner to your app's **Return URL** after installation:

```php
<?php

	if (!shopify_is_app_installed($_GET['shop'], $_GET['t'], $_GET['timestamp'], $_GET['signature'], $shared_secret))
	{
		// Guard Clause
	}

?>
```

Making API calls:

```php
<?php

	// For regular apps:
	$shopify = shopify_api_client($shops_myshopify_domain, $shops_token, $api_key, $shared_secret);

	// For private apps:
	// $shopify = shopify_api_client($shops_myshopify_domain, NULL, $api_key, $password, true);

	try
	{
		// Get all products
		$products = $shopify('GET', '/admin/products.json', array('published_status'=>'published'));


		// Create a new recurring charge
		$charge = array
		(
			"recurring_application_charge"=>array
			(
				"price"=>10.0,
				"name"=>"Super Duper Plan",
				"return_url"=>"http://super-duper.shopifyapps.com",
				"test"=>true
			)
		);

		try
		{
			// All requests accept an optional fourth parameter, that is populated with the response headers.
			$recurring_application_charge = $shopify('POST', '/admin/recurring_application_charges.json', $charge, $response_headers);

			// API call limit helpers
			echo shopify_calls_made($response_headers); // 2
			echo shopify_calls_left($response_headers); // 298
			echo shopify_call_limit($response_headers); // 300

		}
		catch (ShopifyApiException $e)
		{
			// If you're here, either HTTP status code was >= 400 or response contained the key 'errors'
		}

	}
	catch (ShopifyApiException $e)
	{
		/* $e->getInfo() will return an array with keys:
			* method
			* path
			* params (third parameter passed to $shopify)
			* response_headers
			* response
			* shops_myshopify_domain
			* shops_token
		*/
	}
	catch (ShopifyCurlException $e)
	{
		// $e->getMessage() returns value of curl_errno() and $e->getCode() returns value of curl_ error()
	}
?>
```
