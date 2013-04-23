<?php
/**
 * Basic example usage of the AWSP Shipping class to obtain rates.
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
// The $shipmentData array is information you have received from your user.  Always validate and sanitize user input!
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
$shipmentData['receiver_address3'] = 'Room 5C'; // not supported by all shippers
$shipmentData['receiver_city'] = 'Washington';
$shipmentData['receiver_state'] = 'DC';
$shipmentData['receiver_postal_code'] = '20212';
$shipmentData['receiver_country_code'] = 'US';
$shipmentData['receiver_is_residential'] = false; // true or false


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



// UPS rates -----------------------------------------------------------------------------------------------------------
// interface with the desired shipper plugin object
try {
    // create the shipper object and pass it the Shipment object and config data array
    $Ups = new Ship\Ups($Shipment, $config);
    // calculate rates for shipment - returns an instance of RatesResponse
    $rates = $Ups->getRate();
}
catch(\Exception $e) {
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');
}

// send opening html (note: this will not create a valid html document - for example only)
echo '<html><body>';

// output UPS rates response
echo '
    <h2>UPS (United Parcel Service)* Rates:</h2>
    <dl>
        <dt><strong>Status:</strong></dt>
        <dd>' . $rates->status . '</dd>
        <dt><strong>Rate Options:</strong></dt>
        <dd>
            <ol>
';

foreach($rates->services as $service) {
    // display the service, cost and a link to create the label
    echo '<li><strong>' . $service['service_description'] . '*: $' . $service['total_cost'] 
            . '</strong> - <a href="create_label_example.php?shipper=ups&service_code=' . $service['service_code'] 
            . '">Create Shipping Label(s)</a></li><ul>';
    // display any service specific messages
    echo '<li>Service Messages:<ul>';
    foreach($service['messages'] as $message) {
        echo '<li>' . $message . '</li>';
    }
    echo '
            </ul>
        </li>
    ';
    // display a break down of multiple packages if there are more than one
    if($service['package_count'] > 1) {
        echo '<li>Multiple Package Breakdown:<ul>';
        $counter = 1;
        foreach($service['packages'] as $package) {
            echo '<li>Package ' . $counter . ': $' . $package['total_cost'] . ' (Base: ' . $package['base_cost'] 
                    . ' + Options: ' . $package['option_cost'] . ')</li>';
            $counter++;
        }
        echo '
                        </ul>
                     </li>
        ';
    }
    echo '          </ul>';
}    
echo '
            </ol>
        </dd>
    </dl>';

echo '
    <h5>Legal Disclaimer:
        <div>* UPS trademarks, logos and services are the property of United Parcel Service.</div>
    </h5>
    </body>
    </html>';