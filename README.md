# shopify.php

Lightweight PHP (JSON) client for the [Shopify API](http://api.shopify.com/).


## Getting Started

### Download
Download the [latest version of shopify.php](https://github.com/sandeepshetty/shopify.php/archives/master):

```shell
$ curl -L http://github.com/sandeepshetty/shopify.php/tarball/master | tar xvz
$ mv shopify.php-shopify.php-* shopify.php
```

### Configure
For regular applications, change the values of the constants `SHOPIFY_APP_API_KEY`, `SHOPIFY_APP_SECRET` and `SHOPIFY_PRIVATE_APP` to your app's **API Key**, **Shared Secret** and **false** respectively.

For [private applications](http://wiki.shopify.com/Private_applications), change the values of the constants `SHOPIFY_APP_API_KEY`, `SHOPIFY_APP_SECRET` and `SHOPIFY_PRIVATE_APP` to your private app's **API Key**, **Password** and **true** respectively.

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

	$url = shopify_app_install_url($shop_domain);

?>
```

Validate the installation when Shopify redirects the shop owner to your app's **Return URL** after installation:

```php
<?php

	if (!shopify_app_installed($_GET['shop'], $_GET['t'], $_GET['timestamp'], $_GET['signature']))
	{
		// Guard Clause
	}

?>
```

Making API calls:

```php
<?php

	$shopify = shopify_api_client($shops_myshopify_domain, $shops_token);


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
			$recurring_application_charge = $shopify('POST', '/admin/recurring_application_charges.json', $charge, $headers);

			// API call limit helpers
			echo shopify_calls_made($headers); // 2
			echo shopify_calls_left($headers); // 298
			echo shopify_call_limit($headers); // 300

		}
		catch (ShopifyApiException $e)
		{
			// If you're here, either $headers['http_status_code']) != 201 or isset($response['errors'])
		}

	}
	catch (ShopifyApiException $e)
	{
		/* $e->getInfo() will return an array with keys:
			* method
			* path
			* params (third parameter passed to $shopify)
			* headers
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
