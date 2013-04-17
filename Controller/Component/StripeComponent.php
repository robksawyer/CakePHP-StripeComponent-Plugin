<?php
/**
 * StripeComponent
 *
 * A component that handles payment processing using Stripe.
 *
 * PHP version 5
 *
 * @package		StripeComponent
 * @author		Gregory Gaskill <one@chronon.com>
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link		https://github.com/chronon/CakePHP-StripeComponent-Plugin
 */

App::uses('Component', 'Controller');
Configure::load('stripe'); //Load the config file
/**
 * StripeComponent
 *
 * @package		StripeComponent
 */
class StripeComponent extends Component {

/**
 * Default Stripe mode to use: Test or Live
 *
 * @var string
 * @access public
 */
	public $mode = 'Test';

/**
 * Default currency to use for the transaction
 *
 * @var string
 * @access public
 */
	public $currency = 'usd';

/**
 * Default mapping of fields to be returned: local_field => stripe_field
 *
 * @var array
 * @access public
 */
	public $fields = array('stripe_id' => 'id');

/**
 * Default mapping of fields to be returned: local_field => stripe_field
 *
 * @var array
 * @access public
 */
	public $customer_fields = array('stripe_customer_id' => 'id');

/**
 * Controller startup. Loads the Stripe API library and sets options from
 * APP/Config/bootstrap.php.
 *
 * @param Controller $controller Instantiating controller
 * @return void
 * @throws CakeException
 */
	public function startup(Controller $controller) {
		$this->Controller = $controller;

		// load the stripe vendor class
		App::import('Vendor', 'Stripe.Stripe', array(
			'file' => 'Stripe' . DS . 'lib' . DS . 'Stripe.php')
		);
		if (!class_exists('Stripe')) {
			throw new CakeException('Stripe API Library is missing or could not be loaded.');
		}

		// if mode is set in bootstrap.php, use it. otherwise, Test.
		$mode = Configure::read('Stripe.mode');
		if ($mode) {
			$this->mode = $mode;
		}

		// if currency is set in bootstrap.php, use it. otherwise, usd.
		$currency = Configure::read('Stripe.currency');
		if ($currency) {
			$this->currency = $currency;
		}

		// field map for charge response, or use default (set above)
		$fields = Configure::read('Stripe.fields');
		if ($fields) {
			$this->fields = $fields;
		}
	}

/**
 * The customer method handles creating a customer for the transaction
 * @param 
 * @return
 */
	public function customer($data){
		// set the Stripe API key
		$key = Configure::read('Stripe.' . $this->mode . 'Secret');
		if (!$key) {
			throw new CakeException('Stripe API key is not set.');
		}
		Stripe::setApiKey($key);

		if (!isset($data['email'])) {
			throw new CakeException('You didn not provide an email address.');
		}

		// set the (optional) description field to null if not set in $data
		if (!isset($data['description'])) {
			$data['description'] = null;
		}
		if (!isset($data['address_state'])) {
			$data['address_state'] = null;
		}
		if (!isset($data['address_country'])) {
			$data['address_country'] = null;
		}

		$data['number'] = preg_replace('/-/','',$data['number']);
		$data['number'] = preg_replace('/\s/','',$data['number']);

		
		$error = null;
		try {
			$customer = Stripe_Customer::create(array(
				'card' => $data['stripeToken'],
				'plan' => $data['plan'],
				'email' => $data['email'],
				'description' => $data['description'],
				//'address_state' => $data['address_state'],
				//'address_country' => $data['address_country'],
				//'name' => $data['name'],
				//'number' => $data['number'],
				//'exp_month' => intval($data['exp_month']),
				//'exp_year' => intval($data['exp_year']),
				//'cvc_check' => true
			));

		} catch(Stripe_CardError $e) {
			$body = $e->getJsonBody();
			$err  = $body['error'];
			CakeLog::error('Stripe: ' . $err['type'] . ': ' . $err['code'] . ': ' . $err['message'], 'stripe');
			$error = $err['message'];

		} catch (Stripe_InvalidRequestError $e) {
			$body = $e->getJsonBody();
			$err  = $body['error'];
			CakeLog::error('Stripe: ' . $err['type'] . ': ' . $err['message'], 'stripe');
			$error = $err['message'];

		} catch (Stripe_AuthenticationError $e) {
			CakeLog::error('Stripe: API key rejected!', 'stripe');
			$error = 'Payment processor API key error.';

		} catch (Stripe_Error $e) {
			CakeLog::error('Stripe: Stripe_Error - Stripe could be down.', 'stripe');
			$error = 'Customer creation error, try again later.';

		} catch (Exception $e) {
			CakeLog::error('Stripe: Unknown error.', 'stripe');
			$error = 'There was an error, try again later.';
		}

		if ($error !== null) {
			// an error is always a string
			return (string)$error;
		}

		CakeLog::info('Stripe: customer id ' . $customer->id, 'stripe');

		return $this->_formatCustomerResult($customer);
	}

/**
 * retrieve method
 * This method handles retrieving types of data from the api
 * @param string id The id to pull
 * @param string type The type of object to request
 * @return array
 */
	public function retrieve($id = null, $type = "customer"){
		// set the Stripe API key
		$key = Configure::read('Stripe.' . $this->mode . 'Secret');
		if (!$key) {
			throw new CakeException('Stripe API key is not set.');
		}
		Stripe::setApiKey($key);

		switch($type){
			case "customer":
				return Stripe_Customer::retrieve($id);
				break;

			default:
				return false;
				break;
		}
	}
	
/**
 * The charge method prepares data for Stripe_Charge::create and attempts a
 * transaction.
 *
 * @param array	$data Must contain 'amount' and 'stripeToken'.
 * @return array $charge if success, string $error if failure.
 * @throws CakeException
 * @throws CakeException
 */
	public function charge($data, $customer_id = null) {
		// set the Stripe API key
		$key = Configure::read('Stripe.' . $this->mode . 'Secret');
		if (!$key) {
			throw new CakeException('Stripe API key is not set.');
		}

		// $data MUST contain 'amount' and 'stripeToken' to make a charge.
		if (!isset($data['amount']) || !isset($data['stripeToken'])) {
			throw new CakeException('The required amount or stripeToken fields are missing.');
		}

		// set the (optional) description field to null if not set in $data
		if (!isset($data['description'])) {
			$data['description'] = null;
		}
		if (!isset($data['exp_month'])) {
			$data['exp_month'] = null;
		}
		if (!isset($data['exp_year'])) {
			$data['exp_year'] = null;
		}

		// format the amount, in cents.
		$data['amount'] = number_format($data['amount'], 2) * 100;
		$data['currency'] = $this->currency;

		Stripe::setApiKey($key);
		$error = null;
		try {
			if(!empty($customer_id)){
				$charge = Stripe_Charge::create(array(
					'amount' => $data['amount'],
					'currency' => $this->currency,
					'customer' => $customer_id,
					'description' => $data['description'],
					'exp_month' => $data['exp_month'],
					'exp_year' => $data['exp_year']
				));
			}else{
				$charge = Stripe_Charge::create(array(
					'amount' => $data['amount'],
					'currency' => $this->currency,
					'card' => $data['stripeToken'],
					'description' => $data['description'],
					'exp_month' => $data['exp_month'],
					'exp_year' => $data['exp_year']
				));
			}

		} catch(Stripe_CardError $e) {
			$body = $e->getJsonBody();
			$err  = $body['error'];
			CakeLog::error('Stripe: ' . $err['type'] . ': ' . $err['code'] . ': ' . $err['message'], 'stripe');
			$error = $err['message'];

		} catch (Stripe_InvalidRequestError $e) {
			$body = $e->getJsonBody();
			$err  = $body['error'];
			CakeLog::error('Stripe: ' . $err['type'] . ': ' . $err['message'], 'stripe');
			$error = $err['message'];

		} catch (Stripe_AuthenticationError $e) {
			CakeLog::error('Stripe: API key rejected!', 'stripe');
			$error = 'Payment processor API key error.';

		} catch (Stripe_Error $e) {
			CakeLog::error('Stripe: Stripe_Error - Stripe could be down.', 'stripe');
			$error = 'Payment processor error, try again later.';

		} catch (Exception $e) {
			CakeLog::error('Stripe: Unknown error.', 'stripe');
			$error = 'There was an error, try again later.';
		}

		if ($error !== null) {
			// an error is always a string
			return (string)$error;
		}

		CakeLog::info('Stripe: charge id ' . $charge->id, 'stripe');

		return $this->_formatResult($charge);
	}

/**
 * Returns an array of fields we want from Stripe's charge object
 *
 *
 * @param object $charge A successful charge object.
 * @return array The desired fields from the charge object as an array.
 */
	protected function _formatResult($charge) {
		$result = array();
		foreach ($this->fields as $local => $stripe) {
			if (is_array($stripe)) {
				foreach ($stripe as $obj => $field) {
					$result[$local] = $charge->$obj->$field;
				}
			} else {
				$result[$local] = $charge->$stripe;
			}
		}
		return $result;
	}

/**
 * Returns an array of fields we want from Stripe's charge object
 *
 *
 * @param object $customer A successful customer object.
 * @return array The desired fields from the customer object as an array.
 */
	protected function _formatCustomerResult($customer) {
		$result = array();
		foreach ($this->customer_fields as $local => $stripe) {
			if (is_array($stripe)) {
				foreach ($stripe as $obj => $field) {
					$result[$local] = $customer->$obj->$field;
				}
			} else {
				$result[$local] = $customer->$stripe;
			}
		}
		return $result;
	}

}