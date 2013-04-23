<?php
/**
 * Config file for the AWSP Shipping package.
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
namespace Awsp\Ship;

// absolute path to the directory that contains the Awsp_Ship directory (do not use ending slash)
// example: '/home/usr/libs';
define('SHIP_PATH', '');

// configuration options for all shippers
$config = array();
// true for production or false for development
$config['production_status'] = false; 
// can be 'LB' for pounds or 'KG' for kilograms
$config['weight_unit'] = 'LB'; 
// can be 'IN' for inches or 'CM' for centimeters
$config['dimension_unit'] = 'IN'; 
// USD for US dollars
$config['currency_code'] = 'USD'; 
// if true and if a receiver email address is set, the tracking number will be emailed to the receiver by the 
// shipping vendor
$config['email_tracking_number_to_receiver'] = true; 
    

// shipper information
$config['shipper_name'] = ''; 
$config['shipper_attention_name'] = ''; 
$config['shipper_phone'] = ''; 
$config['shipper_email'] = '';
$config['shipper_address1'] = ''; 
$config['shipper_address2'] = '';
$config['shipper_address3'] = ''; 
$config['shipper_city'] = '';
$config['shipper_state'] = ''; 
$config['shipper_postal_code'] = ''; 
$config['shipper_country_code'] = 'US'; 

//----------------------------------------------------------------------------------------------------------------------

// UPS shipper configuration settings
// sign up for credentials at: https://www.ups.com/upsdeveloperkit - Note: Chrome browser does not work for this page.
$config['ups'] = array();
$config['ups']['key'] = '';
$config['ups']['user'] = '';
$config['ups']['password'] = '';
$config['ups']['account_number'] = '';
$config['ups']['testing_url'] = 'https://wwwcie.ups.com/webservices';
$config['ups']['production_url'] = 'https://onlinetools.ups.com/webservices'; 
// absolute path to the UPS API files relateive to the Ups.php file
$config['ups']['path_to_api_files'] = SHIP_PATH . '/Awsp/Ship/ups_api_files'; 

// shipper information - make any necessary overrides
// note: needs to match information on file with UPS or the API call will fail
$config['ups']['shipper_name'] = $config['shipper_name']; 
$config['ups']['shipper_attention_name'] = $config['shipper_attention_name']; 
$config['ups']['shipper_phone'] = $config['shipper_phone']; 
$config['ups']['shipper_email'] = $config['shipper_email'];
$config['ups']['shipper_address1'] = $config['shipper_address1']; 
$config['ups']['shipper_address2'] = $config['shipper_address2'];
$config['ups']['shipper_address3'] = $config['shipper_address3']; 
$config['ups']['shipper_city'] = $config['shipper_city'];
$config['ups']['shipper_state'] = $config['shipper_state']; 
$config['ups']['shipper_postal_code'] = $config['shipper_postal_code']; 
$config['ups']['shipper_country_code'] = $config['shipper_country_code']; 

/*
01 - Daily Pickup (default)
03 - Customer Counter
06 - One Time Pickup
07 - On Call Air
19 - Letter Center
20 - Air Service Center
*/
$config['ups']['pickup_type'] = '01'; 

/*
00 - Rates Associated with Shipper Number
01 - Daily Rates
04 - Retail Rates
53 - Standard List Rates
*/
$config['ups']['rate_type'] = '00'; 

//----------------------------------------------------------------------------------------------------------------------

