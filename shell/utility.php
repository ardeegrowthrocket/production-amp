<?php

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract {

    /**
     * Run script
     *
     */
    public function run()
    {
        // TODO: Implement run() method.
        $testString = 'asdasdfasdf__asdfsadferere---';
        if($this->getArg('replace') && $this->getArg('with')){
            /** @var Mage_Catalog_Model_Resource_Product_Collection $productCollection */
            $productCollection = Mage::getModel('catalog/product')->getCollection();
            $attributeCode = $this->getArg('attribute');
            $counter = 0;
            if($attributeCode){
                $productCollection->addAttributeToSelect($attributeCode);
                /** @var Mage_Catalog_Model_Product $product */
                foreach ($productCollection as $product){
                    if($product->hasData($attributeCode)){
                        $regex = $this->getArg('replace');
                        $value = $this->getArg('with');
                        $haystack = $product->getData($attributeCode);
                        if(preg_match($regex,$haystack)){
                            $counter++;
                            $newValue = preg_replace($regex,$value,$haystack);
                            if($this->getArg('commit')){
                                $product->setData($attributeCode,$newValue);
                                $product->save();
                            }
                            if($this->getArg('verbose')){
                                Mage::log($product->getSku(), null,'utility_replace.log',true);
                            }
                        }
                    }
                }
                if($this->getArg('verbose')){
                    Mage::log("Total >> " . $counter, null,'utility_replace.log',true);
                }
            }
        }
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f utility.php -- [options]
    replace <regex>
    with    <character>
    attribute <attribute code>
    verbose List affected
    commit Save changes
USAGE;
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();