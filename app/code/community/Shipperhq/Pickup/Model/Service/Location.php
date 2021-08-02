<?php

class Shipperhq_Pickup_Model_Service_Location
{
    public function findLocations($quote, $parameters)
    {
        $selectedLocation = $parameters['location_id'];
        $carriergroupId = $parameters['carriergroup_id'];
        $dateSelected = $parameters['date_selected'];
        $loadOnly = $parameters['load_only'];
        $persistDate = $parameters['persist_date'];
        $carrierCode = $parameters['carrier_code'];
        $carrierType = $parameters['carrier_type'];
        $isOsc = array_key_exists('is_osc', $parameters) ? $parameters['is_osc'] == 'true' : false;
        $passedInCarriergroupId = $carriergroupId;

        //Multiaddress checkout support
        $addressId = false;
        Mage::helper('shipperhq_shipper')->extractAddressIdAndCarriergroupId($addressId, $carriergroupId);

        $resultSet = array();
        $resultSet['date_selected'] = $dateSelected;
        if(empty($dateSelected)) {
            $resultSet['date_selected'] = $persistDate;
        }
        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Pickup', 'Store pickup retrieving location data'
                ,array('location_id' => $selectedLocation, 'carrierGroup ID' => $carriergroupId,
                    'date_selected' =>$dateSelected, 'carrier code' =>$carrierCode, 'load only ' => $loadOnly,
                    'carrier type' => $carrierType));
        }
        $location = Mage::getModel('shipperhq_pickup/location');
        $location->getLocationResults(
            $quote, $selectedLocation, $carriergroupId, $carrierCode, 
            $dateSelected, $carrierType, $loadOnly, $addressId, $resultSet, $isOsc
        );

        $resultSet['carriergroup_id'] = $passedInCarriergroupId;
        $resultSet['carrier_code'] = $carrierCode;

        if(Mage::helper('shipperhq_pickup')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Pickup', 'Returning rates to checkout ',$resultSet);
        }
        
        return $resultSet;
    }
}