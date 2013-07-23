<?php
/**
  * Get a secret keys from stripe and fill in this content.
  * save the file to app/Config/stripe.php
  */
	$stripe = array(
		'TestSecret' => getenv('STRIPE_TEST_SECRET'),
		'PublicTestKey' => getenv('STRIPE_TEST_PUBLIC_KEY'),
		'LiveSecret' => getenv('STRIPE_LIVE_SECRET'),
		'PublicLiveKey' => getenv('STRIPE_LIVE_PUBLIC_KEY'),
		'mode' => 'Test',
		'currency' => 'usd'
	);

	Configure::write('Stripe', $stripe);
?>