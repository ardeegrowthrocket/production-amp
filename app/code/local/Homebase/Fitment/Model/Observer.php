<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/24/17
 * Time: 11:47 AM
 */

class Homebase_Fitment_Model_Observer{
    public function addMultiCheckboxAttributeType($observer){
        /** @var Varien_Object $response */
        $response = $observer->getResponse();
        $types = $response->getTypes();

        $types[] = array(
            'value' => 'multicheck',
            'label' => Mage::helper('hfitment')->__('Multi Checkbox'),
            'hide_fields' => array(
                'is_configurable',
                'frontend_class',
                '_default_value',

            ),
        );
        $response->setTypes($types);
        return $this;
    }

    public function assignBackendModelToAttribute($observer){
        $backendModel = 'eav/entity_attribute_backend_array';
        $object = $observer->getAttribute();
        if($object->getFrontendInput() == 'multicheck'){
            $object->setBackendModel($backendModel);
        }
    }
    public function setMultiCheckboxInForm($observer){

    }
    public function updateElementTypes($observer){
        /** @var Varien_Object $response */
        $response = $observer->getResponse();
        $types = $response->getTypes();
        $types['multicheck'] = Mage::getConfig()->getBlockClassName('hfitment/element_multicheckbox');
        $response->setTypes($types);
        return $this;
    }
    public function setMulticheckboxRendererInForm($observer){
        /** @var Varien_Data_Form $form */
        $form = $observer->getForm();
        /** @var Homebase_Fitment_Model_Multicheckbox $multichecks */
        $multichecks = Mage::getResourceSingleton('hfitment/multicheckbox');
        $codes = $multichecks->getMulticheckboxAttributeCodes();
    }

    /**
     * Process product save after events
     *
     * @param $observer Varien_Event_Observer
     */
    public function saveProductAfter($observer){
        /** @var Homebase_Autopart_Model_Product $product */
        $product = $observer->getProduct();
        /** @var Mage_Core_Controller_Request_Http $_request */
        $request = $observer->getRequest();

        /** @var Homebase_Fitment_Helper_Data $helper */
        $helper = Mage::helper('hfitment');
        /** @var Homebase_Fitment_Helper_Fitment_Data $helperData */
        $helperData = Mage::helper('hfitment/fitment_data');

        $fitmentCombinations = $request->getParam('product_combinations',null);
        $fitmentData = json_decode($fitmentCombinations,true);
        $fitmentArray = array();
        $originalFitment = $helper->fetchFitmentCollection($product->getId());
        foreach($fitmentData as $fitmentDatum){
            if(array_key_exists('serial', $fitmentDatum) && !array_key_exists('delete', $fitmentDatum)){
                $fitmentContent = $fitmentDatum['serial'];
                $fitmentItem = array_splice($fitmentContent, 0, 4);
                array_push($fitmentArray, implode('-',$fitmentItem));
            }
        }
        //Set products fitment based on the retrieved product_combination form values.
        $product->setData('fitment', $fitmentArray);
        $product->setOrigData('fitment',$originalFitment);

        if($product instanceof Homebase_Autopart_Model_Product){
            if($product->hasFitmentDataChanged()){
                //Process fitment and insert/update to auto_combination_list
                $removedFitment = $product->getRemovedFitment();
                $newFitment = $product->getNewFitment();
//                Mage::log('Removing fitment', null, 'removedfitment.log',true);
                if(!is_null($fitmentCombinations)){
                    $helperData->removeFitment($removedFitment, $product->getId());
                }

                $helperData->insertFitment($newFitment,$product->getId(), $product);
                //Run reindex
                //Remove Affected Routes
                if(count($removedFitment) > 0){
                    $autotypes = $product->getData('auto_type');
                    $partname = $product->getData('part_name');
//                    $helperData->generateCompleteFitment($removedFitment, $autotypes,$partname);
                }
                if(count($newFitment) > 0){
                    
                }
                $_assocBuilder = Mage::getModel('hautopart/observer');
//                $_assocBuilder->buildAssocations();
            }
        }

        if($product->dataHasChangedFor('part_name')){
            //Do Reindex
        }
        if($product->dataHasChangedFor('auto_type')){
            //Do Reindex
        }
    }
    /**
     * Assign product id to newly created product. Since product id is not available on
     * catalog_product_prepare_save event, use the
     * catalog_product_save_after event to fetch the product id
     *
     * @param $observer
     */
    public function saveProductModelAfter($observer){
        $_product = $observer->getProduct();
        if($_product && $_product->getEntityId()){
            /** @var Homebase_Fitment_Helper_Fitment_Data $helperData */
            $helperData = Mage::helper('hfitment/fitment_data');
            $helperData->updateFitmentWithProductId($_product->getSku(),$_product->getEntityId());
        }

    }

    /**
     * Set original fitment of the product after the product's model load after.
     * Before changes are made on the product, fitments are attached as original values waiting to be
     * compared to newly added values.
     *
     * @param $observer
     */
    public function doLoadAfter($observer){
        /** @var Homebase_Autopart_Model_Product $object */
        $object = $observer->getDataObject();
        /** @var Homebase_Fitment_Helper_Data $helper */
        $helper = Mage::helper('hfitment');
        if($object instanceof Mage_Catalog_Model_Product){
            if($object->getTypeId() == Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID){
//                $fitment = $helper->fetchFitmentCollection($object->getId());
//                $object->setData('fitment', $fitment);
//                Mage::log($object->getOrigData('fitment'), null, 'save2.log',true);
            }
        }
    }
}