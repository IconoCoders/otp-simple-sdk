<?php

/**
 *  Copyright (C) 2020 OTP Mobil Kft.
 *
 *  PHP version 7
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
 * @package   SimplePayV2
 * @author    SimplePay IT Support <itsupport@otpmobil.com>
 * @copyright 2020 OTP Mobil Kft.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link      http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */


/**
 * Base class for SimplePay implementation
 *
 * @category SDK
 * @package  SimplePayV2_SDK
 * @author   SimplePay IT Support <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */
class Base
{
    use Signature;
    use Communication;
    use Views;
    use Logger;

    public $config = [];
    protected $headers = [];
    protected $hashAlgo = 'sha384';
    public $sdkVersion = 'SimplePay_PHP_SDK_2.1.0_200825';
    protected $logSeparator = '|';
    protected $logContent = [];
    protected $debugMessage = [];
    protected $currentInterface = '';
    protected $api = [
        'sandbox' => 'https://sandbox.simplepay.hu/payment',
        'live' => 'https://secure.simplepay.hu/payment'
        ];
    protected $apiInterface = [
        'start' => '/v2/start',
        'finish' => '/v2/finish',
        'refund' => '/v2/refund',
        'query' => '/v2/query',
        ];
    public $logTransactionId = 'N/A';
    public $logOrderRef = 'N/A';
    public $logPath = '';
    protected $phpVersion = 7;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->logContent['runMode'] = strtoupper($this->currentInterface);
        $ver = (float)phpversion();
        $this->logContent['phpVersion'] = $ver;
        if (is_numeric($ver)) {
            if ($ver < 7.0) {
                $this->phpVersion = 5;
            }
        }
    }

    /**
     * Add unique config field
     *
     * @param string $key   Config field name
     * @param string $value Vonfig field value
     *
     * @return void
     */
    public function addConfigData($key = '', $value = '')
    {
        if ($key == '') {
            $key = 'EMPTY_CONFIG_KEY';
        }
        $this->config[$key] = $value;
    }

     /**
      * Add complete config array
      *
      * @param string $config Populated config array
      *
      * @return void
      */
    public function addConfig($config = [])
    {
        foreach ($config as $configKey => $configValue) {
            $this->config[$configKey] = $configValue;
        }
    }

    /**
     * Add uniq transaction field
     *
     * @param string $key   Data field name
     * @param string $value Data field value
     *
     * @return void
     */
    public function addData($key = '', $value = '')
    {
        if ($key == '') {
            $key = 'EMPTY_DATA_KEY';
        }
        $this->transactionBase[$key] = $value;
    }

    /**
     * Add data to a group
     *
     * @param string $group Data group name
     * @param string $key   Data field name
     * @param string $value Data field value
     *
     * @return void
     */
    public function addGroupData($group = '', $key = '', $value = '')
    {
        if (!isset($this->transactionBase[$group])) {
            $this->transactionBase[$group] = [];
        }
        $this->transactionBase[$group][$key] = $value;
    }

    /**
     * Add item to pay
     *
     * @param string $itemData A product or service for pay
     *
     * @return void
     */
    public function addItems($itemData = [])
    {
        $item = [
            'ref' => '',
            'title' => '',
            'description' => '',
            'amount' => 0,
            'price' => 0,
            'tax' => 0,
        ];

        if (!isset($this->transactionBase['items'])) {
            $this->transactionBase['items'] = [];
        }

        foreach ($itemData as $itemKey => $itemValue) {
            $item[$itemKey] = $itemValue;
        }
        $this->transactionBase['items'][] = $item;
    }

    /**
     * Shows transaction base data
     *
     * @return array $this->transactionBase Transaction data
     */
    public function getTransactionBase()
    {
        return $this->transactionBase;
    }

    /**
     * Shows API call return data
     *
     * @return array $this->returnData Return data
     */
    public function getReturnData()
    {
        return $this->convertToArray($this->returnData);
    }

    /**
     * Shows transactional log
     *
     * @return array $this->logContent Transactional log
     */
    public function getLogContent()
    {
        return $this->logContent;
    }

    /**
     * Check data if JSON, or set data to JSON
     *
     * @param string $data Data
     *
     * @return string JSON encoded data
     */
    public function checkOrSetToJson($data = '')
    {
        $json = '[]';
        //empty
        if ($data === '') {
            $json =  json_encode([]);
        }
        //array
        if (is_array($data)) {
            $json =  json_encode($data);
        }
        //object
        if (is_object($data)) {
            $json =  json_encode($data);
        }
        //json
        $result = @json_decode($data);
        if ($result !== null) {
            $json =  $data;
        }
        //serialized
        $result = @unserialize($data);
        if ($result !== false) {
            $json =  json_encode($result);
        }
        return $json;
    }

    /**
     * Serves header array
     *
     * @param string $hash     Signature for validation
     * @param string $language Landuage of content
     *
     * @return array Populated header array
     */
    protected function getHeaders($hash = '', $language = 'en')
    {
        $headers = [
            'Accept-language: ' . $language,
            'Content-type: application/json',
            'Signature: ' . $hash,
        ];
        return $headers;
    }

    /**
     * Random string generation for salt
     *
     * @param integer $length Lemgth of random string, default 32
     *
     * @return string Random string
     */
    protected function getSalt($length = 32)
    {
        $saltBase = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ($i=0; $i <= $length; $i++) {
            $saltBase .= substr($chars, rand(1, strlen($chars)), 1);
        }
        return hash('md5', $saltBase);
    }

    /**
     * API URL settings depend on function
     *
     * @return void
     */
    protected function setApiUrl()
    {
        $api = 'live';
        if (isset($this->config['api'])) {
            $api = $this->config['api'];
        }
        $this->config['apiUrl'] = $this->api[$api] . $this->apiInterface[$this->currentInterface];
    }

    /**
     * Convert object to array
     *
     * @param object $obj Object to transform
     *
     * @return array $new Result array
     */
    protected function convertToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        $new = $obj;
        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $new[$key] = $this->convertToArray($val);
            }
        }
        return $new;
    }

    /**
     * Creates a 1-dimension array from a 2-dimension one
     *
     * @param array $arrayForProcess Array to be processed
     *
     * @return array $return          Flat array
     */
    protected function getFlatArray($arrayForProcess = [])
    {
        $array = $this->convertToArray($arrayForProcess);
        $return = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $subArray = $this->getFlatArray($value);
                foreach ($subArray as $subKey => $subValue) {
                    $return[$key . '_' . $subKey] = $subValue;
                }
            } elseif (!is_array($value)) {
                $return[$key] = $value;
            }
        }
        return $return;
    }

    /**
     * Set config variables
     *
     * @return void
     */
    protected function setConfig()
    {
        if (isset($this->transactionBase['currency'])  && $this->transactionBase['currency'] != '') {
            $this->config['merchant'] = $this->config[$this->transactionBase['currency'] . '_MERCHANT'];
            $this->config['merchantKey'] = $this->config[$this->transactionBase['currency'] . '_SECRET_KEY'];
        } elseif (isset($this->config['merchantAccount'])) {
            foreach ($this->config as $configKey => $configValue) {
                if ($configValue === $this->config['merchantAccount']) {
                    $key = $configKey;
                    break;
                }
            }
            $this->transactionBase['currency'] = substr($key, 0, 3);
            $this->config['merchant'] = $this->config[$this->transactionBase['currency'] . '_MERCHANT'];
            $this->config['merchantKey'] = $this->config[$this->transactionBase['currency'] . '_SECRET_KEY'];
        }

        $this->config['api'] = 'live';
        if ($this->config['SANDBOX']) {
            $this->config['api'] = 'sandbox';
        }
        $this->logContent['environment'] = strtoupper($this->config['api']);

        $this->config['logger'] = false;
        if (isset($this->config['LOGGER'])) {
            $this->config['logger'] = $this->config['LOGGER'];
        }

        $this->config['logPath'] = 'log';
        if (isset($this->config['LOG_PATH'])) {
            $this->config['logPath'] = $this->config['LOG_PATH'];
        }

        $this->config['autoChallenge'] = false;
        if (isset($this->config['AUTOCHALLENGE'])) {
            $this->config['autoChallenge'] = $this->config['AUTOCHALLENGE'];
        }
    }

    /**
     * Transaction preparation
     *
     * All settings before start transaction
     *
     * @return void
     */
    protected function prepare()
    {
        $this->setConfig();
        $this->logContent['callState1'] = 'PREPARE';
        $this->setApiUrl();
        $this->transactionBase['merchant'] = $this->config['merchant'];
        $this->transactionBase['salt'] = $this->getSalt();
        $this->transactionBase['sdkVersion'] = $this->sdkVersion . ':' . hash_file('md5', __FILE__);
        $this->content = $this->getHashBase($this->transactionBase);
        $this->logContent = array_merge($this->logContent, $this->transactionBase);
        $this->config['computedHash'] = $this->getSignature($this->config['merchantKey'], $this->content);
        $this->headers = $this->getHeaders($this->config['computedHash'], 'EN');
    }

    /**
     * Execute API call and returns with result
     *
     * @return array $result
     */
    protected function execApiCall()
    {
        $this->prepare();
        $transaction = [];

        $this->logContent['callState2'] = 'REQUEST';
        $this->logContent['sendApiUrl'] = $this->config['apiUrl'];
        $this->logContent['sendContent'] = $this->content;
        $this->logContent['sendSignature'] = $this->config['computedHash'];

        $commRresult = $this->runCommunication($this->config['apiUrl'], $this->content, $this->headers);

        $this->logContent['callState3'] = 'RESULT';

        //call result
        $result = explode("\r\n", $commRresult);
        $transaction['responseBody'] = end($result);

        //signature
        foreach ($result as $resultItem) {
            $headerElement = explode(":", $resultItem);
            if (isset($headerElement[0]) && isset($headerElement[1])) {
                $header[$headerElement[0]] = $headerElement[1];
            }
        }
        $transaction['responseSignature'] = $this->getSignatureFromHeader($header);

        //check transaction validity
        $transaction['responseSignatureValid'] = false;
        if ($this->isCheckSignature($transaction['responseBody'], $transaction['responseSignature'])) {
            $transaction['responseSignatureValid'] = true;
        }

        //fill transaction data
        if (is_object(json_decode($transaction['responseBody']))) {
            foreach (json_decode($transaction['responseBody']) as $key => $value) {
                   $transaction[$key] = $value;
            }
        }

        if (isset($transaction['transactionId'])) {
            $this->logTransactionId = $transaction['transactionId'];
        } elseif (isset($transaction['cardId'])) {
            $this->logTransactionId = $transaction['cardId'];
        }
        if (isset($transaction['orderRef'])) {
            $this->logOrderRef = $transaction['orderRef'];
        }

        $this->returnData = $transaction;
        $this->logContent = array_merge($this->logContent, $transaction);
        $this->logContent = array_merge($this->logContent, $this->getTransactionBase());
        $this->logContent = array_merge($this->logContent, $this->getReturnData());
        $this->writeLog();
        return $transaction;
    }
}


 /**
  * Start transaction
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
class SimplePayStart extends Base
{
    protected $currentInterface = 'start';
    public $transactionBase = [
        'salt' => '',
        'merchant' => '',
        'orderRef' => '',
        'currency' => '',
        'sdkVersion' => '',
        'methods' => [],
        ];

     /**
      * Send initial data to SimplePay API for validation
      * The result is the payment link to where website has to redirect customer
      *
      * @return void
      */
    public function runStart()
    {
        $this->execApiCall();
    }
}


 /**
  * Back
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
class SimplePayBack extends Base
{
    protected $currentInterface = 'back';
    protected $notification = [];
    public $request = [
        'rRequest' => '',
        'sRequest' => '',
        'rJson' => '',
        'rContent' => [
            'r' => 'N/A',
            't' => 'N/A',
            'e' => 'N/A',
            'm' => 'N/A',
            'o' => 'N/A',
            ]
        ];

     /**
      * Validates CTRL variable
      *
      * @param string $rRequest Request data -> r
      * @param string $sRequest Request data -> s
      *
      * @return boolean
      */
    public function isBackSignatureCheck($rRequest = '', $sRequest = '')
    {
        //request handling
        $this->request['rRequest'] = $rRequest;
        $this->request['sRequest'] = $sRequest;
        $this->request['rJson'] = base64_decode($this->request['rRequest']);
        $this->request['rJson'] = $this->checkOrSetToJson($this->request['rJson']);

        foreach (json_decode($this->request['rJson']) as $key => $value) {
            $this->request['rContent'][$key] = $value;
        }
        $this->logContent = array_merge($this->logContent, $this->request);

        $this->addConfigData('merchantAccount', $this->request['rContent']['m']);
        $this->setConfig();

        //notification
        foreach ($this->request['rContent'] as $contentKey => $contentValue) {
            $this->notification[$contentKey] = $contentValue;
        }

        //signature check
        $this->request['checkCtrlResult'] = false;
        if ($this->isCheckSignature($this->request['rJson'], $this->request['sRequest'])) {
            $this->request['checkCtrlResult'] = true;

        }

        //write log
        $this->logTransactionId = $this->notification['t'];
        $this->logOrderRef = $this->notification['o'];
        $this->writeLog($this->logContent);
        return $this->request['checkCtrlResult'];
    }


    /**
     * Raw notification data of request
     *
     * @return array Notification array
     */
    public function getRawNotification()
    {
        return $this->notification;
    }

     /**
      * Formatted notification data of request
      *
      * @return string Notification in readable format
      */
    public function getFormatedNotification()
    {
        $this->backNotification();
        return $this->notificationFormated;
    }
}


 /**
  * IPN
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
class SimplePayIpn extends Base
{
    protected $currentInterface = 'ipn';
    protected $returnData = [];
    protected $receiveDate = '';
    protected $ipnContent = [];
    protected $responseContent = '';
    protected $ipnReturnData = [];
    public $validationResult = false;

    /**
     * IPN validation
     *
     * @param string $content IPN content
     *
     * @return boolean
     */
    public function isIpnSignatureCheck($content = '')
    {
        if (!function_exists('getallheaders')) {
            /**
             * Getallheaders fon Nginx
             *
             * @return header
             */
            function getallheaders()
            {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) === 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }
        $signature = $this->getSignatureFromHeader(getallheaders());

        foreach (json_decode($this->checkOrSetToJson($content)) as $key => $value) {
            $this->ipnContent[$key] = $value;
        }

        if (isset($this->ipnContent['merchant'])) {
            $this->addConfigData('merchantAccount', $this->ipnContent['merchant']);
        }
        $this->setConfig();

        $this->validationResult = false;
        if ($this->isCheckSignature($content, $signature)) {
            $this->validationResult = true;
        }
        $this->logContent['ipnBodyToValidation'] = $content;

        $this->logTransactionId = $this->ipnContent['transactionId'];
        $this->logOrderRef = $this->ipnContent['orderRef'];

        foreach ($this->ipnContent as $contentKey => $contentValue) {
            $this->logContent[$contentKey] = $contentValue;
        }
        $this->logContent['validationResult'] = $this->validationResult;

        if (!$this->validationResult) {
            $this->logContent['validationResultMessage'] = 'UNSUCCESSFUL VALIDATION, NO CONFIRMATION';
        }
        $this->writeLog($this->logContent);

        //confirm setup
        if (!$this->validationResult) {
            $this->confirmContent = 'UNSUCCESSFUL VALIDATION';
            $this->signature = 'UNSUCCESSFUL VALIDATION';
        } elseif ($this->validationResult) {
            $this->ipnContent['receiveDate'] = @date("c", time());
            $this->confirmContent = json_encode($this->ipnContent);
            $this->signature = $this->getSignature($this->config['merchantKey'], $this->confirmContent);
        }
        $this->ipnReturnData['signature'] = $this->signature;
        $this->ipnReturnData['confirmContent'] = $this->confirmContent;
        $this->writeLog(['confirmSignature' => $this->signature, 'confirmContent' => $this->confirmContent]);

        return $this->validationResult;
    }

    /**
     * Immediate IPN confirmation
     *
     * @return boolean
     */
    public function runIpnConfirm()
    {
        try {
            header('Accept-language: EN');
            header('Content-type: application/json');
            header('Signature: ' . $this->ipnReturnData['signature']);
            print $this->ipnReturnData['confirmContent'];
        } catch (Exception $e) {
            $this->writeLog(['ipnConfirm' => $e->getMessage()]);
            return false;
        }
        $this->writeLog(['ipnConfirm' => 'Confirmed directly by runIpnConfirm']);
        return true;
    }

    /**
     * IPN confirmation data
     *
     * @return array $this->ipnReturnData Content and signature for mercaht system
     */
    public function getIpnConfirmContent()
    {
        $this->writeLog(['ipnConfirm' => 'ipnReturnData provided as content by getIpnConfirmContent']);
        return $this->ipnReturnData;
    }
}


/**
 * Query
 *
 * @category SDK
 * @package  SimplePayV2_SDK
 * @author   SimplePay IT Support <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */
class SimplePayQuery extends Base
{
    protected $currentInterface = 'query';
    protected $returnData = [];
    protected $transactionBase = [
        'salt' => '',
        'merchant' => ''
    ];

    /**
     * Add SimplePay transaction ID to query
     *
     * @param string $simplePayId SimplePay transaction ID
     *
     * @return void
     */
    public function addSimplePayId($simplePayId = '')
    {
        if (!isset($this->transactionBase['transactionIds']) || count($this->transactionBase['transactionIds']) === 0) {
            $this->logTransactionId = $simplePayId;
        }
        $this->transactionBase['transactionIds'][] = $simplePayId;
    }

    /**
     * Add merchant order ID to query
     *
     * @param string $merchantOrderId Merchant order ID
     *
     * @return void
     */
    public function addMerchantOrderId($merchantOrderId = '')
    {
        if (!isset($this->transactionBase['orderRefs']) || count($this->transactionBase['orderRefs']) === 0) {
            $this->logOrderRef = $merchantOrderId;
        }
        $this->transactionBase['orderRefs'][] = $merchantOrderId;
    }

    /**
     * Run transaction data query
     *
     * @return array $result API response
     */
    public function runQuery()
    {
        return $this->execApiCall();
    }
}


 /**
  * Refund
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
class SimplePayRefund extends Base
{
    protected $currentInterface = 'refund';
    protected $returnData = [];
    public $transactionBase = [
        'salt' => '',
        'merchant' => '',
        'orderRef' => '',
        'transactionId' => '',
        'currency' => '',
        ];

    /**
     * Run refund
     *
     * @return array $result API response
     */
    public function runRefund()
    {
        if ($this->transactionBase['orderRef'] == '') {
            unset($this->transactionBase['orderRef']);
        }
        if ($this->transactionBase['transactionId'] == '') {
            unset($this->transactionBase['transactionId']);
        }
        $this->logTransactionId = @$this->transactionBase['transactionId'];
        $this->logOrderRef = @$this->transactionBase['orderRef'];
        return $this->execApiCall();
    }
}


 /**
  * Finish
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
class SimplePayFinish extends Base
{
    protected $currentInterface = 'finish';
    protected $returnData = [];
    public $transactionBase = [
        'salt' => '',
        'merchant' => '',
        'orderRef' => '',
        'transactionId' => '',
        'originalTotal' => '',
        'approveTotal' => '',
        'currency' => '',
        ];

    /**
     * Run finish
     *
     * @return array $result API response
     */
    public function runFinish()
    {
        return $this->execApiCall();
    }
}


  /**
   * Hash generation for Signature
   *
   * @category SDK
   * @package  SimplePayV2_SDK
   * @author   SimplePay IT Support <itsupport@otpmobil.com>
   * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
   * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
   */
trait Signature
{

    /**
     * Get full JSON hash string form hash calculation base
     *
     * @param string $data Data array for checking
     *
     * @return void
     */
    public function getHashBase($data = '')
    {
        return $this->checkOrSetToJson($data);
    }

    /**
     * Gives HMAC signature based on key and hash string data
     *
     * @param string $key  Secret key
     * @param string $data Hash string
     *
     * @return string Signature
     */
    public function getSignature($key = '', $data = '')
    {
        if ($key == '' || $data == '') {
            $this->logContent['signatureGeneration'] = 'Empty key or data for signature';
        }
        return base64_encode(hash_hmac($this->hashAlgo, $data, trim($key), true));
    }

    /**
     * Check data based on signature
     *
     * @param string $data             Data for check
     * @param string $signatureToCheck Signature to check
     *
     * @return boolean
     */
    public function isCheckSignature($data = '', $signatureToCheck = '')
    {
        $this->config['computedSignature'] = $this->getSignature($this->config['merchantKey'], $data);
        $this->logContent['signatureToCheck'] = $signatureToCheck;
        $this->logContent['computedSignature'] = $this->config['computedSignature'];
        try {
            if ($this->phpVersion === 7) {
                if (!hash_equals($this->config['computedSignature'], $signatureToCheck)) {
                    throw new Exception('fail');
                }
            } elseif ($this->phpVersion === 5) {
                if ($this->config['computedSignature'] !== $signatureToCheck) {
                    throw new Exception('fail');
                }
            }
        } catch (Exception $e) {
            $this->logContent['hashCheckResult'] = $e->getMessage();
            return false;
        }
        $this->logContent['hashCheckResult'] = 'success';
        return true;
    }

    /**
     * Get signature value from header
     *
     * @param array $header Header
     *
     * @return string Signature
     */
    protected function getSignatureFromHeader($header = [])
    {
        $signature = 'MISSING_HEADER_SIGNATURE';
        foreach ($header as $headerKey => $headerValue) {
            if (strtolower($headerKey) === 'signature') {
                $signature = trim($headerValue);
            }
        }
        return $signature;
    }
}


 /**
  * Communication
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
trait Communication
{

    /**
     * Handler for cURL communication
     *
     * @param string $url     URL
     * @param string $data    Sending data to URL
     * @param string $headers Header information for POST
     *
     * @return array Result of cURL communication
     */
    public function runCommunication($url = '', $data = '', $headers = [])
    {
        $result = '';
        $curlData = curl_init();
        curl_setopt($curlData, CURLOPT_URL, $url);
        curl_setopt($curlData, CURLOPT_POST, true);
        curl_setopt($curlData, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlData, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlData, CURLOPT_USERAGENT, 'curl');
        curl_setopt($curlData, CURLOPT_TIMEOUT, 60);
        curl_setopt($curlData, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlData, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlData, CURLOPT_HEADER, true);
        //cURL + SSL
        //curl_setopt($curlData, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($curlData, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($curlData);
        $this->result = $result;
        $this->curlInfo = curl_getinfo($curlData);
        try {
            if (curl_errno($curlData)) {
                throw new Exception(curl_error($curlData));
            }
        } catch (Exception $e) {
            $this->logContent['runCommunicationException'] = $e->getMessage();
        }
        curl_close($curlData);
        return $result;
    }
}


 /**
  * Views
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
trait Views
{
    public $formDetails = [
        'id' => 'SimplePayForm',
        'name' => 'SimplePayForm',
        'element' => 'button',
        'elementText' => 'Start SimplePay Payment',
    ];

    /**
     * Generates HTML submit element
     *
     * @param string $formName          The ID parameter of the form
     * @param string $submitElement     The type of the submit element ('button' or 'link' or 'auto')
     * @param string $submitElementText The label for the submit element
     *
     * @return string HTML submit
     */
    protected function formSubmitElement($formName = '', $submitElement = 'button', $submitElementText = '')
    {
        switch ($submitElement) {
        case 'link':
            $element = "\n<a href='javascript:document.getElementById(\"" . $formName ."\").submit()'>".addslashes($submitElementText)."</a>";
            break;
        case 'button':
            $element = "\n<button type='submit'>".addslashes($submitElementText)."</button>";
            break;
        case 'auto':
            $element = "\n<button type='submit'>".addslashes($submitElementText)."</button>";
            $element .= "\n<script language=\"javascript\" type=\"text/javascript\">document.getElementById(\"" . $formName . "\").submit();</script>";
            break;
        default :
            $element = "\n<button type='submit'>".addslashes($submitElementText)."</button>";
            break;
        }
        return $element;
    }

    /**
     * HTML form creation for redirect to payment page
     *
     * @return void
     */
    public function getHtmlForm()
    {
        $this->returnData['form'] = 'Transaction start was failed!';
        if (isset($this->returnData['paymentUrl']) && $this->returnData['paymentUrl'] != '') {
            $this->returnData['form'] = '<form action="' . $this->returnData['paymentUrl'] . '" method="GET" id="' . $this->formDetails['id'] . '" accept-charset="UTF-8">';
            $this->returnData['form'] .= $this->formSubmitElement($this->formDetails['name'], $this->formDetails['element'], $this->formDetails['elementText']);
            $this->returnData['form'] .= '</form>';
        }
    }

    /**
     * Notification based on back data
     *
     * @return void
     */
    protected function backNotification()
    {
        $this->notificationFormated = '<div>';
        $this->notificationFormated .= '<b>Sikertelen fizetés!</b>';
        if ($this->request['rContent']['e'] == 'SUCCESS') {
            $this->notificationFormated = '<div>';
            $this->notificationFormated .= '<b>Sikeres fizetés</b>';
        }
        $this->notificationFormated .= '<b>SimplePay tranzakció azonosító:</b> ' . $this->request['rContent']['t'] . '</br>';
        $this->notificationFormated .= '<b>Kereskedői referencia szám:</b> ' . $this->request['rContent']['o'] . '</br>';
        $this->notificationFormated .= '</div>';
    }
}


 /**
  * Logger
  *
  * @category SDK
  * @package  SimplePayV2_SDK
  * @author   SimplePay IT Support <itsupport@otpmobil.com>
  * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
  * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
  */
trait Logger
{

    /**
     * Prepare log content before write in into log
     *
     * @param array $log Optional content of log. Default is $this->logContent
     *
     * @return boolean
     */
    public function writeLog($log = [])
    {
        if (!$this->config['logger']) {
            return false;
        }

        $write = true;
        if (count($log) == 0) {
            $log = $this->logContent;
        }

        $date = @date('Y-m-d H:i:s', time());
        $logFile = $this->config['logPath'] . '/' . @date('Ymd', time()) . '.log';

        try {
            if (!is_writable($this->config['logPath'])) {
                $write = false;
                throw new Exception('Folder is not writable: ' . $this->config['logPath']);
            }
            if (file_exists($logFile)) {
                if (!is_writable($logFile)) {
                    $write = false;
                    throw new Exception('File is not writable: ' . $logFile);
                }
            }
        } catch (Exception $e) {
            $this->logContent['logFile'] = $e->getMessage();
        }

        if ($write) {
            $flat = $this->getFlatArray($log);
            $logText = '';
            foreach ($flat as $key => $value) {
                $logText .= $this->logOrderRef . $this->logSeparator;
                $logText .= $this->logTransactionId . $this->logSeparator;
                $logText .= $this->currentInterface . $this->logSeparator;
                $logText .= $date . $this->logSeparator;
                $logText .= $key . $this->logSeparator;
                $logText .= $this->contentFilter($key, $value) . "\n";
            }
            $this->logToFile($logFile, $logText);
            unset($log, $flat, $logText);
            return true;
        }
        return false;
    }

    /**
     * Remove card data from log content
     *
     * @param string $key   Log data key
     * @param string $value Log data value
     *
     * @return string  $logValue New log value
     */
    protected function contentFilter($key = '', $value = '')
    {
        $logValue = $value;
        $filtered = '***';
        if (in_array($key, ['content', 'sendContent'])) {
            $contentData = $this->convertToArray(json_decode($value));
            if (isset($contentData['cardData'])) {
                foreach (array_keys($contentData['cardData']) as $dataKey) {
                    $contentData['cardData'][$dataKey] = $filtered;
                }
            }
            if (isset($contentData['cardSecret'])) {
                $contentData['cardSecret'] = $filtered;
            }
            $logValue = json_encode($contentData);
        }
        if (strpos($key, 'cardData') !== false) {
            $logValue = $filtered;
        }
        if ($key === 'cardSecret') {
            $logValue = $filtered;
        }
        return $logValue;
    }

    /**
     * Write log into file
     *
     * @param array $logFile Log file
     * @param array $logText Log content
     *
     * @return boolean
     */
    protected function logToFile($logFile = '', $logText = '')
    {
        try {
            if (!file_put_contents($logFile, $logText, FILE_APPEND | LOCK_EX)) {
                throw new Exception('Log write error');
            }
        } catch (Exception $e) {
            $this->logContent['logToFile'] = $e->getMessage();
        }
        unset($logFile, $logText);
    }
}


/**
 * Strong Customer Authentication (SCA) -- 3DSecure
 *
 * @category SDK
 * @package  SimplePayV21_SDK
 * @author   SimplePay IT Support <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */
trait Sca
{

    /**
     * StartChallenge
     *
     * @param array $v2Result Result of API call
     *
     * @return boolean        Success of redirection
     */
    public function challenge($v2Result = [])
    {
        if (isset($v2Result['redirectUrl'])) {
            $this->returnData['paymentUrl'] = $v2Result['redirectUrl'];
            $this->getHtmlForm();
            $this->writeLog(['3DSCheckResult' => 'Card issuer bank wants to identify cardholder (challenge)', '3DSChallengeUrl' => $v2Result['redirectUrl']]);
            print $this->returnData['form'];
            return true;
        }
        $this->writeLog(['3DSCheckResult' => 'Card issuer bank wants to identify cardholder (challenge)', '3DSChallengeUrl_ERROR' => 'Missing redirect URL']);
        return false;
    }
}
