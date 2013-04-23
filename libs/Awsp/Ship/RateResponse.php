<?php
/**
 * The package class creates an object to hold the response from a rate query.
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 12/30/2012 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/08/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
namespace Awsp\Ship;

class RateResponse {
    
    /**
     *
     * @var string of rate request status - can be 'Success' or 'Error'
     */
    public $status = null;
    
    /**
     * Holds the details of each shipping service available
     * @var array
     *  Each array element will contain:
     *      array [messages] an array of any messages associated with this service
     *      string [service_code] the shippers service code for the method of transit
     *      string [service_description] the description of the method of transit
     *      float [total_cost] the total cost for this shipment and service
     *      string [currency] the currency method used
     *      integer [package_count] the number of packages in the shipment
     *      array [packages] an array holding packages data
     *          each array element will contain:
     *          float [base_cost] the base cost to ship this package
     *          float [option_cost] the cost for any options on this package
     *          float [total_cost] the total cost to ship this package
     *          float|integer [weight] the weight of this package
     *          float|integer [billed_weight] the billed weight of this package
     *          string [weight_unit] the unit of measure used for weight 'LB' or 'KG'
     */
    public $services = array();

    
    /**
     * 
     * @param status $status the status of the request - 'Success' or 'Error'
     * @version updated 12/28/2012
     * @since 12/08/2012
     */
    public function __construct($status) {
        // set class properties
        $this->status = $status;
    }
    
}
