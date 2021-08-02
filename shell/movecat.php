<?php
/**
 * Created by PhpStorm.
 * User: oliver
 * Date: 3/13/2018
 * Time: 2:44 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{
    public function run(){

        if($this->getArg('web') && $this->getArg('old') && $this->getArg('new')){
            $oldCatString = $this->getArg('old');
            $oldCats = explode(',',$oldCatString);
            $newCat = $this->getArg('new');
            $webid =$this->getArg('web');
            if(is_numeric($webid) && is_numeric($newCat) && $this->_areNumerics($oldCats)){
                $productIds = $this->_getWebsiteProducts($webid);
                foreach($productIds as $productId){
                    $categoryIdString = $this->_getCategoryIds($productId['product_id']);
                    if(!is_null($categoryIdString)){
                       $categories = explode(',',$categoryIdString);
                       //Check if the current product category matches with the oldcats
                       if($this->hasMatches($categories,$oldCats)){
                           echo "\n Run process\n";
                           $newCategories = array_diff($categories, $oldCats);
                           //Push new cat
                           array_push($newCategories, $newCat);
                           //Run unique function to eliminate possible duplicates
                           $uniqueCategories = array_unique($newCategories, SORT_NUMERIC);
                           $this->_updateCategory($productId['product_id'], $uniqueCategories);
                           Mage::log($productId['product_id'], null, 'dataaffected.log');
                       }
                       //Get only current categories that didn't match with oldcats
                       }else{
                        Mage::log($productId, null,'dataerror.log');
                    }
                }
                Zend_Debug::dump($productIds->rowCount());
                Zend_Debug::dump("Please re-run indexer to update routes.");
            }
        }else if($this->getArg('web') && $this->getArg('attrid')){
            $attrids = $this->getArg('attrid');
            $attrs = explode(',', $attrids);
            $webid = $this->getArg('web');
            if(is_numeric($webid)){
                $webid = intval($webid);

                $varCharTable = $this->_getTable('catalog_product_entity_varchar');

                foreach($attrs as $attr){
                    $select = $this->_getReader()->select();

                    $select->from($varCharTable)
                        ->where('attribute_id=?', 251)
                        ->where('value LIKE ?', '%' . $attr . '%');


                    $result = $select->query()->fetchAll(PDO::FETCH_ASSOC);

                    Zend_Debug::dump($result);
                }
            }
        }else if($this->getArg('web') && $this->getArg('notype')){
            $webid = $this->getArg('web');
            $notypectr =0;
            echo "\n Product Skus without assigned auto_type \n";
            if(is_numeric($webid)){
                $productIds = $this->_getWebsiteProducts($webid);
                foreach($productIds as $productId){
                    $categories = $this->_getCategoryIds($productId['product_id']);
                    if(is_null($categories)){
                        $_product = Mage::getModel('catalog/product')->load($productId['product_id']);
                        echo $_product->getSku(). ', ';
                        $notypectr++;
                    }
                }
            }
            echo "Products without auto_type >> " . $notypectr . "\n";
        }
    }
    public function hasMatches($productTypes, $oldcats){

        return count(array_intersect($productTypes,$oldcats)) > 0;
    }
    protected function _getWebsiteProducts($webid){
        $webid = intval($webid);
        $select = $this->_getReader()->select();
        $select->from($this->_getTable('catalog/product_website'))
            ->where('website_id=?', $webid);
        $productIds = $select->query();
        return $productIds;
    }

    protected function _areNumerics($array){
        $arenumerics = true;

        foreach($array as $item){
            if(!is_numeric($item)){
                $arenumerics = false;
                break;
            }
        }
        return $arenumerics;
    }
    protected function _updateCategory($productId, $newCategoryArray){
        $valueId = null;
        $varcharTable = $this->_getTable('catalog_product_entity_varchar');
        $select = $this->_getReader()->select();

        $select->from($varcharTable,array('value_id'))
            ->where('entity_id= ? ',$productId)
            ->where('attribute_id= ?', 251);

        $result = $select->query()->fetchAll(PDO::FETCH_ASSOC,0);
        if(count($result) === 1){
            foreach($result as $row){
                $valueId = $row['value_id'];
            }
        }
        if(!is_null($valueId)){
            $writer = $this->_getWriter();
            $writer->update('catalog_product_entity_varchar',array(
                'value' => implode(',',$newCategoryArray)
            ),'value_id=' . $valueId);
        }
    }
    protected function _getCategoryIds($productId){
        $varcharTable = $this->_getTable('catalog_product_entity_varchar');
        $select = $this->_getReader()->select();

        $select->from($varcharTable,array('value'))
            ->where('entity_id= ? ',$productId)
            ->where('attribute_id= ?', 251);
        $result = $select->query()->fetchAll(PDO::FETCH_ASSOC, 0);
        if(count($result) === 1){
           $value = array_pop($result);
            return $value['value'];
        }
        return null;
    }
    protected function _getTable($alias){
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource->getTableName($alias);
    }
    protected function _getReader(){
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource->getConnection('core_read');
    }
    public function _getWriter(){
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        return $resource->getConnection('core_write');
    }
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f movecat.php --old <category_ids> --new <category_id> --web <website_id>
USAGE;
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();