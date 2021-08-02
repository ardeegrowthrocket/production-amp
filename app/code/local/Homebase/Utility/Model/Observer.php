<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/28/17
 * Time: 9:09 PM
 */

class Homebase_Utility_Model_Observer{
    public function handleCoreBlockAbstractToHtmlAfter($observer){
        $block = $observer->getBlock();
        // Apply only to product listing of Homebase_Autopart_Block_Display_Category
        if($block instanceof Homebase_Autopart_Block_Display_Category){
            /** @var Mage_Core_Controller_Request_Http $request */
            $request = $block->getRequest();
            //Filter /cat/**.html pages
            if($request->getActionName() == 'cat' && $request->getControllerName() == 'model'){
                /** @var Mage_Core_Model_Layout $layout */
                $layout = $block->getLayout();
                /** @var Homebase_Autopart_Block_Page_Html_Breadcrumbs $breadcrumb */
                $breadcrumb = $layout->getBlock('breadcrumbs');
                /** @var Homebase_Autopart_Block_Ymm $ymm */
                $ymm = $layout->getBlock('ymm');

                if($breadcrumb &&  $ymm){
                    $fitment = $ymm->getCurrent();
                    $crumbs = $breadcrumb->getAllCrumbs();
                    $labels = array_map(array($this,'extract'),$crumbs);
                    array_shift($labels);
                    $ymmlabel = array_pop($labels);
                    $crumbFitment = implode(' ', $labels);

                    if(!strcmp(strtolower($fitment),strtolower($crumbFitment))){
                        $transport = $observer->getTransport();
                        $html = $transport['html'];
                        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
                        $dom = new DOMDocument();
                        $dom->loadHTML($html);
                        $xpath = new DOMXPath($dom);

                        $hanchors = $xpath->query('//div[@class="category-products"]/ul/li/div/h2/a');
                        //Inject year-make-model category label on /cat/xxx.html route
                        $pageLabels = $xpath->query('//div[@class="category-products"]/p/span[@class="section-title"]');
                        foreach($pageLabels as $label){
                            $newValue = sprintf('%s %s',$crumbFitment,$ymmlabel);
                            $label->appendChild($dom->createTextNode($newValue));
                        }

                        /** @var DOMElement $hanchor */
                        foreach($hanchors as $hanchor){
                            $oldLink = pathinfo($hanchor->getAttribute('href'),PATHINFO_BASENAME);
                            $newText = sprintf('%s %s', $crumbFitment, trim($hanchor->nodeValue));
                            $fitmentLink = Mage::helper('hauto/path')->filterTextToUrl($crumbFitment);
                            $hanchor->setAttribute('href',$baseUrl . 'sku-ymm/'. $fitmentLink . '/' .$oldLink);
                            foreach($hanchor->childNodes as $textNode){
                                $hanchor->removeChild($textNode);
                            }
                            $hanchor->appendChild($dom->createTextNode($newText));
                        }

                        $anchors = $xpath->query('//div[@class="category-products"]/ul/li/div/div/a');
                        /** @var DOMElement $anchor */
                        foreach($anchors as $anchor){
                            if($anchor->getAttribute('class') =='product-image'){
                                $oldLink = pathinfo($anchor->getAttribute('href'),PATHINFO_BASENAME);
                                $fitmentLink = Mage::helper('hauto/path')->filterTextToUrl($crumbFitment);
                                $anchor->setAttribute('href',$baseUrl . 'sku-ymm/'. $fitmentLink . '/' .$oldLink);
                            }
                        }
                        $moreinfo = $xpath->query('//div[@class="category-products"]/ul/li/div/div/div/a');
                        $transport['html'] = $dom->saveHTML();
                    }
                }
            }
        }
    }
    public function extract($element){
        return $element['label'];
    }
    public function injectAdditionalJsValidation($observer){
        $_block = $observer->getBlock();
        if($_block instanceof Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element){
            $attributes = array('sku','amp_part_number');
            try{
                $dataObj = $_block->getDataObject();
                if($dataObj instanceof  Mage_Catalog_Model_Product){
                    if(in_array($_block->getAttributeCode(),$attributes)){
                        $transport = $observer->getTransport();
                        $html = $transport['html'];
                        $dom = new DOMDocument();
                        $dom->loadHTML($html);
                        $xpath = new DOMXPath($dom);
                        $nodes = $xpath->query("//tr/td/input");
                        foreach($nodes as $node){
                            $oldClasses = $node->getAttribute('class');
                            $node->setAttribute("class",$oldClasses . " validate-data-amp");
                        }
                        $transport['html'] = $dom->saveHTML();
                    }
                }
            }catch(Exception $exp){

            }
        }
    }
    public function injectJs($observer){
        /** @var $layout Smartwave_All_Model_Core_Layout */
        $layout = $observer->getLayout();
        $_block = $layout->getBlock('head');

        if($_block){
            $_block->addJs('amp/validation.js','amp_js');
        }

    }
}
