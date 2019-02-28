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

use Iconocoders\OtpSimpleSdk\Source\SimpleBase;

/**
 * SimplePay Instant Payment Notification
 *
 * Processes notifications sent via HTTP POST request
 *
 * @category SDK
 * @package  SimplePay_SDK
 * @author   SimplePay IT <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 *
 */
class SimpleIpn extends SimpleBase
{
    public $echo = true;
    public $commMethod = 'ipn';
    public $successfulStatus = array(
        "PAYMENT_AUTHORIZED",   //IPN
        "COMPLETE",             //IDN
        "REFUND",               //IRN
        "PAYMENT_RECEIVED",     //WIRE
    );

    /**
     * Constructor of SimpleIpn class
     *
     * @param mixed  $config   Configuration array or filename
     * @param string $currency Transaction currency
     *
     * @return void
     *
     */
    public function __construct($config = array(), $currency = '')
    {
        $config = $this->merchantByCurrency($config, $currency);
        $this->setup($config);
        if (isset($this->debug_ipn)) {
            $this->debug = $this->debug_ipn;
        }
    }

    /**
     * Validate recceived data against HMAC HASH
     *
     * @return boolean
     *
     */
    public function validateReceived()
    {
        $this->debugMessage[] = 'IPN VALIDATION: START';
        if (!$this->ipnPostDataCheck()) {
            $this->debugMessage[] = 'IPN VALIDATION: END';
            return false;
        }

        //'ORDERSTATUS'
        $this->logFunc("IPN", $this->postData, @$this->postData['REFNOEXT']);
        if (!in_array(trim($this->postData['ORDERSTATUS']), $this->successfulStatus)) {
            $this->errorMessage[] = 'INVALID IPN ORDER STATUS: ' . $this->postData['ORDERSTATUS'];
            $this->debugMessage[] = 'IPN VALIDATION: END';
            return false;
        }
        $validationResult = false;
        $calculatedHashString = $this->createHashString($this->flatArray($this->postData, array("HASH")));
        if ($calculatedHashString == $this->postData['HASH']) {
            $validationResult = true;
        }
        if ($validationResult) {
            $this->debugMessage[] = 'IPN VALIDATION: ' . 'SUCCESSFUL';
            $this->debugMessage[] = 'IPN CALCULATED HASH: ' . $calculatedHashString;
            $this->debugMessage[] = 'IPN HASH: ' . $this->postData['HASH'];
            $this->debugMessage[] = 'IPN VALIDATION: END';
            return true;
        } elseif (!$validationResult) {
            $this->errorMessage[] = 'IPN VALIDATION: ' . 'FAILED';
            $this->errorMessage[] = 'IPN CALCULATED HASH: ' . $calculatedHashString;
            $this->errorMessage[] = 'IPN RECEIVED HASH: ' . $this->postData['HASH'];
            $this->debugMessage[] = 'IPN VALIDATION: END';
            return false;
        }
        return false;
    }

    /**
     * Creates INLINE string for corfirmation
     *
     * @return string $string <EPAYMENT> tag
     *
     */
    public function confirmReceived()
    {
        $this->debugMessage[] = 'IPN CONFIRM: START';
        if (!$this->ipnPostDataCheck()) {
            $this->debugMessage[] = 'IPN CONFIRM: END';
            return false;
        }

        $serverDate = @date("YmdHis");
        $hashArray = array(
            $this->postData['IPN_PID'][0],
            $this->postData['IPN_PNAME'][0],
            $this->postData['IPN_DATE'],
            $serverDate
        );
        $hash = $this->createHashString($hashArray);
        $string = "<EPAYMENT>" . $serverDate . "|" . $hash . "</EPAYMENT>";
        $this->debugMessage[] = 'IPN CONFIRM EPAYMENT: ' . $string;
        $this->debugMessage[] = 'IPN CONFIRM: END';
        if ($this->echo) {
            echo $string;
        }
        return $string;
    }

    /**
     * Check post data if contains REFNOEXT variable
     *
     * @return boolean
     *
     */
    protected function ipnPostDataCheck()
    {
        if (count($this->postData) < 1 || !array_key_exists('REFNOEXT', $this->postData)) {
            $this->debugMessage[] = 'IPN POST: MISSING CONTENT';
            $this->errorMessage[] = 'IPN POST: MISSING CONTENT';
            return false;
        }
        return true;
    }
}