<?php

class Homebase_Autopart_Model_Observer{

    public function setAttributeTabBlock($observer){
//        $product = $observer->getProduct();
//        if ($product->getTypeId() == 'autopart') {
//            Mage::register('combination_product',$product);
//        }
    }

    /**
     * Deprecated
     * @param $observer
     */
    public function doPrepareSave($observer){
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $observer->getRequest();

        /** @var Mage_Catalog_Model_Product $_product */
        $_product = $observer->getProduct();

        /** @var array $combinations */
        $fitmentCombinations = $_request->getParam('product_combinations',null);

        if($fitmentCombinations){
            $fitments = json_decode($fitmentCombinations,true);
            foreach($fitments as $fitment){
                if(array_key_exists('serial',$fitment) && !array_key_exists('delete',$fitment)){
                    $serial = $fitment['serial'];
                    if(array_key_exists('id',$serial)){
                        //Update
                        if(array_key_exists('update',$fitment)){
                            $editCombination = Mage::getModel('hautopart/mix')->load($serial['id']);
                            if($editCombination->getId()){
                                $editCombination->setYear($serial['y']);
                                $editCombination->setMake($serial['m']);
                                $editCombination->setModel($serial['ml']);
                                $editCombination->save();
                            }
                        }
                    }else{
                        //Insert
                        $newCombination = Mage::getModel('hautopart/mix');
                        $newCombination->setYear($serial['y']);
                        $newCombination->setMake($serial['m']);
                        $newCombination->setModel($serial['ml']);
                        $newCombination->setPosition($serial['i']);
                        $newCombination->setProductId($_product->getId());
                        $newCombination->save();
                    }

                }elseif(array_key_exists('serial',$fitment) && array_key_exists('delete',$fitment)){
                    //Delete
                    $serial = $fitment['serial'];
                    if(array_key_exists('id',$serial)){
                        $fitmentCombination = Mage::getModel('hautopart/mix')->load($serial['id']);
                        if($fitmentCombination->getId()){
                            $fitmentCombination->delete();
                        }
                    }
                }
            }
        }
    }

    /**
     * @deprecated
     */
    public function buildAssocations(){
        $_helper = Mage::helper('hautopart');
        /** @var Homebase_Autopart_Model_Resource_Mix_Collection $_combinations */
        $_mixes = Mage::getModel('hautopart/mix')->getCollection()
            ->addOrder('make',Homebase_Autopart_Model_Resource_Mix_Collection::SORT_ORDER_ASC)
            ->addOrder('model',Homebase_Autopart_Model_Resource_Mix_Collection::SORT_ORDER_ASC);
        $_mixes->getSelect()->group(array(
            'year','make','model'
        ));
        /** @var Homebase_Autopart_Model_Resource_Label $_labelResource */
        $_labelResource = Mage::getModel('hautopart/label')->getResource();
        $_labelResource->resetAutoIncrement();

        /** @var Homebase_Autopart_Model_Resource_Combination $_combinationResource */
        $_combinationResource = Mage::getModel('hautopart/combination')->getResource();
        $_combinationResource->resetAutoIncrement();

        /**
         * Fix for determining YMM options available for multi-store
         * @var  $_resource
         */
        $_resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $_resource->getConnection('core_read');
        $productWebsiteTable = $_resource->getTableName('catalog/product_website');
        foreach($_mixes as $_mix){
            $websiteAssoc = $_reader->select()->from($productWebsiteTable)
                ->where('product_id = ?', $_mix->getProductId())
                ->query()->fetch();
            $_combination = Mage::getModel('hautopart/combination');
            $_combination->setYear($_mix->getYear());
            $_combination->setMake($_mix->getMake());
            $_combination->setModel($_mix->getModel());
            $_combination->setStoreId($websiteAssoc['website_id']);
            $_combination->save();
            if($_combination->getId()){
                $props = $_combination->toArray(array('year','make','model'));
                foreach($props as $prop){
                    $_label = Mage::getModel('hautopart/label')->load($prop,'option');
                    try{
                        if(!$_label->getId()){

                            $label = $_helper->getOptionValue($prop);
                            $name = $label;
                            if($prop == 311) {
                                $name = '1500 DS';
                            }

                            $_label->setOption($prop);
                            $_label->setLabel($label);
                            $_label->setName($name);
                            $_label->save();
                        }
                    }catch(Exception $exception){
                        Mage::log($exception->getMessage(),null,'townfix.log');
                    }
                }
            }
        }

        /** Build auto_type to auto_label */
        Mage::helper('hautopart')->buildAutoTypeLabel();
    }


    public function doModelDeleteAfter($observer){
        $object = $observer->getObject();
        if($object instanceof Mage_Catalog_Model_Product){
            /** @var Mage_Catalog_Model_Product $_product */
            $_product = $object;
            if($_product->getTypeId() == Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID){

                $productId =  $_product->getId();
                if(!empty($productId)){
                    $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $write->query("DELETE FROM auto_combination_list WHERE product_id = {$productId}");
                }
            }
        }

    }

    public function doControllerActionLayoutLoadBefore($observer){
        if($observer->getAction() instanceof Homebase_Autopart_ModelController){
            /** @var Homebase_Autopart_ModelController $action */
            $action = $observer->getAction();
            /** @var Mage_Core_Model_Layout $layout */
            $layout = $observer->getLayout();
            /** @var Mage_Core_Model_Layout_Update $update */
            $update = $layout->getUpdate();

            if($action->getRequest()->getActionName() == 'sku'){
                $update->addHandle('PRODUCT_TYPE_autopart');

            }elseif($action->getRequest()->getActionName() == 'ymms'){
                $update->addHandle('PRODUCT_TYPE_autopart');
            }
            //$update->removeHandle('customer_logged_out');
        }
    }
    public function do_controller_action_layout_generate_blocks_before_handler($observer){
        /** @var Homebase_Autopart_ModelController $_action */
        $_action = $observer->getAction();
        /** @var Mage_Core_Model_Layout $layout */
        $layout = $observer->getLayout();
        if($_action->getRequest()->getActionName() == 'ymms'){
            $xml = <<<XML
<reference name="product.info.additional">
    <remove name="product_fitment"></remove>
    <block type="hautopart/ymms_fitment" name="ymms_product_fitment"></block>
</reference>
XML;
            $layout->getUpdate()->addUpdate($xml);
            $layout->generateXml();
        }
    }
    public function doControllerActionLayoutGenerateBlocksAfter($observer){
        /** @var Homebase_Autopart_ModelController $_action */
        $_action = $observer->getAction();
        /** @var Mage_Core_Model_Layout $layout */
        $layout = $observer->getLayout();
        if($_action->getRequest()->getActionName() == 'ymms' || $_action->getRequest()->getActionName() == 'sku'){
            /** @var Mage_Page_Block_Html_Breadcrumbs $_crumb */
            $_crumb = $layout->getBlock('breadcrumbs');
            $product_crumb = $_crumb->getCrumb('product');
            $_product = Mage::registry('current_product');
            $partname = strtolower(str_replace(' ', '-', $_product->getPartName()));
            if($_product->getPartName() != ''){
                $_crumb->removeCrumb('product');
                $hAutoPathHelper = Mage::helper('hauto/path');

                $category = $_product->getAttributeText('auto_type');
                if(!empty($category)){
                    if(is_array($category)){
                        $category = $category[0];
                    }
                    $textToUrl = $hAutoPathHelper->filterTextToUrl($category);
                    $_crumb->addCrumb('category',array(
                        'label' => $category,
                        'title' => 'category',
                        'link' =>  $hAutoPathHelper->generateLink($textToUrl,'category')
                    ));
                }

                $_crumb->addCrumb('partname',array(
                    'label' => $_product->getPartName(),
                    'title' => 'part-name',
                    'link' =>  Mage::getBaseUrl() .'part/'. $partname . '.html'
                ));

                if($_action->getRequest()->getActionName() == 'sku'){
                    $productName = $product_crumb['label'];
                    $product_crumb['label'] = $productName . ' - ' . $_product->getAmpPartNumber();
                }
                $_crumb->addCrumb('product',$product_crumb);
            }

            $_ymm = $layout->getBlock('ymm');
            // Inject YMM text to breadcrumbs
            if(method_exists($_ymm, 'getCurrent')){
                $isUsed = (trim($_ymm->getCurrent()) != '') ? true : false;
                $fitment = $_ymm->getCurrentFitment();
                if($isUsed && Mage::helper('hautopart')->ymmMatchesFitment($fitment,$_product->getId())){
                    $ymmText = $_ymm->getCurrent();
                    $_crumb->removeCrumb('product');
                    $oldlabel = $product_crumb['label'];
                    $_crumb->addCrumb('product',array(
                        'label' => $ymmText . ' ' . $oldlabel
                    ));
                }
            }

        }
        //Adding prev and next link rel
        if($_action->getRequest()->getActionName() == 'cat'){
            $this->_displayLinkRel($layout, $_action, 'product-list');
        }

        if($_action->getRequest()->getControllerName() == 'category'){
            $this->_displayLinkRel($layout, $_action, 'product-listing');
        }

        //Adding prev and next link rel
        $listingControllers =array('part','partmake','partmodel','partymm');

        if(in_array($_action->getRequest()->getControllerName(),$listingControllers)){
            $_blockListing = $layout->getBlock('product-listing');
            if($_blockListing){
                $_toolbar = $_blockListing->getToolbarBlock();
                /** @var Homebase_Auto_Block_Product_Listing_Pager $_pager */
                $_pager = $layout->createBlock('hauto/product_listing_pager');
                $_pager->setLimit($_toolbar->getLimit());

                $_pager->setCollection($_blockListing->getCollection());


                /** @var Mage_Page_Block_Html_Head $_headBlock */
                $_headBlock = $layout->getBlock('head');
                if($_headBlock instanceof  Mage_Page_Block_Html_Head){
                    $storeCode = $_action->getRequest()->getStoreCodeFromPath();
                    /** @var Mage_Core_Helper_Url $coreUrlHelper */
                    $coreUrlHelper = Mage::helper('core/url');
                    if(!$_pager->isFirstPage()){
                        $prevUrl = $_pager->getPreviousPageUrl();
                        if($storeCode == 'spp_en'){
                            $urlParts = parse_url($prevUrl);
                            $partsParam = array();
                            parse_str(html_entity_decode($urlParts['query']),$partsParam);
                            if(array_key_exists('p',$partsParam)){
                                $charAtInt = strpos($prevUrl, '?');
                                $prevUrl = substr($prevUrl,0 , $charAtInt);
                                $prevUrl = $coreUrlHelper->addRequestParam($prevUrl,array('p' => $partsParam['p']));
                            }
                        }
                        $p = Mage::app()->getRequest()->getParam('p');
                        if($p <= 2){
                            $prevUrl = strtok($prevUrl, '?');
                        }
                        $_headBlock->addLinkRel('prev',$this->parseParamUrl($prevUrl));
                    }
                    if(!$_pager->isLastPage()){
                        $nextUrl = $_pager->getNextPageUrl();
                        if($storeCode == 'spp_en'){
                            $urlParts = parse_url($nextUrl);
                            $partsParam = array();
                            parse_str(html_entity_decode($urlParts['query']),$partsParam);
                            if(array_key_exists('p',$partsParam)){
                                $charAtInt = strpos($nextUrl, '?');
                                $nextUrl = substr($nextUrl,0 , $charAtInt);
                                $nextUrl = $coreUrlHelper->addRequestParam($nextUrl,array('p' => $partsParam['p']));
                            }
                        }

                        $_headBlock->addLinkRel('next',$this->parseParamUrl($nextUrl));
                    }
                }
            }
        }
    }


    /**
     * @param $layout
     * @param $_action
     * @param $blockName
     */
    protected function _displayLinkRel($layout, $_action, $blockName)
    {
        $_blockListing = $layout->getBlock($blockName);
        if(!$_blockListing){
            return;
        }
        $_toolbar = $_blockListing->getToolbarBlock();
        $_pager = $layout->createBlock('hautopart/display_list_pager');
        $_pager->setLimit($_toolbar->getLimit());
        $_pager->setCollection($_blockListing->getLoadedProductCollection());

        /** @var Mage_Page_Block_Html_Head $_headBlock */
        $_headBlock = $layout->getBlock('head');
        if($_headBlock instanceof  Mage_Page_Block_Html_Head){
            $storeCode = $_action->getRequest()->getStoreCodeFromPath();
            /** @var Mage_Core_Helper_Url $coreUrlHelper */
            $coreUrlHelper = Mage::helper('core/url');
            if(!$_pager->isFirstPage()){
                $prevUrl = $_pager->getPreviousPageUrl();
                if($storeCode == 'spp_en'){
                    $urlParts = parse_url($prevUrl);
                    $partsParam = array();
                    parse_str(html_entity_decode($urlParts['query']),$partsParam);
                    if(array_key_exists('p',$partsParam)){
                        $charAtInt = strpos($prevUrl, '?');
                        $prevUrl = substr($prevUrl,0 , $charAtInt);
                        $prevUrl = $coreUrlHelper->addRequestParam($prevUrl,array('p' => $partsParam['p']));
                    }
                }
                $p = Mage::app()->getRequest()->getParam('p');
                if($p <= 2){
                    $prevUrl = strtok($prevUrl, '?');
                }
                $_headBlock->addLinkRel('prev',$this->parseParamUrl($prevUrl));
            }
            if(!$_pager->isLastPage()){
                $nextUrl = $_pager->getNextPageUrl();
                if($storeCode == 'spp_en'){
                    $urlParts = parse_url($nextUrl);
                    $partsParam = array();
                    parse_str(html_entity_decode($urlParts['query']),$partsParam);
                    if(array_key_exists('p',$partsParam)){
                        $charAtInt = strpos($nextUrl, '?');
                        $nextUrl = substr($nextUrl,0 , $charAtInt);
                        $nextUrl = $coreUrlHelper->addRequestParam($nextUrl,array('p' => $partsParam['p']));
                    }
                }
                $_headBlock->addLinkRel('next',$this->parseParamUrl($nextUrl));
            }
        }
    }

    /**
     * Parse Url
     *
     * @param $url
     * @param array $excludeParam
     * @return string
     */
    public function parseParamUrl($url, $excludeParam = array('p'))
    {

        $decodeUrl = htmlspecialchars_decode($url);
        $parts = parse_url($decodeUrl);
        parse_str($parts['query'], $query);

        //Exclude parameters
        foreach ($query as $key => $value){
           if(!in_array($key, $excludeParam)) {
               unset($query[$key]);
           }
        }

        $newUrl = "{$parts['scheme']}://{$parts['host']}{$parts['path']}";
        $new_query = http_build_query($query);
        if(!empty($new_query)){
            $newUrl .= "?".$new_query;
        }

        return $newUrl;
    }

    public function do_core_block_abstract_to_html_after($observer){
        $_block = $observer->getBlock();
        if($_block instanceof  Mage_Catalog_Block_Product_View){
            $_request = $_block->getRequest();
            if($_request->getControllerModule() === 'Homebase_Autopart' && $_request->getActionName() === 'ymms'){
                $_transport = $observer->getTransport();
                /** @var Homebase_Autopart_Model_Product $_product */
                $_product = Mage::registry('current_product');
                if($_product->getTypeId() === Homebase_Autopart_Model_Product_Type_Autopart::CUSTOM_PRODUCT_TYPE_ID){
                    $parts = explode('/', $_request->getOriginalPathInfo());
                    $fitment = ucwords(str_replace('-',' ',$parts[2]));

                    $html = $_transport['html'];
                    $_dom = new DOMDocument();
                    $_dom->loadHTML($html);
                    $target = $_dom->getElementById('product_'. $_product->getId());
                    $target->nodeValue=$fitment . ' ' . $_product->getPartName();
                    $productNameElement = $_dom->getElementById('ymms-product-' . $_product->getId());

                    $productNameElement->nodeValue = $_product->getName();
                    //$productNameElement->nodeValue=sprintf('<label>%s</label>',$_product->getName());
                    $_transport['html']=$_dom->saveHTML();
                }
            }elseif($_request->getControllerModule() === 'Homebase_Autopart' && $_request->getActionName() === 'sku'){
                /** @var Mage_Catalog_Block_Product_View $block */
                $block = $observer->getBlock();
                /** @var Homebase_Autopart_Block_Ymm $ymmblock */
                $ymmblock = $block->getLayout()->getBlock('ymm');
                $isYmmUsed = (trim($ymmblock->getCurrent()) !='' ? true : false);
                $_product = Mage::registry('current_product');
                $_transport = $observer->getTransport();
                $fitment = $ymmblock->getCurrentFitment();
                if($isYmmUsed && Mage::helper('hautopart')->ymmMatchesFitment($fitment,$_product->getId())){
                    $html = $_transport['html'];
                    $_dom = new DOMDocument();
                    $_dom->loadHTML($html);
                    $target = $_dom->getElementById('product_'. $_product->getId());
                    $productName = $_product->getName();
                    $ymmText = $ymmblock->getCurrent();
                    $target->nodeValue = $ymmText . ' ' . $productName;
                    $_transport['html']=$_dom->saveHTML();
                }
            }
        }elseif($_block instanceof Homebase_Autopart_Block_Fitment){
            $_request = $_block->getRequest();
            if($_request->getControllerModule() === 'Homebase_Autopart' && $_request->getActionName() === 'sku'){
                $_transport = $observer->getTransport();
                $html = $_transport['html'];
                $_dom = new DOMDocument();
                $_dom->loadHTML($html);

                /** @var Homebase_Autopart_Block_Ymm $ymmblock */
                $ymmblock = $_block->getLayout()->getBlock('ymm');
                $fitment = $ymmblock->getCurrentFitment();
                $_product = Mage::registry('current_product');
                if($ymmblock && $_product->getId()){
                    $isYmmUsed = (trim($ymmblock->getCurrent()) !='' ? true : false);
                    if($isYmmUsed && Mage::helper('hautopart')->ymmMatchesFitment($fitment,$_product->getId())){
                        $statementElement = $_dom->getElementById('fits-statement');
                        if($statementElement){
                            $statementElement->nodeValue = 'This product also fits the following vehicles.';
                        }
                    }

                }

                $_transport['html'] = $_dom->saveHTML();

            }
        }
    }
    public function doAdminhtmlCatalogProductAttributeEditPrepareForm($observer){
    }

    public function doCoreBlockAbstractToHtmlBefore($observer){
        /** @var Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs $block */
        $block = $observer->getBlock();
        /** @var Mage_Core_Model_Layout $_layout */
        $_layout = $block->getLayout();
        if($block instanceof  Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs) {
            $customblock = $_layout->createBlock('hautopart/adminhtml_attribute_image');
            $allowedAttributes = explode(',',Mage::getStoreConfig('hautopart/settings/attributes'));
            if(in_array($block->getRequest()->getParam('attribute_id'),$allowedAttributes)){
                $block->addTabAfter('custom',array(
                    'label'     => Mage::helper('hautopart')->__('Custom Attribute Images'),
                    'title'     => Mage::helper('catalog')->__('Custom Images'),
                    'content'   => $customblock->toHtml()
                ),'labels');
            }
        }
    }
    public function doAdminhtmlBlockHtmlBefore($observer){
        $block = $observer->getBlock();
        /** @var Mage_Core_Model_Layout $_layout */
        $_layout = $block->getLayout();
        if($block instanceof  Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs){
            $imgblock = $_layout->createBlock('hautopart/adminhtml_attribute_image');
            echo get_class($block);
            $block->addTab('custom',array(
                'label'     => Mage::helper('hautopart')->__('Custom'),
                'title'     => Mage::helper('catalog')->__('Custom Images'),
                'content'   => $imgblock
            ));
            //$transport = $observer->getTransport();
            //Zend_Debug::dump($transport);
//            $block->removeTab('labels');
        }

        if($block instanceof Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tab_Options){
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $_attribute */
            $_attribute = $block->getAttributeObject();
            $attribCode = $_attribute->getAttributeCode();
            //$block->getLayout()->removeOutputBlock($block->getNameInLayout());
//            $imgblock = $_layout->createBlock('hautopart/adminhtml_attribute_image');
//
//            $block->setChild('img_block', $imgblock);
//            $block->setTemplate('homebase/eav/attribute/options.phtml');
//            $options = $block->getOptionValues();
//            /** @var Varien_Object $_option */
//            foreach($options as $_option){
//                //Zend_Debug::dump($_option->getId());
//            }
            $blocks = $block->getLayout()->getAllBlocks();
            foreach($blocks as $ndx => $obj){
                //echo $ndx . '>>' . get_class($obj) . "<br/>";
            }
        }
    }

    public function doAdminhtmlCatalogProductAttributeSave($observer){
        /** @var Homebase_Autopart_Helper_Uploader $_uploader */
        $_uploader = Mage::helper('hautopart/uploader');
        /** @var Mage_Adminhtml_Catalog_Product_AttributeController $_controller */
        $_controller = $observer->getControllerAction();
        $_request = $_controller->getRequest();
        $_uploader->handleUpload();
    }

    /**
     * check YMM combination on login
     */
    public function customerLogin()
    {
        Mage::helper('hautopart/customer')->customerLoginCombination();
    }

    /**
     * Save YMM combination on checkout success.
     * @param $observer
     */
    public function saveCombinationOnCheckoutSuccess($observer)
    {
       $orderIds =  $observer->getData('order_ids');
       if(!empty($orderIds)){
           $orderId = $orderIds[0];
           $orderModel = Mage::getModel('sales/order')->load($orderId);
           $customerEmail = $orderModel->getCustomerEmail();

           $_cookie = Mage::getSingleton('core/cookie');
           $fitment = $_cookie->get('fitment');
           $fitmentYmm = $_cookie->get('fitment-ymm');

           if(!empty($fitment) && !empty($fitmentYmm)) {
               $ymm = explode('-', $fitmentYmm);
               $request = array(
                   'q' => $fitment,
                   'year' => $ymm[0],
                   'make' => $ymm[1],
                   'model' => $ymm[2]
               );
               Mage::helper('hautopart/customer')->setCustomerCombination($request, $customerEmail);
           }

       }
    }
}