<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/4/18
 * Time: 5:43 PM
 */

class Growthrocket_Html_Block_Html_Head extends Smartwave_Porto_Block_Html_Head{
    private $allowed_modules = array('hauto','hautopart');
    public function getTitle()
    {

        $titleSuffix =  Mage::getStoreConfig('design/head/title_suffix');
        $request = Mage::app()->getRequest();
        $controllerTag = "{$request->getControllerModule()}_{$request->getControllerName()}_{$request->getActionName()}";
        $ymmParams = unserialize($this->getRequest()->getParam('ymm_params'));
        if($this->__getWebsite()->getCode() == 'lfp') {
            if(!empty($ymmParams)){

                $ymmTitle = [];
                $hasNoLabel = ['year','make','model','category'];
                foreach ($ymmParams as $label => $key){

                    if(in_array($label,$hasNoLabel)){
                        $ymmTitle[$label] =  ucfirst(Mage::Helper('hauto')->getAutoLabelById($key));
                    }else{
                        $ymmTitle[$label] = $key;
                    }
                }

                if($controllerTag != 'Homebase_Autopart_model_cat') {
                    $ymmTitle['var'] = " - Performance Parts & Accessories";
                    $ymmTitle['title_suffix'] = $titleSuffix;
                }

                switch ($controllerTag) {
                    case 'Homebase_Autopart_model_index':
                        if(strtolower($ymmTitle['make']) == 'lincoln'){
                            $ymmTitle['var'] = " Accessories";
                        }else{
                            $ymmTitle['var'] = " Performance Parts & Accessories";
                        }
                        break;

                    case 'Homebase_Auto_category_index':
                    case 'Homebase_Auto_part_index':
                        $ymmTitle['var'] = " - Lincoln & Ford Accessories";
                        break;

                    default:
                }


                /** Part and Category page */
                $specialPage = array('Homebase_Auto_category_index','Homebase_Auto_part_index');
                if(in_array($controllerTag, $specialPage)){
                    $ymmTitle['var'] = " - Lincoln & Ford Accessories";
                }

                return implode(' ', $ymmTitle);
            }else{
               return parent::getTitle();
            }
        }elseif($this->__getWebsite()->getCode() == 'mopar') {

            if(!empty($ymmParams)){
                $ymmTitle = [];
                $hasNoLabel = ['year','make','model','category'];
                $ymmTitle['title_prefix'] = 'Mopar';
                foreach ($ymmParams as $label => $key){

                    if(in_array($label,$hasNoLabel)){
                        $ymmTitle[$label] =  ucfirst($this->_updateValue(Mage::Helper('hauto')->getAutoLabelById($key)));
                    }else{
                        $ymmTitle[$label] = $key;
                    }
                }
                $categoryTitle = isset($ymmTitle['category']) ? $ymmTitle['category'] : '';
                $checkMopar = strpos(strtolower($categoryTitle), 'mopar');
                if($checkMopar !== false){
                    unset($ymmTitle['title_prefix']);
                }

                $allowdYMMAddons = array('Homebase_Autopart_model_index','Homebase_Autopart_model_model');
                if(in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['var'] = " Parts & Accessories";
                }

                $allowdAddons = array('Homebase_Auto_category_index','Homebase_Auto_part_index','Homebase_Auto_partmake_index');
                $ymmTitle['title_suffix'] = $titleSuffix;

                return implode(' ', $ymmTitle);
            }else{
                return parent::getTitle();
            }
        }elseif($this->__getWebsite()->getCode() == 'sop') {

            if (!empty($ymmParams)) {
                $ymmTitle = [];

                $allowdYMMAddons = array('Homebase_Autopart_model_model');
                if (in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['var'] = "Genuine";
                }

                $allowdAddons = array('Homebase_Auto_category_index');
                if (in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Genuine Subaru";
                }

                $allowdAddons = array('Homebase_Auto_part_index');
                if (in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Buy Genuine";
                }

                $allowedPartArray = array('Homebase_Auto_partmake_index', 'Homebase_Auto_partmodel_index');
                if (in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Genuine";
                }

                $allowedPartArray = array('Homebase_Autopart_model_cat');
                if (in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Buy";
                }

                $hasNoLabel = ['year', 'make', 'model', 'category'];
                foreach ($ymmParams as $label => $key) {

                    if (in_array($label, $hasNoLabel)) {
                        $ymmTitle[$label] = ucfirst(Mage::Helper('hauto')->getAutoLabelById($key));
                    } else {
                        $ymmTitle[$label] = $key;
                    }
                }

                $allowdYMMAddons = array('Homebase_Autopart_model_index', 'Homebase_Autopart_model_model', 'Homebase_Autopart_model_ymm');
                if (in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['parts'] = "Parts & Accessories";
                }

                $ymmTitle['title_suffix'] = $titleSuffix;
                if (in_array($controllerTag, array('Homebase_Autopart_model_cat'))) {
                    $ymmTitle['title_suffix'] = '';
                }


                return implode(' ', $ymmTitle);
            } else {
                return parent::getTitle();
            }
        } elseif($this->__getWebsite()->getCode() == 'spp') {

            if(!empty($ymmParams)){
                $ymmTitle = [];

                $allowdAddons = array('Homebase_Auto_category_index');
                if(in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Browse Subaru";
                }

                $allowdAddons = array('Homebase_Autopart_model_index');
                if(in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Discount Subaru Parts & Accessories";
                }

                $allowdAddons = array('Homebase_Auto_part_index');
                if(in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Shop Subaru";
                }

                $allowedPartArray = array('Homebase_Auto_partmake_index');
                if(in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Genuine";
                }

                $allowedPartArray = array('Homebase_Autopart_model_cat','Homebase_Auto_partmodel_index','Homebase_Autopart_model_model');
                if(in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Buy";
                }

                $hasNoLabel = ['year','make','model','category'];
                foreach ($ymmParams as $label => $key){

                    if(in_array($label,$hasNoLabel)){
                        $ymmTitle[$label] =  ucfirst(Mage::Helper('hauto')->getAutoLabelById($key));
                    }else{
                        $ymmTitle[$label] = $key;
                    }
                }

                $allowdYMMAddons = array('Homebase_Autopart_model_model','Homebase_Autopart_model_ymm');
                if(in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['parts'] = "Parts & Accessories";
                }

                $ymmTitle['title_suffix'] = $titleSuffix;
                if(in_array($controllerTag, array('Homebase_Autopart_model_cat'))) {
                    $ymmTitle['title_suffix'] = '';
                }

                return implode(' ', $ymmTitle);
            }else{
                return parent::getTitle();
            }
        }elseif($this->__getWebsite()->getCode() == 'mop') {

            //echo $controllerTag;
            if(!empty($ymmParams)){
                $ymmTitle = [];

                $allowdYMMAddons = array('Homebase_Autopart_model_model');
                if(in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['var'] = "Genuine";
                }

                $allowdAddons = array('Homebase_Auto_category_index','Homebase_Auto_part_index');
                if(in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Genuine Mopar";
                }

                $allowedPartArray = array('Homebase_Auto_partmake_index','Homebase_Auto_partmodel_index','Homebase_Autopart_model_model');
                if(in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "OEM";
                }

                $allowedPartArray = array('Homebase_Autopart_model_cat');
                if(in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Buy";
                }

                $hasNoLabel = ['year','make','model','category'];
                foreach ($ymmParams as $label => $key){

                    if(in_array($label,$hasNoLabel)){
                        $ymmTitle[$label] =  ucfirst($this->_updateValue(Mage::Helper('hauto')->getAutoLabelById($key)));
                    }else{
                        $ymmTitle[$label] = $key;
                    }
                }

                $allowdYMMAddons = array('Homebase_Autopart_model_index','Homebase_Autopart_model_model','Homebase_Autopart_model_ymm');
                if(in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['parts'] = "Parts & Accessories";
                }

                $ymmTitle['title_suffix'] = $titleSuffix;
                if(in_array($controllerTag, array('Homebase_Autopart_model_cat'))) {
                    $ymmTitle['title_suffix'] = '';
                }

                if(isset($ymmTitle['var']) && isset($ymmTitle['category'])){
                    $category = explode(' ', $ymmTitle['category']);
                    if(isset($category[0]) && strtolower($category[0]) == 'mopar'){
                        $ymmTitle['var'] = "Genuine";
                    }
                }


                return implode(' ', $ymmTitle);
            }else{
                return parent::getTitle();
            }
        }elseif($this->__getWebsite()->getCode() == 'mogp') {

            //echo $controllerTag;
            if(!empty($ymmParams)){
                $ymmTitle = [];

                if(in_array($controllerTag, array('Homebase_Auto_category_index'))) {
                    $ymmTitle['var'] = "Shop Mopar";
                }

                if(in_array($controllerTag, array('Homebase_Auto_part_index'))) {
                    $ymmTitle['var'] = "Buy Mopar";
                }

                $allowedPartArray = array('Homebase_Auto_partmake_index');
                if(in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Shop";
                }

                if(in_array($controllerTag, array('Homebase_Auto_partmodel_index'))) {
                    $ymmTitle['var'] = "Buy";
                }

                if(in_array($controllerTag, array('Homebase_Autopart_model_cat'))) {
                    $ymmTitle['var'] = "Buy";
                }

                if(in_array($controllerTag, array('Homebase_Autopart_model_index'))) {
                    $ymmTitle['var'] = "Genuine";
                }

                $hasNoLabel = ['year','make','model','category'];
                foreach ($ymmParams as $label => $key){

                    if(in_array($label,$hasNoLabel)){
                        $ymmTitle[$label] =  ucfirst($this->_updateValue(Mage::Helper('hauto')->getAutoLabelById($key)));
                    }else{
                        $ymmTitle[$label] = $key;
                    }
                }

                $allowdYMMAddons = array('Homebase_Autopart_model_index','Homebase_Autopart_model_model','Homebase_Autopart_model_ymm');
                if(in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['parts'] = "Parts & Accessories";
                }

                $ymmTitle['title_suffix'] = $titleSuffix;
                if(in_array($controllerTag, array('Homebase_Autopart_model_cat'))) {
                    $ymmTitle['title_suffix'] = '';
                }

                if(isset($ymmTitle['var']) && isset($ymmTitle['category'])){
                    $category = explode(' ', $ymmTitle['category']);
                    if(isset($category[0]) && strtolower($category[0]) == 'mopar'){
                        $ymmTitle['var'] = "Shop";
                    }
                }

                return implode(' ', $ymmTitle);
            }else{
                return parent::getTitle();
            }
        } elseif($this->__getWebsite()->getCode() == 'base') {

            if(!empty($ymmParams)){
                $ymmTitle = [];

                $allowdAddons = array('Homebase_Auto_category_index');
                if(in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Shop Mopar";
                }

                $allowdAddons = array('Homebase_Autopart_model_index');
                if(in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Buy";
                }

                $allowdAddons = array('Homebase_Auto_part_index');
                if(in_array($controllerTag, $allowdAddons)) {
                    $ymmTitle['var'] = "Buy Mopar";
                }

                $allowedPartArray = array('Homebase_Auto_partmake_index');
                if(in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Shop";
                }

                if(in_array($controllerTag, array('Homebase_Auto_partymm_index','Homebase_Autopart_model_model'))) {
                    $ymmTitle['var'] = "Buy";
                }

                $allowedPartArray = array('Homebase_Auto_partmodel_index');
                if(in_array($controllerTag, $allowedPartArray)) {
                    $ymmTitle['var'] = "Shop";
                }

                $hasNoLabel = ['year','make','model','category'];
                foreach ($ymmParams as $label => $key){

                    if(in_array($label,$hasNoLabel)){
                        $ymmTitle[$label] =  ucfirst(Mage::Helper('hauto')->getAutoLabelById($key));
                    }else{
                        $ymmTitle[$label] = $key;
                    }
                }

                $allowdYMMAddons = array('Homebase_Autopart_model_index','Homebase_Autopart_model_model','Homebase_Autopart_model_ymm');
                if(in_array($controllerTag, $allowdYMMAddons)) {
                    $ymmTitle['parts'] = "Parts & Accessories";
                }

                if(in_array($controllerTag, array('Homebase_Auto_part_index'))) {
                    $ymmTitle['suffix'] = "Online";
                }

                $ymmTitle['title_suffix'] = $titleSuffix;

                return implode(' ', $ymmTitle);
            }else{
                return parent::getTitle();
            }
        }

        $websiteId = $this->__getWebsite()->getId();
        $allowedWebsites = explode(',',Mage::getStoreConfig('grhtml/settings/stores'));
        $request = $this->getRequest();

        $moduleAlias = $this->getRequest()->getModuleName();
        $module = $this->getRequest()->getControllerModule();
        $requestString = $this->__getRequestString();
        /** @var Homebase_Autopart_Helper_Data $helper */
        $helper = Mage::helper('hautopart');
        /** @var Growthrocket_Html_Helper_Sql $sqlHelper */
        $sqlHelper = Mage::helper('grhtml/sql');
        //Resume default behavior
        if(!in_array($websiteId, $allowedWebsites)){
            if (empty($this->_data['title'])) {
                $this->_data['title'] = $this->getDefaultTitle();
            }
            return htmlspecialchars(html_entity_decode(trim($this->_data['title']), ENT_QUOTES, 'UTF-8'));
        }
        if($meta_title = $this->getMetaTitle()){
            return htmlspecialchars($meta_title);
        }
        //Check if code is allowed to run on a module
        if(in_array($moduleAlias, $this->allowed_modules)){
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            //Set Generic Dynamic Page Title query
            $queryArray = array(
                'module' => $module,
                'url' => $requestString
            );
            $query = $request->getParam('ymm_params',-1);
            // Validate if there's ymm data available.
            if($query != -1){
                $combination = unserialize($query);
                if($controller === 'model'){
                    if($action === 'index'){
                        // Route make/xxx.html
                        $makeLabel = ucwords($helper->getOptionValue($combination['make']));
                        $title = Mage::getStoreConfig('grhtml/make/title');
                        if (!$sqlHelper->hasPageRecord($queryArray)){
                            $this->_data['title'] = sprintf($title, $makeLabel, $makeLabel);
                        }else{
                            $matches = $sqlHelper->getPageRecord($queryArray);
                            // Fetch first record only
                            $firstMatch = array_pop($matches);
                            $this->_data['title'] = sprintf($firstMatch['title']);
                        }
                    }else if($action === 'model'){
                        // Route model/xxx.html
                        $makeLabel = ucwords($helper->getOptionValue($combination['make']));
                        $modelLabel = ucwords(Mage::helper('hautopart/parser')->getLabel($combination['model'],'name'));
                        $title = Mage::getStoreConfig('grhtml/model/title');
                        $this->_data['title'] = sprintf($title, $makeLabel, $modelLabel);
                    }else if($action === 'ymm'){
                        // Route year/xxx.html
                        $makeLabel = ucwords($helper->getOptionValue($combination['make']));
                        $modelLabel = ucwords(Mage::helper('hautopart/parser')->getLabel($combination['model'],'name'));
                        $yearLabel = ucwords($helper->getOptionValue($combination['year']));
                        $title = Mage::getStoreConfig('grhtml/ymm/title');
                        $this->_data['title'] = sprintf($title,$yearLabel, $makeLabel, $modelLabel);
                    }else if($action === 'cat'){
                        // Route cat/xxx.html
                        $makeLabel = ucwords($helper->getOptionValue($combination['make']));
                        $modelLabel = ucwords(Mage::helper('hautopart/parser')->getLabel($combination['model'],'name'));
                        $yearLabel = ucwords($helper->getOptionValue($combination['year']));
                        $catLabel = ucwords($helper->getOptionValue($combination['category']));
                        $title = Mage::getStoreConfig('grhtml/cat/title');
                        $this->_data['title'] = sprintf($title,$yearLabel,$makeLabel,$modelLabel,$catLabel);
                    }else if($action === 'sku' || $action === 'ymms'){
                        $_product = Mage::registry('current_product');
                        $name = $_product->getName();
                        $sku = $_product->getSku();
                        $title = Mage::getStoreConfig('grhtml/sku/title');
                        $this->_data['title'] = sprintf($title, $name, $sku);
                    }
                }
                else if($controller === 'category'){
                    if($action === 'index'){
                        $systemConfiguredTitle = Mage::getStoreConfig('grhtml/category/title');
                        $this->_data['title'] = $this->getDefaultTitle();
                        //Check if there is a configured page specific title
                        if($sqlHelper->hasPageRecord($queryArray)){
                            $matches = $sqlHelper->getPageRecord($queryArray);
                            $firstMatch = array_pop($matches);
                            $this->_data['title'] = $firstMatch['title'];
                        }else if(!empty($systemConfiguredTitle)){
                            $this->_data['title'] = $systemConfiguredTitle;
                        }
                    }
                }
            }
            return htmlspecialchars(html_entity_decode(trim($this->_data['title']), ENT_QUOTES, 'UTF-8'));
        }else{
            return parent::getTitle();
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function _updateValue($value)
    {
         if($value == '1500'){
             return '1500 DS';
         }else{
             return $value;
         }
    }

    public function getDescription()
    {
        $defaultWebsite = array('lfp','mopar','sop','mop','spp','mogp','base');
        if(in_array($this->__getWebsite()->getCode(),$defaultWebsite)){
            return parent::getDescription();
        }
        $websiteId = $this->__getWebsite()->getId();
        $allowedWebsites = explode(',',Mage::getStoreConfig('grhtml/settings/stores'));
        $request = $this->getRequest();
        $moduleAlias = $this->getRequest()->getModuleName();
        $module = $this->getRequest()->getControllerModule();
        $requestString = $this->__getRequestString();
        /** @var Homebase_Autopart_Helper_Data $helper */
        $helper = Mage::helper('hautopart');
        /** @var Growthrocket_Html_Helper_Sql $sqlHelper */
        $sqlHelper = Mage::helper('grhtml/sql');

        if(!in_array($websiteId, $allowedWebsites)){
            if (empty($this->_data['description'])) {
                $this->_data['description'] = Mage::getStoreConfig('design/head/default_description');
            }
            return htmlspecialchars(html_entity_decode(trim($this->_data['title']), ENT_QUOTES, 'UTF-8'));
        }
        if(in_array($moduleAlias, $this->allowed_modules)){
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            //Set Generic Dynamic Page Title query
            $queryArray = array(
                'module' => $module,
                'url' => $requestString
            );
            $query = $request->getParam('ymm_params',-1);
            if($query != -1){
                $combination = unserialize($query);
                if($controller === 'category'){
                    // Route category/xxx.html
                    $this->_data['description'] = Mage::getStoreConfig('grhtml/category/description');
                    if($sqlHelper->hasPageRecord($queryArray)){
                        $matches = $sqlHelper->getPageRecord($queryArray);
                        $firstMatch = array_pop($matches);
                        if(!empty($firstMatch['meta_desc'])){
                            $this->_data['description'] = $firstMatch['meta_desc'];
                        }
                    }
                    return $this->stripTags($this->_data['description']);
                }
            }
        }else{
            return parent::getDescription();
        }
    }
    private function __getWebsite(){
        return Mage::app()->getStore()->getWebsite();
    }
    private function __getRequestString(){
        $request = $this->getRequest();
        return preg_replace('/\s+/', '', substr($request->getRequestString(), 1));
    }
}