CakePHP Stripe Payment Processing Component
===========================================

This is a simple component that interfaces a CakePHP app with Stripe's PHP API library. Pass the
component an array containing at least an amount and a Stripe token id, it will attempt the charge
and return an array of the fields you want. 

Compatibility:
--------------

Tested with CakePHP 2.2.x and 2.3.x. The required Stripe PHP API library requires PHP 5 with cURL 
support.

Installation:
-------------

You will need the component (packaged as a plugin), and Stripe's PHP library (not included). The
Stripe library needs to be in this plugin's Vendor directory and must be named 'Stripe'. Using git, 
something like this:

	git clone git@github.com:chronon/CakePHP-StripeComponent-Plugin.git APP/Plugin/Stripe  
	git clone git://github.com/stripe/stripe-php.git APP/Plugin/Stripe/Vendor/Stripe

Configuration:
--------------

All configuration is in APP/Config/bootstrap.php.

**Required:** Load the plugin:
	
	CakePlugin::load('Stripe', array('bootstrap' => true, 'routes' => false));

**Required:** Edit the Config/stripe.php file and set (or use Environment variables) your Stripe secret API keys (both testing and live):
	
```
<?php
/**
  * Get a secret keys from stripe and fill in this content.
  * save the file to app/Config/stripe.php
  */
  $config = array(
  	'Stripe' => array(
  		'TestSecret' => getenv('STRIPE_TEST_SECRET'),
  		'PublicTestKey' => getenv('STRIPE_TEST_PUBLIC_KEY'),
  		'LiveSecret' => getenv('STRIPE_LIVE_SECRET'),
  		'PublicLiveKey' => getenv('STRIPE_LIVE_PUBLIC_KEY'),
  		'mode' => 'Test',
  		'currency' => 'usd'
  		)
  	);
?>
```

**Optional:** fields for the component to return mapped to => Stripe charge object response fields. 
Defaults to `'stripe_id' => 'id'`. See the Stripe API docs for [Stripe\_Charge::create()](https://stripe.com/docs/api?lang=php#create_charge) for available fields. For example:
	
	Configure::write('Stripe.fields', array(
		'stripe_id' => 'id',
		'stripe_last4' => array('card' => 'last4'),
		'stripe_address_zip_check' => array('card' => 'address_zip_check'),
		'stripe_cvc_check' => array('card' => 'cvc_check'),
		'stripe_amount' => 'amount'
	));

See Usage below if `Stripe.fields` is confusing.

**Optional:** add a logging config:

	CakeLog::config('stripe', array(
		'engine' => 'FileLog',
		'types' => array('info', 'error'),
		'scopes' => array('stripe'),
		'file' => 'stripe',
	));

Usage:
------

Make a payment form however you want, see the [Stripe docs](https://stripe.com/docs/tutorials/forms)
for sample code. Add the component to your controller:

	public $components = array(
		'Stripe.Stripe'
	);

Format your form data so you can send the component an array containing at least:

	$data = array(
		'amount' => '7.59',
		'stripeToken' => 'tok_0NAEASV7h0m7ny'
	);

Optionally you can include a `description` field as well, which according to Stripe docs is:

> An arbitrary string which you can attach to a charge object. It is displayed when in the web 
> interface alongside the charge. It's often a good idea to use an email address as a description 
> for tracking later.

For example:

	$data = array(
		'amount' => '7.59',
		'stripeToken' => 'tok_0NAEASV7h0m7ny',
		'description' => 'Casi Robot - casi@robot.com'
	);

**Attempt a charge:** `$result = $this->Stripe->charge($data);`

If the charge was successful, `$result` will be an **array** as described by the configuration value 
of `Stripe.fields`. If `Stripe.fields` is not set:

	$result = array(
		'stripe_id' => 'ch_0NXLLCydWzSIeE'
	);

If `Stripe.fields` is set, using the example described above in the Configuration section would 
give you:

	$result = array(
		'stripe_id' => 'ch_0NXLLCydWzSIeE',
		'stripe_last4' => '4242',
		'stripe_address_zip_check' => 'pass',
		'stripe_cvc_check' => 'pass',
		'stripe_amount' => 769
	);

If the charge was not successful, `$result` will be a **string** containing an error message, and 
log the error.
