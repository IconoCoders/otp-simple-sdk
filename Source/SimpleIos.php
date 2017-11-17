<?php
/**
 *  Copyright (C) 2016 OTP Mobil Kft.
 *
 *  PHP version 5
 *
 *  This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @category  SDK
 * @package   SimplePay_SDK
 * @author    SimplePay IT <itsupport@otpmobil.com>
 * @copyright 2016 OTP Mobil Kft. 
 * @license   http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @version   1.0
 * @link      http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 * 
 */

namespace Iconocoders\OtpSimpleSdk\Source;

use Iconocoders\OtpSimpleSdk\Source\SimpleTransaction;

/**
 * SimpleIOS
 * 
 * Helper object containing information about a product
 *
 * @category SDK
 * @package  SimplePay_SDK
 * @author   SimplePay IT <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 *
 */
class SimpleIos extends SimpleTransaction
{
    protected $orderNumber;
    protected $merchantId;
    protected $orderStatus;
    protected $maxRun = 10;
    protected $iosOrderUrl = '';
    public $commMethod = 'ios';
    public $status = Array();
    public $errorMessage = Array();    
    public $debugMessage = Array();
         
    /**
     * Constructor of SimpleIos class
     * 
     * @param array  $config      Configuration array or filename
     * @param string $currency    Transaction currency
     * @param string $orderNumber External number of the order
     *
     * @return void
     *
     */
    public function __construct($config = array(), $currency = '', $orderNumber = '0')
    {
        $config = $this->merchantByCurrency($config, $currency);
        $this->setup($config);      
        if (isset($this->debug_ios)) {
            $this->debug = $this->debug_ios;
        }
        $this->orderNumber = $orderNumber;
        $this->iosOrderUrl = $this->defaultsData['BASE_URL'] . $this->defaultsData['IOS_URL'];
        $this->runIos();  
        $this->logFunc("IOS", $this->status, $this->orderNumber);
    }
        
    /**
     * Starts IOS communication
     * 
     * @return void 
     *
     */  
    public function runIos()
    {
        $this->debugMessage[] = 'IOS: START';
        $iosArray = array(
            'MERCHANT' => $this->merchantId, 
            'REFNOEXT' => $this->orderNumber, 
            'HASH' => $this->createHashString(array($this->merchantId, $this->orderNumber))
        );  
        $this->logFunc("IOS", $iosArray, $this->orderNumber);        
        $iosCounter = 0;
        while ($iosCounter < $this->maxRun) {
            $result = $this->startRequest($this->iosOrderUrl, $iosArray, 'POST');           
            if ($result === false) {        
                $result = '<?xml version="1.0"?>
                <Order>
                    <ORDER_DATE>' . @date("Y-m-d H:i:s", time()) . '</ORDER_DATE>
                    <REFNO>N/A</REFNO>
                    <REFNOEXT>N/A</REFNOEXT>
                    <ORDER_STATUS>EMPTY RESULT</ORDER_STATUS>
                    <PAYMETHOD>N/A</PAYMETHOD>
                    <HASH>N/A</HASH>
                </Order>';                
            }

            $resultArray = (array) simplexml_load_string($result);           
            foreach ($resultArray as $itemName => $itemValue) {
                $this->status[$itemName] = $itemValue;
            }           
            switch ($this->status['ORDER_STATUS']) {
            case 'NOT_FOUND': 
                $iosCounter++;
                sleep(1);
                break;
            case 'CARD_NOTAUTHORIZED': 
                $iosCounter += 5;
                sleep(1);
                break;               
            default:
                $iosCounter += $this->maxRun;
            }
            $this->debugMessage[] = 'IOS ORDER_STATUS: ' . $this->status['ORDER_STATUS'];
        } 
        $this->debugMessage[] = 'IOS: END';        
    }        
}

