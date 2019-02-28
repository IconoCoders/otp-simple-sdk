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
 * SimplePay Instant Delivery Information
 *
 * Sends delivery notification via HTTP
 *
 * @category SDK
 * @package  SimplePay_SDK
 * @author   SimplePay IT <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 *
 */
class SimpleIdn extends SimpleTransaction
{
    public $targetUrl = '';
    public $commMethod = 'idn';
    public $idnRequest = array();
    public $hashFields = array(
        "MERCHANT",
        "ORDER_REF",
        "ORDER_AMOUNT",
        "ORDER_CURRENCY",
        "IDN_DATE"
    );

    protected $validFields = array(
        "MERCHANT" => array("type"=>"single", "paramName"=>"merchantId", "required" => true),
        "ORDER_REF" => array("type"=>"single", "paramName"=>"orderRef", "required"=>true),
        "ORDER_AMOUNT" => array("type"=>"single", "paramName"=>"amount", "required"=>true),
        "ORDER_CURRENCY" => array("type"=>"single", "paramName"=>"currency", "required"=>true),
        "IDN_DATE" => array("type"=>"single", "paramName"=>"idnDate", "required"=>true),
        "REF_URL" => array("type"=>"single", "paramName"=>"refUrl"),
    );

    /**
     * Constructor of SimpleIdn class
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
        if (isset($this->debug_idn)) {
            $this->debug = $this->debug_idn;
        }
        $this->fieldData['MERCHANT'] = $this->merchantId;
        $this->targetUrl = $this->defaultsData['BASE_URL'] . $this->defaultsData['IDN_URL'];
    }

    /**
     * Creates associative array for the received data
     *
     * @param array $data Processed data
     *
     * @return void
     *
     */
    protected function nameData($data = array())
    {
        return array(
            "ORDER_REF" => (isset($data[0])) ? $data[0] : 'N/A',
            "RESPONSE_CODE" => (isset($data[1])) ? $data[1] : 'N/A',
            "RESPONSE_MSG" => (isset($data[2])) ? $data[2] : 'N/A',
            "IDN_DATE" => (isset($data[3])) ? $data[3] : 'N/A',
            "ORDER_HASH" => (isset($data[4])) ? $data[4] : 'N/A',
        );
    }

    /**
     * Sends notification via cURL
     *
     * @param array $data Data array to be sent
     *
     * @return array $this->nameData() Result
     *
     */
    public function requestIdn($data = array())
    {
        if (count($data) == 0) {
            $this->errorMessage[] = 'IDN DATA: EMPTY';
            return $this->nameData();
        }
        $data['MERCHANT'] = $this->merchantId;
        $this->refnoext = $data['REFNOEXT'];
        unset($data['REFNOEXT']);

        foreach ($this->hashFields as $fieldKey) {
            $data2[$fieldKey] = $data[$fieldKey];
        }
        $irnHash = $this->createHashString($data2);
        $data2['ORDER_HASH'] = $irnHash;
        $this->idnRequest = $data2;
        $this->logFunc("IDN", $this->idnRequest, $this->refnoext);

        $result = $this->startRequest($this->targetUrl, $this->idnRequest, 'POST');
        $this->debugMessage[] = 'IDN RESULT: ' . $result;

        if (is_string($result)) {
            $processed = $this->processResponse($result);
            $this->logFunc("IDN", $processed, $this->refnoext);
            return     $processed;
        }
        $this->debugMessage[] = 'IDN RESULT: NOT STRING';
        return false;
    }
}
