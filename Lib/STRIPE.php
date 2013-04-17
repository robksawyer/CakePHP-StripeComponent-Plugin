<?php
/**
  * Stripe.Api Library used to create an instanciated stripe API available to the user
  *
  * @author Rob Sawyer <robksawyer [at] gmail [dot] com>
  * @version 1.0.0
  * @link http://www.robksawyer.com
  * @license MIT
  */

App::uses('Stripe', 'Vendor.Stripe', array('file' => 'Stripe' . DS . 'lib' . DS . 'Stripe.php'));
App::uses('StripeInfo', 'Stripe.Lib');
App::uses('StripeApiException', 'Error');
class STRIPE {

  /**
    * Stripe Api
    */
  public static $Stripe = null;
  
  public function __construct() {
    if (empty(self::$Stripe)) {
			self::$Stripe = new Stripe(StripeInfo::getConfig());
		}
  }
  
  /**
    * Forward any call to the Stripe API
    * @param string method name
    * @param mixed params passed into method
    * @return mixed return value of result from Stripe API
    */
  public function __call($method, $params){
  	try {
  		return call_user_func_array(array(self::$Stripe, $method), $params);
  	} catch (StripeApiException $e) {
	    error_log($e);
	  }
  }
  
  /**
    * Retrieve the property of the Stripe API
    * @param string name of property
    * @return mixed property of Stripe API
    */
  public function __get($name){
    return self::$Stripe->$name;
  }
  
  /**
    * PHP 5.3.0 only
    * Usage: 
    * - FB::method(params);
    * Example:
    * - FB::getUser();
    */
  public static function __callstatic($method, $params){
  	try {
  		return call_user_func_array(array(self::$Stripe, $method), $params);
  	} catch (StripeApiException $e) {
	    error_log($e);
	  }
  }
}