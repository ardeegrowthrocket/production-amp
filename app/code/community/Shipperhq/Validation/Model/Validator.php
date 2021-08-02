<?php
/**
 * Magento
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
 * @category   Mage
 * @package    Mage_Shipping
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
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

include_once 'ShipperHQ/WS/Client/WebServiceClient.php';
include_once 'ShipperHQ/WS/Response/ErrorMessages.php';

class Shipperhq_Validation_Model_Validator extends Mage_Core_Model_Abstract
{
    public $valdiator;

    protected $_validationRequest;

    /**
     *
     * Validate address, if required
     * @param $address
     */
    public function validateAddress($address)
    {
        $validatorMapper = $this->getValidator();
        $results = new Varien_Object();
        if($validatorMapper) {
            $this->setRequest($address);
            $response = $this->requestValidate();
            if($response && $response['result']) {
                $results = $this->parseResponse($response['result']);
                if($results->getCandidates()) {
                    $candidateAddresses = $results->getCandidates();
                    if (count($candidateAddresses) > 0) {
                        $serialisedCandidates = Mage::helper('core')->jsonEncode($candidateAddresses);
                        Mage::register('candidate_addresses', $serialisedCandidates, true);
                    }
                }
            }
        }
        else {
            $results->outcome = Shipperhq_Validation_Model_Validator_Result::ERROR;
        }

        return $results;

    }

    protected function parseResponse($response)
    {
        $results = new Varien_Object;

        $debugRequest = $this->_validationRequest;


        $debugRequest->credentials = null;
        $debugData = array('request' => $debugRequest, 'response' => $response);

        if (!is_object($response)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Validation', 'Shipper HQ did not return a response to validation request',
                    $debugData);
            }

            return $results;
        }
        elseif(!empty($response->errors)) {
            if (Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postInfo('Shipperhq_Validation', 'Shipper HQ Validation returned an error',
                    $debugData);
            }
            $results->outcome = Shipperhq_Validation_Model_Validator_Result::ERROR;
            return $results;
        }

        if(isset($response->validationStatus)) {
            $results->outcome = $response->validationStatus;
        }
        else {
            $results->outcome = Shipperhq_Validation_Model_Validator_Result::NOT_VALIDATED;
        }

        if(isset($response->suggestedAddresses)) {
            $candidateCount = count($response->suggestedAddresses);

            if ($candidateCount > 0) {
                foreach ($response->suggestedAddresses as $possAddress){
                    $address = array();
                    $address['address_line'] 		= (string)$possAddress->street;
                    $address['address_line_2'] 		= (string)$possAddress->street2;
                    $address['city'] 			    = (string)$possAddress->city;
                    $address['state'] 			    =  Mage::getModel('directory/region')->loadByCode(
                        (string)$possAddress->region,
                        (string)$possAddress->country)
                        ->getId();
                    $address['postcode_primary'] 	= (string)$possAddress->zipcode;
                    $address['country_code'] 		= (string)$possAddress->country;
                    $address['state_name']          = Mage::getModel('directory/region')->loadByCode(
                        (string)$possAddress->region,
                        (string)$possAddress->country)
                        ->getName();
                    $address['destination_type'] = (string)$possAddress->addressType == 'UNKNOWN' ? null: (string)$possAddress->addressType;
                    $candidateAddresses[] = $address;
                }
                $results->setDestinationType(strtolower($address['destination_type']));
                $results->setCandidates($candidateAddresses);
                $results->setCandidateCount(count($candidateAddresses));
            }
        }

        if (Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Shipper', 'Validation request and result', $debugData);
        }
        return $results;
    }

    protected function setRequest($address)
    {
        $this->_validationRequest = $this->getValidator()->getTranslation($address);
        return $this;

    }

    protected function requestValidate()
    {
        $requestString = serialize($this->_validationRequest);
        $debugData = array('request' => $requestString);
        $timeout = Mage::helper('shipperhq_shipper')->getWebserviceTimeout();
        $resultSet = $this->_getShipperInstance()->sendAndReceive($this->_validationRequest,
            Mage::helper('shipperhq_shipper')->getValidationGatewayUrl(), $timeout);
        $debugData['result'] = $resultSet;

        return $resultSet;

    }

    /**
     * Initialise shipper library class
     *
     * @return null|Shipper_Shipper
     */
    protected function _getShipperInstance()
    {
        if (empty($this->_shipperWSInstance)) {
            $this->_shipperWSInstance = new \ShipperHQ\WS\Client\WebServiceClient();
        }
        return $this->_shipperWSInstance;
    }


    public function getResultDisplay($results, $prependId, $layout = null)
    {
        $html = '';

        if(($results->getOutcome() == Shipperhq_Validation_Model_Validator_Result::VALID_CORRECTED ||
                $results->getOutcome() == Shipperhq_Validation_Model_Validator_Result::AMBIGUOUS
            )
            && count($results->getCandidates()) > 0 ) {
            $html = $this->getCandidateHtmlSelect($results->getCandidates(),$prependId, $layout);
        }
        elseif($results->getOutcome() ==  Shipperhq_Validation_Model_Validator_Result::INVALID) {
            $html = $this->getInvalidHtml();

        }
        return $html;

    }

    public function getCandidateHtmlSelect($candidateOptions, $prependId, $layout)
    {

        $options = array();
        $starterText = Mage::helper('shipperhq_validation')->__('Please select an address');
        $options = array('-1' => $starterText);

        if(!empty($candidateOptions)) {
            foreach($candidateOptions as $candidate) {
                $key = $candidate['address_line'] . '|' . $candidate['address_line_2'] . '|' . $candidate['city'] . '|' .
                    $candidate['state'] . '|' . $candidate['postcode_primary'] . '|' . $candidate['country_code'] . '|' . $candidate['destination_type'];

                $options[$key] = $candidate['address_line'] . ' ' . $candidate['address_line_2'] . ' ' . $candidate['city']
                    . ' ' . $candidate['state_name'] . ' ' . $candidate['postcode_primary'] . ' ' . $candidate['country_code'];
            }


        }
        $select = $layout->createBlock('core/html_select')
            ->setName('[candidate_addresses]')
            ->setId($prependId.'candidates')
            ->setTitle('Candidate Addresses')
            ->setClass('validate-select')
            ->setExtraParams('') //onchange="candidateSwitcher()"
            ->setOptions($options);
        return $select->getHtml();
    }

    public function getInvalidHtml() {

        $html = '<div class="invalid_address_text">'
            .Mage::helper('shipperhq_validation')->__('Your entered address could not be validated')
            .'</div>';

        return $html;
    }

    public function getJqueryHtml($name) {
        $js = '<script type="text/javascript">'.
            '$j( "#checkout-'.$name.'-load" ).dialog("open")'.
            '</script>';
        return $js;
    }

    protected function getValidator()
    {
        if(is_null($this->valdiator)) {
            $this->valdiator = Mage::getSingleton('Shipperhq_Validation_Model_Validator_ValidationMapper');
        }
        return $this->valdiator;
    }

}