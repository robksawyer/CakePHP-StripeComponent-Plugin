<?php
/**
  * Facebook.Api component used to create an instanciated facebook API available to the user
  *
  * @author Nick Baker <nick [at] webtechnick [dot] com>
  * @version 1.1
  * @link http://www.webtechnick.com
  * @license MIT
  */
App::uses('StripeApi', 'Stripe.Lib');
class ApiComponent extends Object {
  
  /**
    * Allow direct access to the stripe API
    * @link https://stripe.com/docs
    * @access public
    */
  public $STRIPE = null;
  
  /**
    * Load the API into a class property and allow access to it.
    */
  public function initialize($controller){
    $this->STRIPE = new StripeApi();
  }
  
  
}
?>