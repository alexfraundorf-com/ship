<?php
/**
 * A simple autoloader for the AWSP Shipping package.
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version updated 04/19/2013
 * @since 12/28/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
namespace Awsp\Ship;

/**
 * A simple autoloader for the Awsp\Ship classes based on PSR-0
 * 
 * @param string $class_name the class being loaded
 * @return void
 * @version updated 04/19/2012
 * @since 12/28/2012
 */
function awsp_ship_autoloader($class_name) {
    // remove any leading backslash
    $class_name = ltrim($class_name, '\\');
    // explode the class name into an array
    $class_name_array = explode('\\', $class_name);
    // extract the last element (file name) from the array
    $file_name = array_pop($class_name_array);
    // begin building the file path
    $file_path = SHIP_PATH . '/';
    // append the namespace pieces to the file path
    $file_path .= implode('/', $class_name_array) . '/';
    // complete the file path
    $file = $file_path . $file_name . '.php';
    // see if the file exists and is readable in this directory
    if(is_readable($file)) {
        // require the file if it exists
        require($file);
    }
}

// register the autoloader
spl_autoload_register('\Awsp\Ship\awsp_ship_autoloader');

