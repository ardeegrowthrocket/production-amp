<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/05/2018
 * Time: 4:27 PM
 */

class Growthrocket_Fitment_Model_Observer{

public function getAttributes($code){

$attribute = Mage::getResourceModel('eav/entity_attribute_collection')
                  ->addFieldToFilter('attribute_code',$code);
$attributeValue = Mage::getResourceModel('eav/entity_attribute_option_collection')
                        ->setAttributeFilter($attribute->getData('attribute_id'))
                        ->setStoreFilter(0, false);

$array = array();

foreach($attributeValue->getData() as $d){

    if(!empty(strlen(trim(strtolower($d['value'])))) && strlen(trim(strtolower($d['value'])))>=3)
    {
      $array[] = trim(strtolower($d['value']));  
    }
    
}

return $array;


}

public function genUrl($text){

  $text = str_replace("-","",$text);
  // replace non letter or digits by -
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);

  // transliterate
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  // trim
  $text = trim($text, '-');

  // remove duplicate -
  $text = preg_replace('~-+~', '-', $text);

  // lowercase
  $text = strtolower($text);

  if (empty($text)) {
    return 'n-a';
  }

  return $text;
}

public function Checkredirect(Varien_Event_Observer $observer)
{
    $storeCode =  Mage::helper('gr_redirection')->getStoreCode();
    $action = Mage::app()->getRequest()->getActionName();
    if($action == 'noRoute' && $storeCode == 'lfp'){

        $pathinfo = str_replace(array("/",".html"),"",Mage::app()->getRequest()->getOriginalPathInfo());
        $arraypath = explode("-",$pathinfo);
        $arraypathids = explode("-",$pathinfo);
        $last = end($arraypath);
        $prelast = $arraypath[count($arraypath)-2];
        $arraypathids = end(explode("_",$last));

        $pathcats = implode(",",explode("_",$last));
        //$prelast."--".$last."---".$arraypathids;

        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');


        if($prelast=='c' && is_numeric($arraypathids)){

            $type = "c";
            $mapper_id = $arraypathids;


             $results = $readConnection->fetchOne("SELECT GROUP_CONCAT(mapper) FROM `redirectcustom`  WHERE type='c' AND mapper_id IN ($pathcats)");

             if(!empty($results)){
                 $mapper = strtolower($results)." ".str_replace("-"," ",$pathinfo);
             }

             if(!empty($mapper)){

                         $selected = array();
                         $attr = array('auto_year','auto_make','auto_model','auto_type');
                         
                         foreach($attr as $at){

                          $vals =  $this->getAttributes($at);
                                foreach($vals as $valsdata){
                                      if (strpos($mapper,$valsdata) !== false) {
                                        $selected[$at] = $valsdata;
                                    }                                  
                                }
                         }

             }


             //model only
             if(isset($selected['auto_model'])){
                $str = "model/".$this->genUrl($selected['auto_model']).".html";
             }
             //make only
             if(isset($selected['auto_make'])){
                $str = "make/".$this->genUrl($selected['auto_make']).".html";
             }

             //category only
             if(isset($selected['auto_type'])){
                $str = "category/".$this->genUrl($selected['auto_type']).".html";
             }

             //year
             if(isset($selected['auto_make']) && isset($selected['auto_model']) && isset($selected['auto_year'])){
                $str = "year/".$this->genUrl($selected['auto_year']." ".$selected['auto_make']." ".$selected['auto_model']).".html";
             }


             //full pack
             if(count($selected)==4){
                $str = "cat/".$this->genUrl($selected['auto_year']." ".$selected['auto_make']." ".$selected['auto_model']." ".$selected['auto_type']).".html";
             }

             if(!empty($str)){
                $strdata = Mage::getBaseUrl().$str;
             }

        }



        if(($prelast=='p' && is_numeric($arraypathids)) || ($prelast=='pr' && is_numeric($arraypathids)))
        {

            $type = "p";
            $mapper_id = $arraypathids;
            $results = $readConnection->fetchOne("SELECT mapper FROM `redirectcustom`  WHERE type='p' AND mapper_id = '{$mapper_id}'");

             if(!empty($results)){
                  echo $mapper = $results;
             }


            $_sku = $mapper;
            $_catalog = Mage::getModel('catalog/product');
            $_productId = $_catalog->getIdBySku($_sku);
            if(!empty($_productId)){
                $_product = Mage::getModel('catalog/product')->load($_productId);
                $strdata = $_product->getProductUrl();                
            }




        }



            if(!empty($strdata)){
                //Mage::app()->getResponse()->setRedirect($strdata,301);
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $strdata");
                exit();
            }


    }
}
        


    public function injectWebsiteTab($observer){
        /** @var Mage_Core_Block_Template $block */
        $block = $observer->getBlock();

        if($block instanceof Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs){
            $attributeId = $block->getRequest()->getParam('attribute_id');
            if($attributeId == 251){
                $layout = $block->getLayout();
                $customblock = $layout->createBlock('grfitment/adminhtml_attribute_website');

                $block->addTabAfter('website',array(
                    'label' => Mage::helper('grfitment')->__('Website Assignment'),
                    'title' => Mage::helper('grfitment')->__('Website Assignment'),
                    'content' => $customblock->toHtml()
                ), 'labels');
            }
        }

        if($block instanceof Mage_Adminhtml_Block_Page_Head){
            $request = $block->getRequest();
            if($request->getControllerName() == 'catalog_product_attribute' && $request->getActionName() == 'edit'){
//  Don't use ExtJs
//                $block->setCanLoadExtJs(true);
                $block->addJs('jqtree/jquery.js');
                $block->addJs('jqtree/app.js');
                $block->addJs('jqtree/tree.jquery.js');
                $block->addCss('jqtree/app.css');
            }
        }
    }

    public function saveTypeProduct($observer){
        /** @var Mage_Adminhtml_Catalog_Product_AttributeController $_controller */
        $_controller = $observer->getControllerAction();
        /** @var Mage_Core_Controller_Request_Http $_request */
        $_request = $_controller->getRequest();
        $treeData = $_request->getParam('web-attr-tree-data', null);
        if(!empty($treeData)){
            $websiteCollection = Mage::getModel('grfitment/website')->getCollection();
            foreach($websiteCollection as $website){
                $website->delete();
            }
            $data = json_decode($treeData,true);
            foreach($data as $datum){
                if($datum['parent'] > 0){
                    try{
                        $website = Mage::getModel('grfitment/website');
                        $website->setWebsiteId($datum['parent']);
                        $website->setValueId($datum['option_id']);
                        $website->save();
                    }catch(Exception $exception){

                    }
                }
            }
        }
    }
}