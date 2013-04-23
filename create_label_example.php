<?php
/**
 * Basic example usage of the AWSP Shipping class to create a shipping label(s).
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 04/19/2013 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
use \Awsp\Ship as Ship;

// display all errors while in development
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// require the config file and the autoloader file
require_once('includes/config.php');
require_once('includes/autoloader.php');


//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// The $shipmentData array and $_GET are information you have received from your user.  Always validate and sanitize 
// user input!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

// initialize the $shipmentData array
$shipmentData = array();

// shipping from location information (if different than shipper's information)
$shipmentData['ship_from_different_address'] = false;
// if $shipmentData['ship_from_different_address'] is true, the following information must be completed
$shipmentData['shipping_from_name'] = null;
$shipmentData['shipping_from_attention_name'] = null;
$shipmentData['shipping_from_phone'] = null;
$shipmentData['shipping_from_email'] = null;
$shipmentData['shipping_from_address1'] = null;
$shipmentData['shipping_from_address2'] = null;
$shipmentData['shipping_from_address3'] = null;
$shipmentData['shipping_from_city'] = null;
$shipmentData['shipping_from_state'] = null;
$shipmentData['shipping_from_postal_code'] = null;
$shipmentData['shipping_from_country_code'] = null;

// receiver information
$shipmentData['receiver_name'] = 'XYZ Corporation';
$shipmentData['receiver_attention_name'] = 'Attn: Bill';
$shipmentData['receiver_phone'] = '555-123-4567';
$shipmentData['receiver_email'] = '';
$shipmentData['receiver_address1'] = '2 Massachusetts Ave NE';
$shipmentData['receiver_address2'] = 'Suite 100';
$shipmentData['receiver_address3'] = 'Room 5C'; // not supported by USPS API
$shipmentData['receiver_city'] = 'Washington';
$shipmentData['receiver_state'] = 'DC';
$shipmentData['receiver_postal_code'] = '20212';
$shipmentData['receiver_country_code'] = 'US';
$shipmentData['receiver_is_residential'] = false; // true or false

// validate user input
// extract shipper
if(isset($_GET['shipper'])) {
    $shipper = filter_var($_GET['shipper'], FILTER_SANITIZE_STRING);
}
else {
    throw new \Exception('Missing required input (shipper).');
}

// extract service code
if(isset($_GET['service_code'])) {
    $service_code = filter_var($_GET['service_code'], FILTER_SANITIZE_STRING);
}
else {
    throw new \Exception('Missing required input (service_code).');
}


// create a Shipment object
try {
    $Shipment = new Ship\Shipment($shipmentData); 
}
// catch any exceptions 
catch(\Exception $e) {
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
}

// create a Package object and add it to the Shipment (a shipment can have multiple packages)
// this package is 24 pounds, has dimensions of 10 x 6 x 12 inches, has an insured value of $274.95 and is being sent 
//      signature required
try {
    $Package1 = new Ship\Package(
            24, // weight 
            array(10, 6, 12), // dimensions
            array( // options
                'signature_required' => true, 
                'insured_amount' => 274.95
            )
        );
    $Shipment->addPackage($Package1);
}
// catch any exceptions 
catch(\Exception $e) {
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
}

// optional - create additional Package(s) and add them to the Shipment
// note: weight and dimensions can be integers or floats, although UPS alwasy rounds up to the next whole number
// this package is 11.34 pounds and has dimensions of 14.2 x 16.8 x 26.34 inches
try {
    $Package2 = new Ship\Package(11.34, array(14.2, 16.8, 26.34));
    $Shipment->addPackage($Package2);
}
// catch any exceptions 
catch(\Exception $e) {
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
}


// create the shipper object for the appropriate shipping vendor and pass it the shipment and config data
// using UPS
if($shipper == 'ups') {
    $ShipperObj = new Ship\Ups($Shipment, $config);
}
// unrecognized shipper
else {
    throw new \Exception('Unrecognized shipper (' . $shipper . ').');
}

// send request for a shipping label(s)
try{
    // build parameters array to send to the createLabel method
    $params = array(
        'service_code' => $service_code
    );
    // call the createLabel method - a LabelResponse object will be returned unless there is an exception
    $Response = $ShipperObj->createLabel($params);
}
// display any caught exception messages
catch(\Exception $e){
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');
}

// send opening html (note: this will not create a valid html document - for example only)
echo '<html><body>';

// format label(s) response
echo '
    <dl>
        <dt><strong>Status:</strong></dt>
        <dd>' . $Response->status . '</dd>
        <dt><strong>Shipment Cost:</strong></dt>
        <dd>$' . $Response->shipment_cost . '</dd>
        <dt><strong>Label(s):</strong></dt>
        <dd>
            <ol>
';

// loop through and display information for each label
foreach($Response->labels as $label){
    // output the label tracking number and image
    echo '
        <li>
            <ul>
                <li>Tracking Number: ' . $label['tracking_number'] . '</li>
                <li>';
    
                if($label['label_file_type'] == 'gif') {
                    echo '<img src="data:image/gif;base64, ' . $label['label_image'] . '" />';
                }
                
                echo '</li>
            </ul>
        </li>';
}

echo '
            </ol>
        </dd>
    </dl>
    <h5>Legal Disclaimer:
        <div>* UPS trademarks, logos and services are the property of United Parcel Service.</div>
    </h5>
    </body>
    </html>';