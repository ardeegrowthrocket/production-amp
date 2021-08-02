<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 5/28/17
 * Time: 11:52 PM
 */

class Homebase_Auto_Model_Observer{

    protected $_excludeStores = array('lfp','mopar','sop','mop','spp','mogp','base');

    /**
     * @param Varien_Event_Observer $observer
     */
    public function doCatalogProductSaveAfter($observer){
        /** @var Homebase_Autopart_Model_Product $_product */
        $_product = $observer->getProduct();
        if($_product->getTypeId() == Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID){
            if($_product->dataHasChangedFor('auto_type')){
                //$this->doHandleAutoTypeChanges($_product);
            }
        }
    }
    /**
     * @param Varien_Event_Observer $observer
     * @Deprecated
     */
    public function doAutoFitmentSaveAfter($observer){
        /** @var Homebase_Autopart_Model_Mix $_mix */
        $_mix = $observer->getObject();
        $ymmSerial = serialize($_mix->toArray(array('year','make','model')));
        $makeSerial = serialize($_mix->toArray(array('make')));
        $makeModelSerial = serialize($_mix->toArray(array('make','model')));
        $_product = Mage::getModel('catalog/product')->load($_mix->getProductId());
        $categories = array();
        if($_product->hasData('auto_type')){
            $categories = explode(',',$_product->getAutoType());
        }
        /** @var Homebase_Auto_Model_Resource_Index_Combination $_collection */
        $_collection = Mage::getResourceSingleton('hauto/index_combination');
        $_collection->buildSerial($ymmSerial);
        $_collection->buildSerial($makeSerial,'make');
        $_collection->buildSerial($makeModelSerial,'model');
        $filteredCategories = array_filter($categories);
        /** @var Homebase_Auto_Helper_Path $_pathHelper */
        $_pathHelper = Mage::helper('hauto/path');
        if(count($filteredCategories) > 0 ){
            foreach($filteredCategories as $categoryId){
                $fitmentCatCombo = $_mix->toArray(array('year','make','model'));
                $fitmentCatCombo['category'] = $categoryId;
                $ymmcatSerial = serialize($fitmentCatCombo);
                $_collection->buildSerial($ymmcatSerial,'cat');
            }
        }
        $_assocBuilder = Mage::getModel('hautopart/observer');
//        $_assocBuilder->buildAssocations();
    }
    /**
     * @param Varien_Event_Observer $observer
     * @Deprecated
     */
    public function doAutoFitmentDeleteAfter($observer){
        $_mix = $observer->getObject();
        $_collection = Mage::getResourceSingleton('hauto/index_combination');
        $_collection->rebuildSerial($_mix);
        $_assocBuilder = Mage::getModel('hautopart/observer');
//        $_assocBuilder->buildAssocations();
    }
    /**
     * @param Homebase_Autopart_Model_Product $_product
     */
    private function doHandleAutoTypeChanges($_product){
        /** @var string $autotype 267,258 */
        $newAutotype = explode(',',$_product->getData('auto_type'));
        $oldAutotype = explode(',',$_product->getOrigData('auto_type'));
        $insert = array_diff($newAutotype, $oldAutotype);
        $remove = array_diff($oldAutotype,$newAutotype);
        /** @var Homebase_Auto_Model_Resource_Index_Combination $_IdxCollection */
        $_IdxCollection = Mage::getResourceSingleton('hauto/index_combination');
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_collection */
        $_collection = Mage::getModel('hautopart/mix')->getCollection()
            ->addFieldToFilter('product_id',$_product->getId());
        if(count($insert)>0){
            /** @var Homebase_Autopart_Model_Mix $_item */
            foreach($_collection as $_item){
                $combination = $_item->toArray(array('year','make','model'));
                foreach($insert as $newItem){
                    if($newItem !=''){
                        $combination['category'] = $newItem;
                        $serial = serialize($combination);
                        $_IdxCollection->buildSerial($serial,'cat');
                    }
                }
            }
        }
        if(count($remove)>0){
            foreach($remove as $deleteItem){
                if($deleteItem !=''){
                    foreach($_collection as $_item){
                        $combination = $_item->toArray(array('year','make','model'));
                        $combination['category'] = $deleteItem;
                        $_IdxCollection->removeSerial(serialize($combination));
                    }
                }
            }
        }
    }

    /**
     * @param $observer Varien_Event_Observer
     */
    public function doCoreBlockAbstractToHtmlAfter($observer){

        $_block = $observer->getBlock();
        /** @var Homebase_Auto_Helper_Path $_helper */
        $_helper = Mage::helper('hauto/path');
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $_block->getRequest();
        $module = $_request->getControllerModule();
        $controller = $_request->getControllerName();
        $action = $_request->getActionName();
        if($_block instanceof Homebase_Auto_Block_Product_Listing){
            if($module == "Homebase_Auto" && $controller == 'partmake' && $action == 'index'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);
                $params = unserialize($_request->getParam('ymm_params'));
                $make = $_helper->getRawOptionText('make',$params['make']);
                $productNames = $domXpath->query('//h2[@class="product-name"]/a');
                /** @var DOMElement $anchor */
                foreach($productNames as $anchor){
                    $productName = $anchor->nodeValue;
//                    $title =  htmlentities($make) . ' ' . trim(htmlentities($productName));
                    $title = sprintf('%s %s', htmlentities($make),trim(htmlentities($productName)));
                    $anchor->nodeValue = $title;
                    $anchor->setAttribute('title',$title);
                }
                $transport['html'] = $_dom->saveHTML();
            }
            else if($module == "Homebase_Auto" && $controller == 'partmodel' && $action == 'index'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);

                $params = unserialize($_request->getParam('ymm_params'));
                $make = $_helper->getRawOptionText('make',$params['make']);
                $model = $_helper->getRawOptionText('model',$params['model']);

                $productNames = $domXpath->query('//h2[@class="product-name"]/a');

                /** @var DOMElement $anchor */
                foreach($productNames as $anchor){
                    $productName = $anchor->nodeValue;
                    $title = sprintf('%s %s %s', htmlentities($make),htmlentities($model), trim(htmlentities($productName)));
                    $anchor->nodeValue = $title;
                    $anchor->setAttribute('title', $title);
                }
                $transport['html'] = $_dom->saveHTML();
            }
            else if($module == "Homebase_Auto" && $controller == 'partymm' && $action == 'index'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);
                $params = unserialize($_request->getParam('ymm_params'));
                $make = $_helper->getRawOptionText('make',$params['make']);
                $model = $_helper->getRawOptionText('model',$params['model']);
                $year = $_helper->getRawOptionText('year',$params['year']);
                $friendlyMake = $_helper->getOptionText('make',$params['make']);
                $friendlyModel = $_helper->getOptionText('model',$params['model']);
                $productNames = $domXpath->query('//h2[@class="product-name"]/a');
                /** @var DOMElement $anchor */
                foreach($productNames as $anchor){
                    $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'sku-ymm/';
                    $productName = $anchor->nodeValue;
                    $title = sprintf('%s %s %s %s',htmlentities($year),htmlentities($make),htmlentities($model),trim(htmlentities($productName)));
                    $anchor->nodeValue = $title;
                    $anchor->setAttribute('title',$title);
                    $paths = pathinfo($anchor->getAttribute('href'));
                    if(array_key_exists('filename',$paths)){
                        $url.=sprintf('%s-%s-%s/%s.html',$year,$friendlyMake,$friendlyModel,$paths['filename']);
                    }
                    $anchor->setAttribute('href',$url);
                }
                $moreInfos = $domXpath->query('//a[@class="addtocart"]');
                /** @var DOMElement $element */
                foreach($moreInfos as $element){
                    $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'sku-ymm/';
                    $oldLink = $element->getAttribute('href');
                    $paths = pathinfo($oldLink);
                    if(array_key_exists('filename',$paths)){
                        $url.=sprintf('%s-%s-%s/%s.html',$year,$friendlyMake,$friendlyModel,$paths['filename']);
                    }
                    $element->setAttribute("href", $url);
                }

                $transport['html'] = $_dom->saveHTML();
            }
        }
        if($_block instanceof Mage_Catalog_Block_Product_Price && Mage::helper('mobiledetect')->isMobile()){

            if($module == 'Homebase_Auto' && $controller == 'part' && $action == 'index') {
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);

                $priceBoxes = $domXpath->query('//div[@class="price-box"]');
                /** @var DOMElement $node */
                foreach ($priceBoxes as $node) {
                    $productId = $node->getAttribute('data-product');
                    $_product = Mage::getModel('catalog/product')->load($productId);
                    $partNumber = $_product->getData('amp_part_number');

                    $element = $_dom->createElement('div');
                    $anchor = $_dom->createElement('a');

                    $anchor->setAttribute('href', $_helper->generateLink(strtolower($partNumber), 'sku'));
                    $anchor->setAttribute('rel', 'nofollow');
                    $element->setAttribute('class', 'part-container');

                    $element->nodeValue = 'Part Number: ';
                    $anchor->nodeValue = $partNumber;

                    $element->appendChild($anchor);
                    $node->appendChild($element);
                }
                $transport['html'] = $_dom->saveHTML();
            }
            else if($module == "Homebase_Auto" && $controller == 'partmake' && $action == 'index'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);

                $priceBoxes = $domXpath->query('//div[@class="price-box"]');
                /** @var DOMElement $node */
                foreach ($priceBoxes as $node) {
                    $productId = $node->getAttribute('data-product');
                    $_product = Mage::getModel('catalog/product')->load($productId);
                    $partNumber = $_product->getData('amp_part_number');

                    $params = unserialize($_request->getParam('ymm_params'));
                    $make = $_helper->getRawOptionText('make',$params['make']);

                    $element = $_dom->createElement('div');
                    $anchor = $_dom->createElement('a');
                    $div = $_dom->createElement('div',sprintf('Fits: %s Models',htmlentities($make)));
                    $div->setAttribute('class','make-fitment');

                    $anchor->setAttribute('href', $_helper->generateLink(strtolower($partNumber), 'sku'));
                    $anchor->setAttribute('rel','nofollow');
                    $element->setAttribute('class', 'part-container');

                    $element->nodeValue = 'Part Number: ';
                    $anchor->nodeValue = $partNumber;


                    $element->appendChild($anchor);
                    $node->appendChild($element);
                    $node->appendChild($div);
                }
                $transport['html'] = $_dom->saveHTML();
            }
            else if($module == "Homebase_Auto" && $controller == 'partmodel' && $action == 'index'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);

                $priceBoxes = $domXpath->query('//div[@class="price-box"]');
                /** @var DOMElement $node */
                foreach ($priceBoxes as $node) {
                    $productId = $node->getAttribute('data-product');
                    $_product = Mage::getModel('catalog/product')->load($productId);
                    $partNumber = $_product->getData('amp_part_number');

                    $params = unserialize($_request->getParam('ymm_params'));
                    $make = $_helper->getRawOptionText('make',$params['make']);
                    $model = $_helper->getRawOptionText('model',$params['model']);

                    $element = $_dom->createElement('div');
                    $anchor = $_dom->createElement('a');
                    $div = $_dom->createElement('div',sprintf('Fits: %s %s',htmlentities($make),htmlentities($model)));
                    $div->setAttribute('class','make-fitment');

                    $anchor->setAttribute('href', $_helper->generateLink(strtolower($partNumber), 'sku'));
                    $anchor->setAttribute('rel', 'nofollow');
                    $element->setAttribute('class', 'part-container');

                    $element->nodeValue = 'Part Number: ';
                    $anchor->nodeValue = $partNumber;

                    $element->appendChild($anchor);
                    $node->appendChild($element);
                    $node->appendChild($div);
                }
                $transport['html'] = $_dom->saveHTML();

            }
            else if($module == "Homebase_Auto" && $controller == 'partymm' && $action == 'index'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);
                $priceBoxes = $domXpath->query('//div[@class="price-box"]');
                /** @var DOMElement $node */
                foreach ($priceBoxes as $node) {
                    $productId = $node->getAttribute('data-product');
                    $_product = Mage::getModel('catalog/product')->load($productId);
                    $partNumber = $_product->getData('amp_part_number');

                    $params = unserialize($_request->getParam('ymm_params'));
                    $make = $_helper->getRawOptionText('make',$params['make']);
                    $model = $_helper->getRawOptionText('model',$params['model']);
                    $year = $_helper->getRawOptionText('year', $params['year']);

                    $element = $_dom->createElement('div');
                    $anchor = $_dom->createElement('a');
                    $div = $_dom->createElement('div',sprintf('Fits: %s %s %s',$year,htmlentities($make),htmlentities($model)));
                    $div->setAttribute('class','make-fitment');

                    $friendlyMake = $_helper->filterTextToUrl($make);
                    $friendlyModel = $_helper->filterTextToUrl($model);
                    $friendlyYear = $_helper->filterTextToUrl($year);

                    $sku = $_product->getSku();
                    $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'sku-ymm/';

                    $url.=sprintf('%s-%s-%s/%s.html',$friendlyYear,$friendlyMake,$friendlyModel,$sku);

                    $anchor->setAttribute('href', $url);
                    $anchor->setAttribute('rel','nofollow');
                    $element->setAttribute('class', 'part-container');

                    $element->nodeValue = 'Part Number: ';
                    $anchor->nodeValue = $partNumber;

                    $element->appendChild($anchor);
                    $node->appendChild($element);
                    $node->appendChild($div);
                }
                $transport['html'] = $_dom->saveHTML();
            }
            else if(($module == "Smartwave_Ajaxcatalog_CatalogSearch" && $controller == 'result' && $action == 'index') || ($module == "Homebase_Autopart" && $controller == 'model' && $action == 'cat')) {
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);

                $priceBoxes = $domXpath->query('//div[@class="price-box"]');
                /** @var DOMElement $node */
                foreach ($priceBoxes as $node) {
                    $productId = $node->getAttribute('data-product');
                    $_product = Mage::getModel('catalog/product')->load($productId);
                    $partNumber = $_product->getData('amp_part_number');

                    $element = $_dom->createElement('div');
                    $anchor = $_dom->createElement('a');

                    $anchor->setAttribute('href', $_helper->generateLink(strtolower($partNumber), 'sku'));
                    $anchor->setAttribute('rel','nofollow');
                    $element->setAttribute('class', 'part-container');

                    $element->nodeValue = 'Part Number: ';
                    $anchor->nodeValue = $partNumber;

                    $element->appendChild($anchor);
                    $node->appendChild($element);
                }
                $transport['html'] = $_dom->saveHTML();

            }

        }

        if($_block instanceof Mage_Page_Block_Html_Head && !in_array(Mage::app()->getStore()->getWebsite()->getCode(), $this->_excludeStores)){

            /** @var Homebase_Auto_Helper_Data $_dataHelper */
            $_dataHelper = Mage::helper('hauto');
            if($module == 'Homebase_Auto'){
                $transport = $observer->getTransport();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);
                $params = unserialize($_request->getParam('ymm_params'));
                /**
                 * Responsible for changing title elements for the following path
                 * /part/{part-name}.html
                 */
                if($controller == 'part' && $action == 'index') {
                    $partName = $params['part'];
                    $rawPartName = $params['part'];
                    if(substr($params['part'],-1) !== "s"){
                        $partName = $params['part'] . 's';
                    }
                    $resultCount = $_dataHelper->getProductListingCount($_block);
                    $storeCode = $_request->getStoreCodeFromPath();

                    $metaTag = $_dom->createElement('meta');
                    $metaTag->setAttribute('name','description');

                    $metaText = sprintf('Shop for OEM %s & auto parts for your Dodge, Jeep, Chrysler or Ram. Shop from over %s genuine car %s for various models.',$partName,$resultCount,$partName);

                    if($storeCode == Mage::getStoreConfig('hauto/store/code')){
                        $metaText = sprintf("Find Subaru %s at SubaruPartsPros.com", $partName);
                    }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                        $metaText = sprintf("Shop OEM %s parts for your Jeep truck. Wide selection of Jeep %s & accessories for %s, %s & more!", $partName, $partName,'Liberty', 'Commander');
                    }else if($storeCode == Mage::getStoreConfig('hauto/rau/code')){
                        $metaText = sprintf('Shop OEM %s parts for your Ram truck. Wide selection of Ram %s & accessories for ram vehicles! Get yours now!', $rawPartName,$rawPartName);
                    }

                    $metaTag->setAttribute('content', $metaText);
                    /** @var DOMElement $node */
                    $nodes = $domXpath->query('//title');

                    foreach($nodes as $node){
                        if($storeCode == Mage::getStoreConfig('hauto/amp/code')){
                            $node->nodeValue = sprintf('Mopar %s - Genuine Factory Parts - AllMoparParts.com',$partName);
                        }else if ($storeCode == Mage::getStoreConfig('hauto/store/code')){
                            $node->nodeValue = sprintf('Subaru %s - SubaruPartsPros.com',$partName);
                        }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                            $node->nodeValue = sprintf('%s - OEM Jeep Parts | JeepsAreUs',$partName);
                        }else if($storeCode == Mage::getStoreConfig('hauto/rau/code')){
                            $node->nodeValue = sprintf('%s - Ram OEM Parts | RamsAreUs',$rawPartName);
                        }
                    }
                    $headElement = $_dom->getElementsByTagName('head');
                    /** @var DOMElement $head */
                    foreach($headElement as $head){
                        $head->appendChild($metaTag);
                    }
                }else if($controller == 'partmake' && $action == 'index') {
                    $storeCode = $_request->getStoreCodeFromPath();
                    $nodes = $domXpath->query('//title');
                    $partName = $params['part'];
                    if(substr($params['part'],-1) !== "s"){
                        $partName = $params['part'] . 's';
                    }
                    $make = $_helper->getRawOptionText('make',$params['make']);
                    foreach($nodes as $node){
                        if($storeCode == Mage::getStoreConfig('hauto/amp/code')){
                            $node->nodeValue = sprintf('Genuine %s %s by Mopar Factory - AllMoparParts.com',$make,$partName);

                        }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                            $node->nodeValue = sprintf('%s %s - OEM Jeep Parts | JeepsAreUs',$make,$partName);
                        }
                        break;
                    }
                    $models = $_dataHelper->getModelList($_block->getLayout());

                    $metaTag = $_dom->createElement('meta');
                    $metaTag->setAttribute('name','description');
                    if($storeCode == Mage::getStoreConfig('hauto/amp/code') || $storeCode == Mage::getStoreConfig('hauto/store/code')){
                        $metaTag->setAttribute('content',sprintf('Find the best selection of %s %s at discounted pricing. Factory replacement parts for all models including the %s & more!',$make,$partName,$models));
                    }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                        $segment = explode(',', $models);
                        $cleanModels = implode(', ', $segment);
                        $metaTag->setAttribute('content',sprintf('Shop quality %s %s & parts online for your Jeep. JeepsAreUs has a wide selection of %s %s parts for %s & more.',$make,$partName, $make,$partName,$cleanModels));
                    }

                    $headElement = $_dom->getElementsByTagName('head');
                    /** @var DOMElement $head */
                    foreach($headElement as $head){
                        $head->appendChild($metaTag);
                        break;
                    }
                }else if($controller == 'partmodel' && $action == 'index') {
                    $storeCode = $_request->getStoreCodeFromPath();
                    $nodes = $domXpath->query('//title');
                    $partName = $params['part'];
                    if(substr($params['part'],-1) !== "s"){
                        $partName = $params['part'] . 's';
                    }
                    $make = $_helper->getRawOptionText('make',$params['make']);
                    $model = Mage::helper('hautopart/parser')->getLabel($params['model'],'name');
                    $storeCode = $_request->getStoreCodeFromPath();
                    foreach($nodes as $node){
                        if($storeCode == Mage::getStoreConfig('hauto/amp/code')){
                            $node->nodeValue = sprintf('%s %s %s by Mopar Factory - AllMoparParts.com',$make,$model,$partName);
                        }else if($storeCode == Mage::getStoreConfig('hauto/store/code')){
                            $node->nodeValue = sprintf('Subaru %s %s - SubaruPartsPros.com', $model, $partName);
                        }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                            $node->nodeValue = sprintf('%s %s %s - OEM Jeep Parts | JeepsAreUs', $make, $model, $partName);
                        }else{
                            $node->nodeValue = sprintf('%s %s %s - Ram OEM Parts | RamsAreUs', $make, $model, $partName);
                        }
                        break;
                    }
                    $metaTag = $_dom->createElement('meta');
                    $metaTag->setAttribute('name','description');
                    if($storeCode == Mage::getStoreConfig('hauto/amp/code')){
                        $metaTag->setAttribute('content',sprintf('Shop discount %s for your %s %s. We offer genuine OEM car %s & parts for your %s %s. Order online today.',$partName,$make,$model,$partName,$make,$model));
                    }else if($storeCode == Mage::getStoreConfig('hauto/store/code')){
                        $metaTag->setAttribute('content',sprintf('Find Subaru %s %s.', $model,$partName));
                    }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                        $metaTag->setAttribute('content',sprintf('Shop discount %s %s %s & OEM parts. Browse a wide collection of Exterior parts & accessories for your %s.', $make, $model, $partName, $model));
                    }else{
                        $metaTag->setAttribute('content',sprintf('Grab quality %s %s %s & parts online for your Ram. RamsAreUs has a wide selection of parts for ram vehicles & trucks.', $make, $model, $partName));
                    }

                    $headElement = $_dom->getElementsByTagName('head');
                    /** @var DOMElement $head */
                    foreach($headElement as $head){
                        $head->appendChild($metaTag);
                        break;
                    }
                }else if($controller == 'partymm' && $action == 'index') {
                    $storeCode = $_request->getStoreCodeFromPath();
                    $nodes = $domXpath->query('//title');
                    $partName = $params['part'];
                    if(substr($partName,-1) !== 's'){
                        $partName = $params['part'] . 's';
                    }
                    $rawPartName = $params['part'];
                    $storeCode = $_request->getStoreCodeFromPath();
                    $make = $_helper->getRawOptionText('make',$params['make']);
                    $model = Mage::helper('hautopart/parser')->getLabel($params['model'],'name');
                    $year = $_helper->getRawOptionText('year',$params['year']);
                    foreach($nodes as $node){
                        if($storeCode == Mage::getStoreConfig('hauto/amp/code')){
                            $node->nodeValue = sprintf('OEM %s %s %s %s - AllMoparParts.com',$year,$make,$model,$partName);
                        }else if($storeCode == Mage::getStoreConfig('hauto/store/code')){
                            $node->nodeValue = sprintf('%s Subaru %s %s - SubaruPartsPros.com', $year, $model, $partName);
                        }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                            $node->nodeValue = sprintf('%s %s %s %s | JeepsAreUs', $year, $make, $model, $partName);
                        }else{
                            $node->nodeValue = sprintf('%s %s %s %s - Ram OEM Parts | RamsAreUs', $year, $make, $model, $partName);
                        }
                        break;
                    }
                    $metaTag = $_dom->createElement('meta');
                    $metaTag->setAttribute('name','description');
                    if($storeCode == Mage::getStoreConfig('hauto/amp/code')){
                        $metaTag->setAttribute('content',sprintf('Need to replace your %s %s %s %s? Shop for the best deals of genuine mopar auto %s for your %s %s. Order yours today.',$year,$make,$model,$partName,$singularPartName,$year,$model));
                    }else if($storeCode == Mage::getStoreConfig('hauto/store/code')){
                        $metaTag->setAttribute('content', sprintf('Find %s Subaru %s %s.', $year, $model,$partName));
                    }else if($storeCode == Mage::getStoreConfig('hauto/jau/code')){
                        $meta = 'Shop discount Exterior Accessories for your %s %s %s. Genuine mopar Exterior Accessories parts for your %s %s.';
                        $metaTag->setAttribute('content', sprintf($meta,$year,$make, $model, $year, $model));
                    }else{
                        $meta = 'Buy discount %s for your %s %s %s. Genuine Ram Exterior Accessory parts for your %s %s %s. Shop now!';
                        $metaTag->setAttribute('content', sprintf($meta, $rawPartName,$year,$make, $model, $year, $make, $model));
                    }
                    $headElement = $_dom->getElementsByTagName('head');
                    /** @var DOMElement $head */
                    foreach($headElement as $head){
                        $head->appendChild($metaTag);
                        break;
                    }
                }
                $transport['html'] = $_dom->saveHTML();
            }

        }

        if($_block instanceof Homebase_Auto_Block_Related_Part){
            $transport = $observer->getTransport();
            $html = $transport['html'];
            $_dom = new DOMDocument();
            $_dom->loadHTML($html);
            $domXpath = new DOMXPath($_dom);

            if($controller == 'partmake' || $controller == 'partmodel'){
                $_request = Mage::app()->getRequest();
                $ymm = $_request->getParam('ymm_params');
                $params = unserialize($ymm);

                $ymmArray = array();

                foreach($params as $key => $value){
                    if($key != 'part'){
                        $ymmArray[] = $_helper->getOptionText($key,$value);
                    }
                }
                $makeUrl = implode('-',$ymmArray) . '-';
                $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $_request->getModuleName() . '/';

                $nodes = $domXpath->query('//ul/li/a');

                /** @var DOMElement $node */
                foreach($nodes as $_node){
                    $paths = pathinfo($_node->getAttribute('href'));
                    $partfilename = $paths['basename'];
                    $newRoute = $baseUrl . $makeUrl .$partfilename;

                    $_node->setAttribute('href', $newRoute);
                }
                $transport['html'] = $_dom->saveHTML();
            }else if($controller == 'partymm'){
                $_request = Mage::app()->getRequest();
                $ymm = $_request->getParam('ymm_params');
                $params = unserialize($ymm);
                $year = array_pop($params);
                $yearLabel = $_helper->getOptionText('year',$year);
                $ymmArray = array(
                    $yearLabel
                );
                foreach($params as $key => $value){
                    if($key != 'part'){
                        $ymmArray[] = $_helper->getOptionText($key,$value);
                    }
                }
                $filteredYmmArray = array_filter($ymmArray);
                $addedLabel = implode('-',$filteredYmmArray) . '-';

                $nodes = $domXpath->query('//ul/li/a');

                /** @var DOMElement $node */
                foreach($nodes as $_node){
                    $paths = pathinfo($_node->getAttribute('href'));
                    $partfilename = $paths['basename'];
                    $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $_request->getModuleName() . '/';
                    $href = $baseUrl . $addedLabel . $partfilename;

                    $_node->setAttribute('href',$href);
                }
                $transport['html'] = $_dom->saveHTML();
            }
        }
    }

    /**
     * @param $observer
     *
     * Deprecated.
     * Due to & getting converted to HTMLEntity
     */
    public function doInjectCanonical($observer){
        $_block = $observer->getBlock();
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $_block->getRequest();
        $module = $_request->getControllerModule();
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $currentUrl = substr($_request->getPathInfo(),1);
        $hrefnew = $baseUrl . $currentUrl;

//        $href = $baseUrl . $currentUrl;
        $href= Mage::helper('core/url')->getCurrentUrl();
        if($_block instanceof Mage_Page_Block_Html_Head){
            if($module === 'Homebase_Autopart' || $module === 'Homebase_Auto'){
                $transport = $observer->getTransport();
                $storeCode = $_request->getStoreCodeFromPath();
                $html = $transport['html'];
                $_dom = new DOMDocument();
                @$_dom->loadHTML($html);
                $domXpath = new DOMXPath($_dom);

                /** @var DOMNodeList $canonicals */
                $canonicals = $domXpath->query('//link[@rel="canonical"]');

                if($canonicals->length > 0){
                    $existingCanonical = null;
                    foreach($canonicals as $canonical){
                        $existingCanonical = $canonical;
                        break;
                    }
                    if(!is_null($existingCanonical)){
//                        Zend_Debug::dump($existingCanonical);
                        if($canonicals->length > 1){
                            $existingCanonical->parentNode->removeChild($existingCanonical);
                        }
                        $page =  $_request->getParam('p', null);
                        if(!is_null($page) && $page > 1) {
                                //$hrefnew = Mage::helper('core/url')->addRequestParam($hrefnew, array('p' => $page));
                        }
                        $existingCanonical->setAttribute('href',$hrefnew);
                    }
                }else{
                    $_linkEl = $_dom->createElement('link');
                    $_linkEl->setAttribute('rel','canonical');
                    $_linkEl->setAttribute('href',$href);
                    $headElement = $_dom->getElementsByTagName('head');
                    /** @var DOMElement $head */
                    foreach($headElement as $head){
                        $head->appendChild($_linkEl);
                        break;
                    }
                }
                $transport['html'] = $_dom->saveHTML();
            }
        }
    }
    public function doCoreBlockAbstractToHtmlBefore($observer){
    }
    public function injectCanonicalUsingBlocks($observer){
        /** @var Mage_Core_Model_Layout $layout */
        $layout = $observer->getLayout();
        /** @var Mage_Page_Block_Html_Head $_headBlock */
        $_headBlock = $layout->getBlock('head');
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $observer->getAction()->getRequest();

        $pagination = $request->getParam('p', null);
        $module = $observer->getAction()->getRequest()->getControllerModule();
        $controller = $observer->getAction()->getRequest()->getControllerName();
        $listingControllers =array('part','partmake','partmodel','partymm','model');
        if($module === 'Homebase_Autopart' || $module === 'Homebase_Auto'){
            if($_headBlock instanceof  Mage_Page_Block_Html_Head){
                $currentUrl = Mage::helper('core/url')->getCurrentUrl();
                $storeCode = $request->getStoreCodeFromPath();
                if(in_array($controller,$listingControllers) && $storeCode == 'spp_en'){
                    $charAtInt = strpos($currentUrl, '?');
                    if($charAtInt !== false){
                        $currentUrl = substr($currentUrl,0, $charAtInt);
                        if(!is_null($pagination)) {
                            $currentUrl = Mage::helper('core/url')->addRequestParam($currentUrl, array('p' => $pagination));
                        }
                    }
                }else if(in_array($controller,$listingControllers) && $storeCode == 'default'){
                    $currentUrl = strtok($currentUrl,'?');
                }
                
                $_headBlock->addLinkRel('canonical', $currentUrl);
            }
        }
    }
}