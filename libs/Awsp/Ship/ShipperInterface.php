<?php
/**
 * The shipper interface is for all shipping vendor classes.
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 04/19/2012 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
namespace Awsp\Ship;

interface ShipperInterface {
    public function setShipment(Shipment $Shipment);
    public function setConfig(array $config);
    public function getRate();
    public function createLabel();
}
