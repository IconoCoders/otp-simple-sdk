<?php
/**
 * Simple Payment Information Object
 *
 * @category    Iconocoders
 * @package     Iconocoders_OtpSimple
 * @author      Attila Kiss & Daniel Kovacs & Peter Szabo
 * @copyright   Iconocoders (http://iconocoders.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Iconocoders\OtpSimpleSdk\Source;

use Iconocoders\OtpSimpleSdk\Source\SimpleLiveUpdate;

class SimpleObject 
{   
    /**
     * @var \Iconocoders\OtpSimple\Model\SimpleLiveUpdate;
     */
    protected $_simpleLiveUpdate;
    /**
     * @var \Iconocoders\OtpSimple\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_objectManager;
    protected $_currency;
    protected $_sourceStringArray = [];

    /**
     * Class constructor.
     * 
     * @param \Magento\Sales\Model\Order $order
     */
    public function __construct(\Magento\Sales\Model\Order $order)
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $this->_objectManager->create('Iconocoders\OtpSimple\Helper\Data');
        $this->_helper->setCurrency($order->getStoreCurrencyCode());
        $this->_currency = $order->getStoreCurrencyCode();
        $this->_simpleLiveUpdate = new SimpleLiveUpdate($this->_helper->getConfiguration(), $order->getStoreCurrencyCode());
        $this->_simpleLiveUpdate->setField("ORDER_REF", $order->getIncrementId());
        $this->_simpleLiveUpdate->setField("ORDER_DATE", $order->getCreatedAt());
        $this->_simpleLiveUpdate->setField("PRICES_CURRENCY", $this->_currency);
        $this->_sourceStringArray[1] =  [$this->_helper->getMerchant()];
        $this->_sourceStringArray[2] =  [$order->getIncrementId()];
        $this->_sourceStringArray[3] =  [$order->getCreatedAt()];
        $this->_sourceStringArray[12] =  [0];
        
        /** Shipping Amount */
        $shippingAmount = $this->_currency == 'HUF' ? intval($order->getShippingAmount()) : $order->getShippingAmount();
        $this->_sourceStringArray[11] =  [$this->_currency];
        $this->_simpleLiveUpdate->setField("ORDER_SHIPPING", $shippingAmount);
        $this->_sourceStringArray[10] =  [$shippingAmount];
        
        /** Payment site languge */
        $resolver = $this->_objectManager->get('Magento\Framework\Locale\Resolver');
        $language = strstr($resolver->getLocale(), '_', true);
        $this->_simpleLiveUpdate->setField("LANGUAGE", $language);
        
        $this->_setItems($order->getItems());
        $this->_setBillingData($order->getBillingAddress());
        $this->_setShippingAddress($order->getShippingAddress());
        ksort($this->_sourceStringArray);
        $hash = $this->_calculateHash();
        $order->setOtpSimpleHash($hash);
        $order->save();
    }
    
    /**
     * Set Items
     * 
     * @param array $items
     */
    private function _setItems($items)
    {
        $this->_sourceStringArray[4] = [];
        $this->_sourceStringArray[5] = [];
        //$this->_sourceStringArray[6] = [];
        $this->_sourceStringArray[7] = [];
        $this->_sourceStringArray[8] = [];
        $this->_sourceStringArray[9] = [];
        
        foreach ($items as $item) {
            if ($item->getPrice() != 0) {
                $product = [
                    'name' => $item->getName(),
                    'code' => $item->getSku(),
                    //'info' => $item->getDescription(),
                    'price' => $this->_currency == 'HUF' ? intval($item->getPriceInclTax()) : $item->getPriceInclTax(),
                    'vat' => 0,
                    'qty' => $item->getQtyOrdered(),
                ];
                $this->_sourceStringArray[4][] = $product['name'];
                $this->_sourceStringArray[5][] = $product['code'];
                //$this->_sourceStringArray[6][] = $product['info'];
                $this->_sourceStringArray[7][] = $product['price'];
                $this->_sourceStringArray[8][] = $product['qty'];
                $this->_sourceStringArray[9][] = $product['vat'];
                
                $this->_simpleLiveUpdate->addProduct($product);
            }
        }
    }
    
    /**
     * Set Billing Data
     * 
     * @param type $billingAddress
     */
    private function _setBillingData(\Magento\Sales\Model\Order\Address $address)
    {
        $this->_simpleLiveUpdate->setField("BILL_FNAME", $address->getFirstname());
        $this->_simpleLiveUpdate->setField("BILL_LNAME", $address->getLastname());
        $this->_simpleLiveUpdate->setField("BILL_EMAIL", $address->getEmail()); 
        $this->_simpleLiveUpdate->setField("BILL_PHONE", $address->getTelephone());
        $this->_simpleLiveUpdate->setField("BILL_COMPANY", $address->getCompany());          		
        //$this->_simpleLiveUpdate->setField("BILL_FISCALCODE", " ");                  	
        $this->_simpleLiveUpdate->setField("BILL_COUNTRYCODE", $address->getCountryId());
        $this->_simpleLiveUpdate->setField("BILL_STATE", $address->getRegion());
        $this->_simpleLiveUpdate->setField("BILL_CITY", $address->getCity()); 
        $this->_simpleLiveUpdate->setField("BILL_ADDRESS", $address->getStreet()); 
        //$this->_simpleLiveUpdate->setField("BILL_ADDRESS2", "Second line address");
        $this->_simpleLiveUpdate->setField("BILL_ZIPCODE", $address->getPostcode());
    }
    
    /**
     * Set Shipping Address
     * 
     * @param \Magento\Sales\Model\Order\Address $address
     */
    private function _setShippingAddress(\Magento\Sales\Model\Order\Address $address)
    {
        $this->_simpleLiveUpdate->setField("DELIVERY_FNAME", $address->getFirstname()); 
        $this->_simpleLiveUpdate->setField("DELIVERY_LNAME", $address->getLastname()); 
        $this->_simpleLiveUpdate->setField("DELIVERY_EMAIL", $address->getEmail()); 
        $this->_simpleLiveUpdate->setField("DELIVERY_PHONE", $address->getTelephone()); 
        $this->_simpleLiveUpdate->setField("DELIVERY_COUNTRYCODE", $address->getCountryId());
        $this->_simpleLiveUpdate->setField("DELIVERY_STATE", $address->getRegion());
        $this->_simpleLiveUpdate->setField("DELIVERY_CITY", $address->getCity());
        $this->_simpleLiveUpdate->setField("DELIVERY_ADDRESS", $address->getStreet()); 
        //$this->_simpleLiveUpdate->setField("DELIVERY_ADDRESS2", "Second line address");
        $this->_simpleLiveUpdate->setField("DELIVERY_ZIPCODE", $address->getPostcode());
    }
    
    /**
     * Calculate Hash
     * 
     * @return string
     */
    private function _calculateHash()
    {
        $sourceString = '';
        foreach ($this->_sourceStringArray as $sources) {
            foreach ($sources as $source) {
                $sourceString .= strlen($source).$source;
            }
        }
        
        return $this->_helper->calculateHash($sourceString);
    }

        /**
     * Redirect
     */
    public function redirect()
    {
        $display = $this->_simpleLiveUpdate->createHtmlForm('SinglePayForm', 'auto');
        echo '<div style="display: none;">'.$display.'</div>';
    }
}
