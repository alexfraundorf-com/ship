<?php
/**
 * The package class creates an object for each package being shipped.
 * 
 * @package Awsp Shipping Package
 * @author Alex Fraundorf - AlexFraundorf.com
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 01/14/2013 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
namespace Awsp\Ship;

class Package {
 
    /**
     *
     * @var mixed integer or float - weight of package
     */
    protected $weight = null;
    
    /**
     *
     * @var mixed integer or float - length of package (the longest dimension - sorted and set by constructor)
     */
    protected $length = null;
    
    /**
     *
     * @var mixed integer or float - width of package
     */
    protected $width = null;
    
    /**
     *
     * @var mixed integer or float - height of package
     */
    protected $height = null;
    
    /**
     *
     * @var int calculated size of package (length plus girth)
     */
    protected $size = null;
    
    /**
     *
     * @var array package options
     * 
     * acceptable keys are:
     *  string 'description'
     *  string|int 'type'
     *  float|int 'insured_amount'
     *  boolean 'signature_required'
     */
    protected $options = array();
    
    
    /**
     * Constructor sets class properties and delegates calculation of the package size
     * 
     * @version updated 01/14/2013
     * @since 12/02/2012
     * @param int|float $weight the weight of the package - do NOT enclose in quotes!
     * @param array $dimensions - array elements can be integers or floats - do NOT enclose values in quotes!
     * @param array $options
     */
    public function __construct($weight, array $dimensions, array $options = array()) {
        // set class weight property
        $this->weight = $weight;
        // set the object options
        $this->options = $options;
        // order the dimensions from longest to shortest
        rsort($dimensions, SORT_NUMERIC);
        // set class dimension properties
        // note: length is the longest dimension
        $this->length = $this->roundUpToTenth($dimensions[0]);
        $this->width = $this->roundUpToTenth($dimensions[1]);
        $this->height = $this->roundUpToTenth($dimensions[2]);
        // validate the package parameters
        $this->isPackageValid();
        // calculate the package's size and set the class property
        $this->size = $this->calculatePackageSize();
    }
    
    
    /**
     * Rounds a float UP to the next tenth (always rounds up) ie: 2.32 becomes 2.4, 3.58 becomes 3.6
     * 
     * @version updated 12/09/2012
     * @since 12/09/2012
     * @param float $float the float to be rounded
     * @return float the rounded float
     */
    protected function roundUpToTenth($float) {
        // round each value UP to the next tenth
        return ceil($float * 10) / 10;
    }
    
    
    /**
     * Validates the package's weight and dimensions
     * 
     * @version updated 12/09/2012
     * @since 12/04/2012
     * @return boolean of package validity
     * @throws \UnexpectedValueException if the weight or a dimension is invalid
     */
    protected function isPackageValid() {
        // create an array of the values to validate
        $values = array('weight', 'length', 'width', 'height');
        // create a variable to hold invalid properties
        $invalid_properties = null;
        // loop through the values to check
        foreach($values as $value) {
            // make sure that each value is set and not less than or equal to zero
            if(!isset($this->{$value}) || $this->{$value} <= 0) {
                // add the invalid property to the array
                $invalid_properties .= $value . ', ';
            }
            else {
                // make sure that the value evaluates to either an integer or a float
                if(!filter_var($this->{$value}, FILTER_SANITIZE_NUMBER_INT) && 
                        !filter_var($this->{$value}, FILTER_SANITIZE_NUMBER_FLOAT)) {
                    // add the invalid property to the array
                    $invalid_properties .= $value . ', ';
                }
            }
        }
        // if there are any invalid properties, throw an exception
        if(!empty($invalid_properties)) {
            throw new \UnexpectedValueException('Package object is not valid.  Properties (' . $invalid_properties 
                . ') are invalid or not set.');
        }
        else {
            return true;
        }
    }
    
    
    /**
     * Calculates the package's size (the length plus the girth)
     * 
     * @version updated 01/14/2013
     * @since 12/04/2012
     * @return int the size (length plus girth of the package) and rounded
     */
    protected function calculatePackageSize() {
        return round($this->length + $this->calculatePackageGirth());
    }
    
    
    /**
     * Calculates the package's girth (the distance around the two smaller sides of the package or width + width 
     *      + height + height
     * 
     * @param int|float $width the width of the package (if null, the object property $this->width will be used)
     * @param int|float $height the height of the package (if null, the object property $this->height will be used)
     * @version updated 01/14/2013
     * @since 12/04/2012
     * @return int the girth of the package
     */
    public function calculatePackageGirth($width = null, $height = null) {
        // if values are null, fill them with the object properties
        if($width == null) {
            $width = $this->width;
        }
        if($height == null) {
            $height = $this->height;
        }
        // calculate and return the girth
        return 2 * ($width + $height);
    }
    
    
    /**
     * Returns the specified property of the object or throwns an exception if that property is not set.
     * 
     * @version updated 12/08/2012
     * @since 12/08/2012
     * @param string $property the desired object property
     * @return mixed the value found for the desired object property
     * @throws \UnexpectedValueException if the property is not set
     */
    public function get($property) {
        // make sure that the property is set and throw an exception if it is not
        if(!isset($this->{$property})) {
            throw new \UnexpectedValueException('There is no data in the requested property (' . $property . ').');
        }
        // as long as the property is set, return its value
        return $this->{$property};
    }
    
    
    /**
     * Returns the specified option value of the object's options array
     * 
     * @version updated 01/01/2013
     * @since 01/01/2013
     * @param string $key the desired key of the options array
     * @return mixed the value found for the desired array key
     */
    public function getOption($key) {
        // return the option value if it exists
        if(isset($this->options[$key])) {
            return $this->options[$key];
        }
        else {
            return null;
        }
    }
    
    
    /**
     * Converts an integer or float in pounds to pounds and ounces
     * 
     * @param int|float $pounds pounds value to convert
     * @return array ['pounds'] and ['ounces']
     * @throws \UnexpectedValueException if $this->weight is not recognized as an integer or a float
     * @version 01/14/2013
     * @since 01/14/2013
     */
    public function convertWeightToPoundsOunces($pounds) {
        // initialize output
        $output = array();
        // see if the package weight is an integer
        if(is_integer($pounds)) {
            $output['pounds'] = intval($pounds);
            $output['ounces'] = 0;
        }
        // see if the package weight is a float
        elseif (is_float($pounds)) {
            // split the weight by the decimal point after setting to three decimal places (for uniformity)
            $w = explode('.', number_format($pounds, 3));
            // pounds are the first entry
            $output['pounds'] = intval($w[0]);
            // back up check in case integer is evaluated as a float
            if(isset($w[1])) {
                // format $w[1] back to a decimal of pounds (dividing by 1000 because it has 3 decimal places above)
                $w[1] = $w[1] / 1000;
                // convert second entry to ounces
                $ounces = 16 * $w[1];
                // round up to the tenth of an ounce
                $output['ounces'] = $this->roundUpToTenth($ounces);
            }
            else {
                $output['ounces'] = 0;
            }
        }
        // not an integer or a float
        else {
            throw new \UnexpectedValueException('Weight value (' . $this->weight . ') is not a float or an integer.');
        }
        // return array holding pounds and ounces
        return $output;
    }
    
    
    /**
     * Converts a weight in KG to pounds (rounded to hundreths)
     * 
     * @param int|float $kg weight in KG
     * @return float weight in pounds
     * @throws \InvalidArgumentException if $kg is not numeric
     * @version 01/14/2013
     * @since 01/14/2013
     */
    public function convertKgToPounds($kg) {
        // make sure that supplied KG value is numeric
        if(! is_numeric($kg)) {
            throw new \InvalidArgumentException('Supplied KG value (' . $kg . ') is not numeric.');
        }
        // convert to pounds and round to hundreths
        return number_format($kg * 2.20462, 2);        
    }
    
    
    /**
     * Converts a length in CM to inches (rounded to hundreths)
     * 
     * @param int|float $cm length in CM
     * @return float length in inches
     * @throws \InvalidArgumentException if $cm is not numeric
     * @version 01/14/2013
     * @since 01/14/2013
     */
    public function convertCmToInches($cm) {
        // make sure that supplied CM value is numeric
        if(! is_numeric($cm)) {
            throw new \InvalidArgumentException('Supplied CM value (' . $cm . ') is not numeric.');
        }
        // convert to inches and round to hundreths
        return number_format($cm * 0.393701, 2);
    }
    
}