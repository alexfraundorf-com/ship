<?php
/**
 * Shipping vendor class for UPS.
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 04/19/2013 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 * @copyright (c) UPS API, documentation and logos are the property of United Parcel Service.
 * @version This class uses the December 31, 2012 version of the UPS WebServices API
 * @link https://www.ups.com/upsdeveloperkit
 */
namespace Awsp\Ship;

class Ups implements ShipperInterface {
    
    /**
     *
     * @var array holder for config data (from includes/config.php)
     */
    protected $config = array();
    
    /**
     *
     * @var string the array to be sent to the UPS API
     */
    protected $request = array();
    
    /**
     *
     * @var string the URL to send the API request to (set by __construct)
     */
    protected $api_url = null;
    
    /**
     *
     * @var object the Shipment object to process which contains Package object(s)
     */
    protected $Shipment = null;
    
    /**
     *
     * @var array An array of UPS services.  The key is the service code and the value is the service description.
     */
    protected $services = array(
        '01' => 'Next Day Air',
        '02' => '2nd Day Air',
        '03' => 'Ground',
        '07' => 'Worldwide Express',
        '08' => 'Worldwide Expeditor',
        '11' => 'Standard',
        '12' => '3 Day Select',
        '13' => 'Next Day Air Saver',
        '14' => 'Next Day Air Early AM',
        '59' => '2nd Day Air AM',
        '65' => 'World Wide Saver',
    );
    
    /**
     *
     * @var object the API call response object
     */
    protected $Response = null;
    
    
    
    /**
     * Constructor function - sets object properties
     * @param object \Awsp\Ship\Shipment $Shipment the Shipment object which also contains Package object(s)
     * @param array $config the configuration data
     * @version 04/19/2012
     * @since 12/02/2012
     */
    public function __construct(Shipment $Shipment, array $config) {
        // set the config array property
        $this->setConfig($config); 
        // set the local reference of the Shipment object
        $this->setShipment($Shipment);
        // set the API URL based on production status
        if($config['production_status'] == true) {
            $this->api_url = $config['ups']['production_url'];
        } 
        else {
            $this->api_url = $config['ups']['testing_url'];
        }
        // set request array settings that apply to all UPS requests
        // pickup type
        $this->request['PickupType']['Code'] = $config['ups']['pickup_type'];
        // rate type
        $this->request['CustomerClassification']['Code'] = $config['ups']['rate_type'];
        // UPS account number
        $this->request['Shipment']['Shipper']['ShipperNumber'] = $config['ups']['account_number'];
    }
    
    
    /**
     * Validate the config array and sets it as an object property
     * 
     * @param array $config
     * @throws \InvalidArgumentException
     * @version 04/19/2013
     * @since 04/19/2013
     */
    public function setConfig(array $config = array()) {
        // validate the config array
        if(!is_array($config) || empty($config)) {
            throw new \InvalidArgumentException('Config array is not valid.');
        }
        // set the object config array
        $this->config = $config;
    }
    
    
    /**
     * Validate the Shipment object and sets it as an object property
     * 
     * @param \Awsp\Ship\Shipment $config
     * @throws \InvalidArgumentException
     * @version 04/19/2013
     * @since 04/19/2013
     */
    public function setShipment(Shipment $Shipment) {
        // validate the Shipment object
        if(!($Shipment instanceof Shipment)) {
            throw new \InvalidArgumentException('Shipment is not a valid object type.');
        }
        // set the object property
        $this->Shipment = $Shipment;
    }
    
    
    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest() 
     *      sends the request to the UPS API and returns a RateResponse object.
     * 
     * @version updated 01/01/2013
     * @since 12/02/2012
     * @return object \Awsp\Ship\RateResponse
     * @throws \Exception
     */
    public function getRate() {
        // set request array settings
        
        // return rates for all valid services     
        $this->request['Request']['RequestOption'] = 'Shop';

        // extract shipper information from the config array
        $this->request['Shipment']['Shipper']['Address']['PostalCode'] = $this->config['ups']['shipper_postal_code'];
        $this->request['Shipment']['Shipper']['Address']['CountryCode'] = $this->config['ups']['shipper_country_code'];
        
        // check for a different shipping from location
        if($this->Shipment->get('ship_from_different_address') == true) {
            $this->request['Shipment']['ShipFrom']['Address']['PostalCode'] = 
                    $this->Shipment->get('shipping_from_postal_code');
            $this->request['Shipment']['ShipFrom']['Address']['CountryCode'] = 
                    $this->Shipment->get('shipping_from_country_code');
        }

        // extract receiver information from the Shipment object
        // receiver postal code
        $this->request['Shipment']['ShipTo']['Address']['PostalCode'] = $this->Shipment->get('receiver_postal_code');
        // receiver country code
        $this->request['Shipment']['ShipTo']['Address']['CountryCode'] = $this->Shipment->get('receiver_country_code');
        // receiver is rsidential
        if ($this->Shipment->get('receiver_is_residential') == true) {
            $this->request['Shipment']['ShipTo']['Address']['ResidentialAddressIndicator'] = '';
        }
        
        // retrieve the packages array from the Shipment object
        $packages = $this->Shipment->getPackages();
        // loop through the packages and create required fields for them
        foreach($packages as $package) {
            // (re)initialize the array holding this package's UPS formated data
            $data = array();
            // set package type (default to '02' for customer supplied package)
            if($package->getOption('type') == null) {
                $data['PackagingType']['Code'] = '02';
            }
            else {
                $data['PackagingType']['Code'] = $package->getOption('type');
            }
            // set the package's unit of dimensional measurement (inches is the default unit)
            if($this->config['dimension_unit'] == 'CM') {
                $data['Dimensions']['UnitOfMeasurement']['Code'] = 'CM';
            }
            else {
                $data['Dimensions']['UnitOfMeasurement']['Code'] = 'IN';
            }
            // set the package's dimensions and round each dimension up to the next whole number
            $data['Dimensions']['Length'] = ceil($package->Get('length'));
            $data['Dimensions']['Width'] = ceil($package->Get('width'));
            $data['Dimensions']['Height'] = ceil($package->Get('height'));
            // set the package's unit of weight (pounds are the default unit)
            if($this->config['weight_unit'] == 'KG') {
                $data['PackageWeight']['UnitOfMeasurement']['Code'] = 'KGS';
            }
            else {
                $data['PackageWeight']['UnitOfMeasurement']['Code'] = 'LBS';
            }
            // set the package's weight and round it up to the next whole number
            $data['PackageWeight']['Weight'] = ceil($package->Get('weight'));
            // check for any package options
            // insurance
            if($package->getOption('insured_amount') != null) {
                $data['PackageServiceOptions']['DeclaredValue']['CurrencyCode'] = $this->config['currency_code'];
                $data['PackageServiceOptions']['DeclaredValue']['MonetaryValue'] = 
                    $package->getOption('insured_amount');
            }
            // signature required
            if($package->getOption('signature_required') == true) {
                // use standard delivery confirmation, signature required
                $data['PackageServiceOptions']['DeliveryConfirmation']['DCISType'] = '2';
            }
            
            // add this package's data to the UPS packages array
            $this->request['Shipment']['Package'][] = $data;
        }
        
        // build the task specific SOAP options
        // initialize the params array
        $params = array();
        // set the path to the WSDL file
        $params['wsdl'] = $this->config['ups']['path_to_api_files'] . '/RateWS.wsdl';
        // set the API operation call
        $params['operation'] = 'ProcessRate';
        // complete the API URL
        $params['url'] = $this->api_url . '/Rate';
        // send the SOAP request - returns a standard object
        $this->Response = $this->sendRequest($params);
        
        // check on the response status
        $status = $this->getResponseStatus();
        // if there was an error, throw an exception
        if($status != 'Success') {
            throw new \Exception('There was an error retrieving the rates.');
        }
        // as long as the request was successful, create the RateResponse object and fill it
        $Response = new RateResponse($status);
        // fill the RateResponse object with package details for each shipment method
        $Response->services = $this->getResponseRates();
        // return RateResponse object
        return $Response;
    }
    
    
    /**
     * Compiles the required information for obtaining a shipping rate quote into the UPS array and using sendRequest() 
     *      sends the request to the UPS API and returns a RateResponse object.

     * @param array $params parameters for label creation 
     *      string $params['service_code'] - the UPS code for the shipping service
     * @return object \Awsp\Ship\LabelResponse
     * @version updated 01/16/2013
     * @since 12/09/2012
     */
    public function createLabel(array $params=array()) {
        // set request array settings
        $this->request['Request']['RequestOption'] = 'nonvalidate';

        // extract shipper information from config array
        $this->request['Shipment']['Shipper']['Name'] = $this->config['ups']['shipper_name'];
        $this->request['Shipment']['Shipper']['AttentionName'] = $this->config['ups']['shipper_attention_name'];
        $this->request['Shipment']['Shipper']['Phone']['Number'] = $this->config['ups']['shipper_phone'];
        $this->request['Shipment']['Shipper']['EMailAddress'] = $this->config['ups']['shipper_email'];
        $this->request['Shipment']['Shipper']['Address']['AddressLine'][] = $this->config['ups']['shipper_address1'];
        if($this->config['ups']['shipper_address2'] != null) {
            $this->request['Shipment']['Shipper']['Address']['AddressLine'][] = 
                    $this->config['ups']['shipper_address2'];
        }
        if($this->config['ups']['shipper_address3'] != null) {
            $this->request['Shipment']['Shipper']['Address']['AddressLine'][] = 
                    $this->config['ups']['shipper_address3'];
        }
        $this->request['Shipment']['Shipper']['Address']['City'] = $this->config['ups']['shipper_city'];
        $this->request['Shipment']['Shipper']['Address']['StateProvinceCode'] = $this->config['ups']['shipper_state'];
        $this->request['Shipment']['Shipper']['Address']['PostalCode'] = $this->config['ups']['shipper_postal_code'];
        $this->request['Shipment']['Shipper']['Address']['CountryCode'] = $this->config['ups']['shipper_country_code'];
        
        // check for a different shipping from location
        if($this->Shipment->get('ship_from_different_address') == true) {
            $this->request['Shipment']['ShipFrom']['Name'] = $this->Shipment->get('shipping_from_name');
            $this->request['Shipment']['ShipFrom']['AttentionName'] = 
                    $this->Shipment->get('shipping_from_attention_name');
            $this->request['Shipment']['ShipFrom']['Phone']['Number'] = $this->Shipment->get('shipping_from_phone');
            $this->request['Shipment']['ShipFrom']['EMailAddress'] = $this->Shipment->get('shipping_from_email');
            $this->request['Shipment']['ShipFrom']['Address']['AddressLine'][] = 
                    $this->Shipment->get('shipping_from_address1');
            if($this->Shipment->get('shipping_from_address2') != null) {
                $this->request['Shipment']['ShipFrom']['Address']['AddressLine'][] = 
                        $this->Shipment->get('shipping_from_address2');
            }
            if($this->Shipment->get('shipping_from_address3') != null) {
                $this->request['Shipment']['ShipFrom']['Address']['AddressLine'][] = 
                        $this->Shipment->get('shipping_from_address3');
            }
            $this->request['Shipment']['ShipFrom']['Address']['City'] = $this->Shipment->get('shipping_from_city');
            $this->request['Shipment']['ShipFrom']['Address']['StateProvinceCode'] = 
                    $this->Shipment->get('shipping_from_state');
            $this->request['Shipment']['ShipFrom']['Address']['PostalCode'] = 
                    $this->Shipment->get('shipping_from_postal_code');
            $this->request['Shipment']['ShipFrom']['Address']['CountryCode'] = 
                    $this->Shipment->get('shipping_from_country_code');
        }
        
        // receiver information
        $this->request['Shipment']['ShipTo']['Name'] = $this->Shipment->get('receiver_name');
        $this->request['Shipment']['ShipTo']['AttentionName'] = $this->Shipment->get('receiver_attention_name');
        $this->request['Shipment']['ShipTo']['Phone']['Number'] = $this->Shipment->get('receiver_phone');
        $this->request['Shipment']['ShipTo']['EMailAddress'] = $this->Shipment->get('receiver_email');
        $this->request['Shipment']['ShipTo']['Address']['AddressLine'][] = $this->Shipment->get('receiver_address1');
        if($this->Shipment->get('receiver_address2') != null) {
            $this->request['Shipment']['ShipTo']['Address']['AddressLine'][] = 
                    $this->Shipment->get('receiver_address2');
        }
        if($this->Shipment->get('receiver_address3') != null) {
            $this->request['Shipment']['ShipTo']['Address']['AddressLine'][] = 
                    $this->Shipment->get('receiver_address3');
        }
        $this->request['Shipment']['ShipTo']['Address']['City'] = $this->Shipment->get('receiver_city');
        $this->request['Shipment']['ShipTo']['Address']['StateProvinceCode'] = $this->Shipment->get('receiver_state');
        $this->request['Shipment']['ShipTo']['Address']['PostalCode'] = $this->Shipment->get('receiver_postal_code');
        $this->request['Shipment']['ShipTo']['Address']['CountryCode'] = $this->Shipment->get('receiver_country_code');
        // Receiver is Residential (remove this line if not a residential address)
        if ($this->Shipment->get('receiver_is_residential') == true) {
            $this->request['Shipment']['ShipTo']['Address']['ResidentialAddressIndicator'] = '';
        }
        
        $this->request['Shipment']['PaymentInformation']['ShipmentCharge']['BillShipper']['AccountNumber'] = 
                $this->config['ups']['account_number'];
        $this->request['Shipment']['Service']['Code'] = $params['service_code'];
        
        // billing for transportation charges
        $this->request['Shipment']['PaymentInformation']['ShipmentCharge']['Type'] = '01'; 
        
        // set the return format of the image
        $this->request['Shipment']['LabelSpecification']['LabelImageFormat']['Code'] = 'GIF';
        
        // use Quantum View Notify to email tracking number(s) to receiver
        if(($this->config['email_tracking_number_to_receiver'] == true) && ($this->Shipment->get('receiver_email') != null)) {
            $this->request['Shipment']['ShipmentServiceOptions']['Notification']['NotificationCode'] = '6';
            $this->request['Shipment']['ShipmentServiceOptions']['Notification']['EMail']['EMailAddress'] = 
                    $this->Shipment->get('receiver_email');
        }
        
        // retrieve the packages array from the Shipment object
        $packages = $this->Shipment->getPackages();
        // loop through the packages and create required fields for them
        foreach($packages as $package) {
            // (re)initialize the array holding this package's UPS formated data
            $data = array();
            // set package type (default to '02' for customer supplied package)
            // !!! important - this is a different array than is used in the rating request!
            if($package->getOption('type') == null) {
                $data['Packaging']['Code'] = '02';
            }
            else {
                $data['Packaging']['Code'] = $package->getOption('type');
            }
            // set the package's description or a space if none is set
            if($package->getOption('description') != null) {
                $data['Description'] = $package->getOption('description');
            }
            else {
                // UPS requires a non-null entry for the description
                $data['Description'] = ' ';
            }
            // set the package's unit of dimensional measurement (inches is the default unit)
            if($this->config['dimension_unit'] == 'CM') {
                $data['Dimensions']['UnitOfMeasurement']['Code'] = 'CM';
            }
            else {
                $data['Dimensions']['UnitOfMeasurement']['Code'] = 'IN';
            }
            // set the package's dimensions and round each dimension up to the next whole number
            $data['Dimensions']['Length'] = ceil($package->Get('length'));
            $data['Dimensions']['Width'] = ceil($package->Get('width'));
            $data['Dimensions']['Height'] = ceil($package->Get('height'));
            // set the package's unit of weight (pounds are the default unit)
            if($this->config['dimension_unit'] == 'KG') {
                $data['PackageWeight']['UnitOfMeasurement']['Code'] = 'KGS';
            }
            else {
                $data['PackageWeight']['UnitOfMeasurement']['Code'] = 'LBS';
            }
            // set the package's weight and round it up to the next whole number
            $data['PackageWeight']['Weight'] = ceil($package->Get('weight'));
            
            // check for any package options
            // insurance
            if($package->getOption('insured_amount') != null) {
                $data['PackageServiceOptions']['DeclaredValue']['CurrencyCode'] = $this->config['currency_code'];
                $data['PackageServiceOptions']['DeclaredValue']['MonetaryValue'] = 
                    $package->getOption('insured_amount');
            }
            // signature required
            if($package->getOption('signature_required') == true) {
                // use standard delivery confirmation, signature required
                $data['PackageServiceOptions']['DeliveryConfirmation']['DCISType'] = '2';
            }
            
            // add this package's data to the UPS packages array
            $this->request['Shipment']['Package'][] = $data;
        }
        
        // build the task specific SOAP options
        // initialize the params array
        $params = array();
        // set the path to the WSDL file
        $params['wsdl'] = $this->config['ups']['path_to_api_files'] . '/Ship.wsdl';
        // set the API operation call
        $params['operation'] = 'ProcessShipment';
        // complete the API URL
        $params['url'] = $this->api_url . '/Ship';
        // send the SOAP request - returns a standard object
        $this->Response = $this->sendRequest($params);
        
        // build parameter for RatesResponse object
        $status = $this->getResponseStatus();
        // if there was an error, throw an exception
        if($status != 'Success') {
            throw new \Exception('There was an error creating the label.');
        }
        // as long as the request was successful, create the RateResponse object and fill it
        $Response = new LabelResponse($status);
        // get the total cost of the shipment
        $Response->shipment_cost = $this->getResponseLabelTotalCost();
        // fill the RateResponse object with package details for each shipment method
        $Response->labels = $this->getResponseLabels();
        // return LabelResponse object
        return $Response;
    }
    
    
    /**
     * Sends the SOAP request to the UPS API server and converts the response into a standard object.
     * 
     * @param array $params 
     *      string $params['wsdl'] the absolute local path to the WSDL file
     *      string $params['operation'] the UPS operation keyword
     *      string $params['url'] the URL to send the request to
     * @return object \SoapVar generated standard object containing the SOAP response
     * @throws \SoapFault exception if the SOAP request fails
     * @version updated 12/09/2012
     * @since 12/02/2012
     */
    protected function sendRequest(array $params=array()) {
        try {
            // set the SOAP mode array
            $mode = array(
                'soap_version' => 'SOAP_1_1',  // use soap 1.1 client
                'trace' => 1
                );
            // instantiate the SOAP client
            $client = new \SoapClient($params['wsdl'] , $mode);
            //set endpoint url
            $client->__setLocation($params['url']);
            // build the SOAP header
            $upss = array();
            $upss['UsernameToken']['Username'] = $this->config['ups']['user'];
            $upss['UsernameToken']['Password'] = $this->config['ups']['password'];
            $upss['ServiceAccessToken']['AccessLicenseNumber'] = $this->config['ups']['key'];
            $header = new \SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0', 'UPSSecurity', $upss);
            $client->__setSoapHeaders($header);
            // get SOAP response
            $Response = $client->__soapCall($params['operation'] , array($this->request));
            // convert the response into a standard object and return it
            return new \SoapVar($Response, SOAP_ENC_OBJECT);
        }
        catch(\Exception $e) {
            // extract the error details from SoapFault object
            $error_detail = serialize($e->detail->Errors->ErrorDetail);
            // rethrow a more useful exception message
            throw new \Exception('UPS SOAP Request failed - ' . $e->getMessage() . ' - Serialized Details: ' 
                    . $error_detail);
        }
   }
    

    /**
     * Extracts that status of the response and normalizes it.  Returns 'Success' or 'Error'
     * 
     * @return string 'Success' or 'Error'
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    protected function getResponseStatus() {
        // extract the response status from the SOAP response object
        $status = $this->Response->enc_value->Response->ResponseStatus->Description;
        // normalize the status output
        if($status == 'Success') {
            return 'Success';
        }
        else {
            return 'Error';
        }
    }
    
    
    /**
     * Extracts any UPS service messages from the SOAP response object
     * 
     * @param type $messages the alert section of the SOAP response object
     * @return array of any messages
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    protected function getResponseMessages($messages) {
        // initialize the output array
        $output = array();
        // make sure that $messages is not an empty array or object
        if(! empty($messages)) {
            // if there are more than one messages, $messages will be an array of objects
            if(is_array($messages)) {
            // loop through response messages
                foreach($messages as $message) {
                    $output[] = $message->Code . ': ' . $message->Description;
                }
            }
            // if there is only one message, $messages will be an object
            elseif(is_object($messages)) {
                $output[] = $messages->Code . ': ' . $messages->Description;
            }
        }
        // return the completed array
        return $output;
    }
    
    
    /**
     * Extracts and returns rates for the services from the SOAP response object
     * 
     * @return array a multi-dimensional array containing the rate data for each service
     * @throws \UnexpectedValueException
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    protected function getResponseRates() {
        // extract the rates from the SOAP response object
        $rates = $this->Response->enc_value->RatedShipment;
        // make sure that $rates is not empty
        if(empty($rates)) {
            throw new \UnexpectedValueException('Failed to retrieve shipping rates from API.');
        }
        // initialize the output array
        $output = array();
        // if there are more than one rates, $rates will be an array of objects
        if(is_array($rates)) {
        // loop through rates
            foreach($rates as $rate) {
                // add this array to the output
                $output[] = $this->getResponseRatesWorker($rate);
            }
        }
        // if there is only one message, $messages will be an object
        elseif(is_object($rates)) {
            // add the array to the output
            $output[] = $this->getResponseRatesWorker($rates);
        }
        // not an array or an object
        else {
            throw new \UnexpectedValueException('Value $rates is not an array nor an object.');
        }
        // return the completed array
        return $output;
    }
    
    
    /**
     * Extracts the data for a single rate service (used by getResponseRates)
     * 
     * @param object $rate is an object containing data for a single rate service
     * @return array containing the extracted data
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    protected function getResponseRatesWorker($rate) {
        // (re)initialize the array holder for the loop
        $array = array();
        // build an array for this rate's information
        $array['messages'] = $this->getResponseMessages($rate->RatedShipmentAlert);
        $array['service_code'] = $rate->Service->Code;
        $array['service_description'] = 'UPS ' . $this->services[$array['service_code']];
        $array['total_cost'] = $rate->TotalCharges->MonetaryValue;
        $array['currency_code'] = $rate->TotalCharges->CurrencyCode;
        $array['packages'] = $this->getPackageRateDetails($rate->RatedPackage);
        $array['package_count'] = count($array['packages']);
        return $array;
    }
    
    
    /**
     * Extracts rate details for each package in the shipment
     * 
     * @param array|object $packages data about the package(s) from the SOAP response
     * @return array
     * @throws \UnexpectedValueException
     * @version updated 01/16/2013
     * @since 12/08/2012
     */
    protected function getPackageRateDetails($packages) {
        // initialize the output array
        $output = array();
        // if there are more than one rates, $rates will be an array of objects
        if(is_array($packages)) {
            // loop through rates
            foreach($packages as $package) {
                // add this package's array to the output
                $output[] = $this->getPackageRateDetailsWorker($package);
            }
        }
        // if there is only one message, $messages will be an object
        elseif(is_object($packages)) {
            // add the package array to the output
            $output[] = $this->getPackageRateDetailsWorker($packages);
        }
        // not an array or an object
        else {
            throw new \UnexpectedValueException('Value $packages is not an array nor an object.');
        }
        // return the completed array
        return $output;
    }
    

    /**
     * Extracts the details for a single package (used by getPackageRateDetails)
     * 
     * @param object $package is an object containing data for a single package
     * @return array containing the extracted data
     * @version updated 12/09/2012
     * @since 12/08/2012
     */
    protected function getPackageRateDetailsWorker($package) {
        // standardize the weight unit used for this package
        $weight_unit = $package->BillingWeight->UnitOfMeasurement->Code;
        if($weight_unit == 'LBS') {
            $weight_unit = 'LB';
        }
        elseif($weight_unit == 'KGS') {
            $weight_unit = 'KG';
        }
        // build an array for this rate's information
        $array = array(
            'base_cost' => $package->TransportationCharges->MonetaryValue,
            'option_cost' => $package->ServiceOptionsCharges->MonetaryValue,
            'total_cost' => $package->TotalCharges->MonetaryValue,
            'weight' => $package->Weight,
            'billed_weight' => $package->BillingWeight->Weight,
            'weight_unit' => $weight_unit
            );
        return $array;
    }
    
    
    /**
     * Extract the total cost of the shipping label(s) from the SOAP response
     * 
     * @return string the cost of the shipping label(s)
     * @version updated 01/08/2013
     * @since 01/08/2013
     */
    protected function getResponseLabelTotalCost() {
        return $this->Response->enc_value->ShipmentResults->ShipmentCharges->TotalCharges->MonetaryValue;
    }
    
    
    /**
     * Extracts the label(s) information from the SOAP response object
     * 
     * @return array with the label(s) data
     * @throws \UnexpectedValueException
     * @version updated 01/08/2013
     * @since 01/08/2013
     */
    protected function getResponseLabels() {
        // extract the rates from the SOAP response object
        $labels = $this->Response->enc_value->ShipmentResults->PackageResults;
        // make sure that $rates is not empty
        if(empty($labels)) {
            throw new \UnexpectedValueException('Failed to retrieve shipping labels from API.');
        }
        // initialize the output array
        $output = array();
        // if there are more than one rates, $rates will be an array of objects
        if(is_array($labels)) {
        // loop through rates
            foreach($labels as $label) {
                // append each label array to the output
                $output[] = $this->getResponseLabelsWorker($label);
            }
        }
        else {
            // there is only one label
            $output[] = $this->getResponseLabelsWorker($labels);
        }
        // return the labels array
        return $output;
    }
    
    
    /**
     * Extracts the data for an individual label from the SOAP response object (used by getResponseLabels)
     * 
     * @return array with the label's data
     * @version updated 01/17/2013
     * @since 01/08/2013
     */
    protected function getResponseLabelsWorker($label) {
        // (re)initialize the array holder for the loop
        $array = array();
        // build an array for this rate's information
        $array['tracking_number'] = $label->TrackingNumber;
        $array['label_image'] = $label->ShippingLabel->GraphicImage;
        $array['label_file_type'] = 'gif';
        // return the array
        return $array;
    }

    
}