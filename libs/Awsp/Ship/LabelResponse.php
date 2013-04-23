<?php
/**
 * The package class creates an object to hold the response from a rate query.
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 04/15/2013 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/08/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
namespace Awsp\Ship;

class LabelResponse {
    
    /**
     *
     * @var string of rate request status - can be 'Success' or 'Error'
     */
    public $status = null;
    
    /**
     *
     * @var float the total cost of this shipment
     */
    public $shipment_cost = null;
    
    
    /**
     * Holds the details of each shipping service available
     * @var array
     *  Each array element will contain:
     *      string [tracking_number] the tracking number of the package
     *      string [label_image] base-64 encoded image of the shipping label for the package
     *      string [label_file_type] type of format for label ie: gif, tif
     */
    public $labels = array();

    
    /**
     * Constructs the object and sets the status
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
