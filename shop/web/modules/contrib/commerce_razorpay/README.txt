CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
This module serve as Payment Gateway provided by Razorpay.

REQUIREMENTS
------------

This module requires the following modules:

 * Commerce (The base setup to enable functionalities like Add to Cart, Payment Gateways, Product price, order, checkout etc.)

💾 INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.
 * Composer - 'drupal/commerce_razorpay:^1.0'

CONFIGURATION
-------------

I) Using Composer
	1. In drupal root composer.json, in merge-plugin include modules/*/composer.json OR modules/custom/*/composer.json.
	2. Then run composer update from docroot to include razorpay library in the
docroot.

1.2 Install the module.

2. Go to payment setting under store
	2.1. Enter the required keys provided to you by Razorpay.
	2.2. For Drupal Vanilla instance with commerce modules,
	you can also configure razorpay via select Razorpay as one of the payment methods.
	  - Go to admin/commerce/config/payment-gateways/add
	  - Select Razorpay
	  - Add Key Id and Key secret.
	  - Name and Machine name of payment gateway can be added as per choice as done for other payement gateways. Eg: Name = Razorpay, Machine name = razorpay.
3. Select Test or Production based on your use.
4. Razorpay only supports Indian Rupee as the currency.

## Step to setup keys on razorpay site.
	- Go to https://razorpay.com/
	- Register or sign in with an account.
	- Get Peronsalized account -> APIKeys -> key ID under APIKeys (https://dashboard.razorpay.com/app/keys)
	- If you don't remember the Key secret, then regenerate the keys and keysecret is displayed immediately on the razorpay site and set it as the secret key for payment gateway on the drupal site.

MAINTAINERS
-----------

Current maintainers:
 * Jyoti Bohra (nehajyoti).
