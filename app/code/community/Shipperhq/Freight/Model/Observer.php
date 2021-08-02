<?php
/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */

class Shipperhq_Freight_Model_Observer extends Mage_Core_Model_Abstract
{
    /*
     * Refresh carriers in configuration pane
     *
     */
    public function hookToControllerActionPreDispatch($observer)
    {
        $actionName = $observer->getEvent()->getControllerAction()->getFullActionName();

        $actionNames = array('checkout_cart_estimatePost','checkout_cart_index',
            'checkout_cart_updatePost','checkout_cart_delete');

        //we compare action name to see if that's action for which we want to add our own event
        if (in_array($actionName, $actionNames)) {
            switch($actionName) {
                case 'checkout_cart_updatePost':
                case 'checkout_cart_delete':
                case 'checkout_cart_index':
                 //   $this->getFreightAccessorials($observer);
                    break;
                case 'checkout_cart_estimatePost':
                    $this->processFreightAccessorials($observer);
                    break;
            }
        }

    }

    public function saveShippingOptionsSelected($observer)
    {
        $shippingmethod = $observer->getShippingMethod();
        $shippingAddress = $observer->getShippingAddress();
        $params = $observer->getParams();
        $carrierGroupInfo = $shippingAddress->getCarriergroupShippingDetails();
        $carrierGroupShippingDetail = Mage::helper('shipperhq_shipper')->decodeShippingDetails($carrierGroupInfo);

        //SHQ16-1605
        $this->saveAccessorialsToCarrierGroupData($carrierGroupShippingDetail,
            $shippingmethod, $params);

        $encodedDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($carrierGroupShippingDetail);
        $shippingAddress->setCarriergroupShippingDetails($encodedDetails);
        $shippingAddress->save();
    }

    public function saveOptionsSelectedInAdmin($observer)
    {
        $requestData = $observer->getRequest();
        $orderData = array();

        if (isset($requestData['order'])) {
            $orderData = $requestData['order'];
            if(isset($requestData['shipping_method_flag'])) {
                $orderData = $requestData;
            }
        }
        if ($orderData
            && !empty($orderData['shipping_method_flag'])
            && !empty($orderData['shipping_method'])) {
            $shippingmethod = $orderData['shipping_method'];
            $quote = $observer->getOrderCreateModel();
            $shippingAddress = $quote->getShippingAddress();
            //SHQ16-1605
            $carrierGroupInfo = $shippingAddress->getCarriergroupShippingDetails();
            $carrierGroupShippingDetail = Mage::helper('shipperhq_shipper')->decodeShippingDetails($carrierGroupInfo);
            $this->saveAccessorialsToCarrierGroupData($carrierGroupShippingDetail,
                $shippingmethod, $orderData);
            $encodedDetails = Mage::helper('shipperhq_shipper')->encodeShippingDetails($carrierGroupShippingDetail);
            $shippingAddress->setCarriergroupShippingDetails($encodedDetails);

            $options = Mage::helper('shipperhq_freight')->getAllPossibleOptions();
            $carrierCodeSplit = explode('_', $shippingmethod);
            $carrierCode = $carrierCodeSplit[0];
            foreach($options as $optionCode) {
                if (array_key_exists($optionCode  .'_' .$carrierCode, $orderData)) {
                    $shippingAddress->setData($optionCode, $orderData[$optionCode  .'_' .$carrierCode]);
                }
            }
            $shippingAddress->save();
        }
    }

    protected function getFreightAccessorials($observer)
    {
        $quote = $this->getQuote();
        $response = Mage::getSingleton('shipperhq_freight/service_accessorials')->retrieveAccessorials($quote);


    }

    protected function processFreightAccessorials($observer)
    {
        $request = $observer->getControllerAction()->getRequest();
        $country = (string)$request->getParam('country_id');
        $postcode = (string)$request->getParam('estimate_postcode');
        $city = (string)$request->getParam('estimate_city');
        $regionId = (string)$request->getParam('region_id');
        $region = (string)$request->getParam('region');
        $params = $request->getParams();
        $shipAddress =   $this->getQuote()->getShippingAddress();

        $shipAddress->setCountryId($country)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setRegionId($regionId)
            ->setRegion($region)
            ->setCollectShippingRates(true);

        $selectedFreightOptions = Mage::helper('shipperhq_shipper')->getQuoteStorage()->getSelectedFreightCarrier();
        $allAccessorials = Mage::helper('shipperhq_freight')->getAllPossibleOptions();
        foreach($allAccessorials as $accessorial_code) {
            $value = array_key_exists($accessorial_code, $params) ? $params[$accessorial_code] : null;
            if(!is_null($value)) {
                $selectedFreightOptions[$accessorial_code] = $value;
                $shipAddress->setData($accessorial_code, $value);
            }
        }
        Mage::helper('shipperhq_shipper')->getQuoteStorage()->setSelectedFreightCarrier($selectedFreightOptions);
    }

    protected function getQuote()
    {
        return $this->getCart()->getQuote();
    }

    protected function getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

  /*  protected function _getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    protected function _getAdminQuote()
    {
        return $this->_getAdminSession()->getQuote();
    }*/

  protected function saveAccessorialsToCarrierGroupData(&$carrierGroupShippingDetail, $shippingmethod, $params)
  {
      $options = Mage::helper('shipperhq_freight')->getAllPossibleOptions();
      $carrierCodeSplit = explode('_', $shippingmethod);
      $carrierCode = $carrierCodeSplit[0];

      foreach($carrierGroupShippingDetail as $key => $shipDetail) {
          //SHQ16-1605 handle merged rates accessorials
          if ($carrierCode == 'multicarrier') {
              $subcarriercode = $shipDetail['carrier_code'];
              $carrierGroupAccessorials = Mage::helper('shipperhq_freight')->getFreightAccessorials(
                  $shipDetail['carrierGroupId'], $subcarriercode);
              if ($carrierGroupAccessorials) {
                  foreach ($carrierGroupAccessorials as $accessorial) {
                      if (isset($params[$accessorial['code'] . '_' . $carrierCode])) {
                          $shipDetail[$accessorial['code']] = $params[$accessorial['code'] . '_' . $carrierCode];
                      }
                  }
              }
          }
          else {
              if($shipDetail['carrier_code'] != $carrierCode) {
                  continue;
              }
              foreach($options as $optionCode) {
                  if(isset($params[$optionCode .'_' .$carrierCode])) {
                      $shipDetail[$optionCode] = $params[$optionCode .'_' .$carrierCode];
                  }

              }
          }
          $carrierGroupShippingDetail[$key] = $shipDetail;
      }
  }

}