<?php
/**
  * Stripe.Api Library used to create an instanciated stripe API available to the user
  *
  * @author Rob Sawyer <robksawyer [at] gmail [dot] com>
  * @version 1.0
  * @link http://www.robksawyer.com
  * @license MIT
  */
//App::uses('Stripe.Stripe/src/stripe', 'Vendor');
App::uses('Stripe', 'Vendor.Stripe', array('file' => 'Stripe' . DS . 'lib' . DS . 'Stripe.php'));
Configure::load('stripe');
class StripeApi {

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
    * Stripe Api
    */
  public static $Stripe = null;

  /**
    * Forward any call to the Stripe API
    * @param string method name
    * @param mixed params passed into method
    * @return mixed return value of result from Stripe API
    */
  public function __call($method, $params){
    self::buildStripe();
    return call_user_func_array(array(self::$Stripe, $method), $params);
  }

  /**
    * Retrieve the property of the stripeApi
    * @param string name of property
    * @return mixed property of stripeApi
    */
  public function __get($name){
    self::buildStripe();
    return self::$Stripe->$name;
  }

  /**
    * PHP 5.3.0 only
    * Usage:
    * - StripeApi::method(params);
    * Example:
    * - StripeApi::get_loggedin_user();
    * - StripeApi::require_login('myaccount');
    */
  public static function __callstatic($method, $params){
    self::buildStripe();
    return call_user_func_array(array(self::$Stripe, $method), $params);
  }

  /**
    * Builds the stripe API if we need it
    */
  public static function buildStripe(){
    if(!self::$Stripe){
      self::$Stripe = new Stripe();

      // load the stripe vendor class
      /*App::import('Vendor', 'Stripe.Stripe', array(
        'file' => 'Stripe' . DS . 'lib' . DS . 'Stripe.php')
      );*/
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

      //Set the default keys
      // set the Stripe API key
      $key = Configure::read('Stripe.' . $this->mode . 'Secret');

      if (!$key) {
        throw new CakeException('Stripe API key is not set.');
      }

      Stripe::setApiKey($key); //Set the key

    }
  }
}
?>