<?php
/**
 * The shipment class creates a shipment object.  Each shipment object will have one or more package objects.
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 04/15/2013 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
namespace Awsp\Ship;

class Shipment {
 
    /**
     *
     * @var array holding package objects
     */
    protected $packages = array();
    
    /**
     *
     * @var array holding data specific to the shipment
     */
    protected $shipment_data = array();
    
  
    /**
     * Constructor sets the object properties, sanitizes input and makes sure all required fields are set
     * 
     * @param array $shipment_data the shipment data
     * @version 04/19/2013
     * @since 12/02/2012
     */
    public function __construct(array $shipment_data = array()) {
        // set object properties
        if(!is_array($shipment_data) || empty($shipment_data)) {
            throw new \Awsp\FW\InvalidArgumentException('Shipment Data array is empty.');
        }
        $this->shipment_data = $shipment_data;
        // sanitize $shipment_data values
        $this->sanitizeInput();
        // make sure that all required fields in $shipment_data are set
        $this->isShipmentValid();        
    }
    
    
    /**
     * Adds a Package object to the class' $packages array
     * 
     * @version updated 12/04/2012
     * @since 12/02/2012
     * @param object \Awsp\Ship\Package $package
     */
    public function addPackage(Package $package) {
        // add this package to the shipment's array
        $this->packages[] = $package;
    }
    
    
    /**
     * Returns the specified property of the object's shipment_data array.
     * 
     * @version updated 01/01/2013
     * @since 12/08/2012
     * @param string $field the field of the desired property within the shipment_data array
     * @return mixed the value found for the specified field of the shipment_data array
     */
    public function get($field) {
        // as long as the field is set, return its value
        if(isset($this->shipment_data[$field])) {
            return $this->shipment_data[$field];
        }
    }
    
    
    /**
     * Returns the array containing the package(s) object(s) or throwns an exception if there are none.
     * 
     * @version updated 01/01/2013
     * @since 12/08/2012
     * @return array containing all package object(s) that belong to this Shipment
     * @throws \UnexpectedValueException if the packages array is empty
     */
    public function getPackages() {
        // make sure that field of the array is set and throw an exception if it is not
        if(empty($this->packages)) {
            throw new \UnexpectedValueException('There is no data in the packages array.');
        }
        // as long as the field is set, return its value
        return $this->packages;
    }
    
    
    /**
     * Goes through each element of $this->shipment_data and applys some basic filtering to it.  The elements of 
     *  $this->shipment_data are updated with the filtered results.
     * 
     * @todo this is basic filtering - add additional filtering as necessary for your application
     * @return void
     * @version 01/14/2013
     * @since 01/14/2013
     */
    protected function sanitizeInput() {
        // go through all elements of the $shipment_data array and sanitize each value
        foreach($this->shipment_data as $key => $value) {
            // trim any whitespace
            $value = trim($value);
            // strip out any HTML or PHP
            $value = filter_var($value, FILTER_SANITIZE_STRING);
            // trim all input to maximum of 50 characters
            $value = substr($value, 0, 50); 
            // update the array with the sanitized value
            $this->shipment_data[$key] = $value;
        }
    }
    
    
    /**
     * Makes sure that all required fields are set
     * 
     * @return boolean true if all required fields are set
     * @throws \UnexpectedValueException if a required field is null
     * @version 01/14/2013
     * @since 01/14/2013
     */
    protected function isShipmentValid() {
        // create an array with the keys of $shipment_data that are required
        $required_fields = array('receiver_name', 'receiver_address1', 'receiver_city', 'receiver_state', 
            'receiver_postal_code', 'receiver_country_code');
        // if shipment is being sent from an address other than the shippers, there are additional required fields
        if($this->shipment_data['ship_from_different_address'] == true) {
            array_push($required_fields, 'shipping_from_name', 'shipping_from_address1', 'shipping_from_city', 
                    'shipping_from_state', 'shipping_from_postal_code', 'shipping_from_country_code');
        }
        // create a variable to hold invalid properties
        $invalid_properties = null;
        // make sure that each of these keys has an acceptable value
        foreach($required_fields as $field) {
            // make sure the required field is not empty
            if($this->shipment_data[$field] == null) {
                // add this field to the list of invalid properties
                $invalid_properties .= $field . ', ';
            }
        }
        // if there are any invalid properties, throw an exception
        if(!empty($invalid_properties)) {
            throw new \UnexpectedValueException('Shipment object is not valid.  Required properties (' 
                . $invalid_properties . ') are not set.');
        }
        else {
            return true;
        }
    }
    
    
}