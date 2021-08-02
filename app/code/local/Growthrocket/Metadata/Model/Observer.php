<?php
class Growthrocket_Metadata_Model_Observer
{

    /**
     * @return array
     */
    protected function _allowedWebsites()
    {
        return array('lfp','mopar','sop','mop','spp','mogp','base');
    }

    /**
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Model_Store_Exception
     */
    public function setProductMetaData(Varien_Event_Observer $observer)
    {

        $websiteCode = $this->_getWebsite()->getCode();
        $product = $observer->getProduct();

        if(in_array($websiteCode, $this->_allowedWebsites()) && $product) {
            $product = $observer->getProduct();
            $productName = $product->getName();
            $partNumber = $product->getAmpPartNumber();
            $title = $product->getMetaTitle();
            $description = $product->getMetaDescription();
            $savingPercent = 0;

            if($product->getMsrp() > 0) {
                $savingPercent = 100 - round(($product->getFinalPrice() / $product->getMsrp()) * 100);
                $savings = Mage::helper('core')->currency($product->getMsrp() - $product->getFinalPrice(), true, false);
            }

            if(empty($title)) {
                $title = "{$productName} - {$partNumber}";
            }

            if(empty($description)){
                $affordableOrDiscount = array('affordable','discounted');
                $randomValue = rand(0,1);

                switch ($websiteCode){

                    case 'lfp':
                        $description = "Buy {$product->getName()} - PN: {$partNumber}. Shop {$affordableOrDiscount[$randomValue]} parts & accessories & save up to {$savings}. Fitment, price & details available.";
                        break;

                    case  'mopar':
                        $description = "Shop {$product->getName()} - PN: {$partNumber}. Get {$this->_randomText(array('quality','the best'))} {$this->_randomText(array('oem','performance'))} parts & accessories & save up to {$savings}. Fitment, price & details available.";
                        break;

                    case  'sop':
                        $description = "{$this->_randomText(array('Buy','Shop','Get'),2)} {$product->getName()} - PN: {$partNumber} online! Choose SubaruOnlineParts for {$this->_randomText(array('affordable','discounted'))} {$this->_randomText(array('oem','genuine'))} parts & save up to {$savings}. Order Now!";
                        break;

                    case  'mop':
                        $description = "{$this->_randomText(array('Buy','Shop','Get'),2)} {$product->getName()} - PN: {$partNumber} online! Browse {$this->_randomText(array('affordable','discounted'))} {$this->_randomText(array('oem','genuine'))} parts at MoparOnlineParts & save up to  {$savings}. Buy Now!";
                        break;

                    case  'spp':
                        $description = "{$this->_randomText(array('Buy','Shop'))} {$product->getName()} - {$partNumber} online. Subaru Parts Pros offers {$this->_randomText(array('affordable','discounted'))} {$this->_randomText(array('maintenance','tuneup'))} & performance parts. Order now & save up to {$savings}! ";
                        break;

                    case  'mogp':
                        $title = "Buy {$productName} - {$partNumber}";
                        $description = "Buy {$product->getName()}. Get discounted price for Mopar car parts & accessories for your {$this->_randomText(array('car','auto','vehicle'))}. Shop now & save up to {$savings}!  ";
                        break;

                    case  'base':
                        $title = "{$productName} - {$partNumber}";
                        $description = "{$this->_randomText(array('Buy','Shop'))} {$product->getName()} - {$partNumber} online. Choose All Mopar Parts for its {$this->_randomText(array('affordable','discounted'))} Mopar {$this->_randomText(array('parts','accessories'))} & save {$savings}. Shop now! ";
                        break;

                    default;
                        $description = '';
                }
            }

            $product->setMetaTitle($title);
            $product->setMetaDescription($description);
        }
    }

    /**
     * @param array $data
     * @return mixed|string
     */
    protected function _randomText(array $data, $max = 1)
    {
        $randomValue = rand(0,$max);
        $selected = '';
        if(count($data) >= 2){
            $selected = $data[$randomValue];
        }

        return $selected;

    }

    /**
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Model_Store_Exception
     */
    public function changeCustomMetaTitle(Varien_Event_Observer $observer)
    {

        if(in_array($this->_getWebsite()->getCode(), $this->_allowedWebsites())){
            $request = Mage::app()->getRequest();
            $ymmParam = unserialize($request->getParam('ymm_params'));

            if(!empty($ymmParam)){
                $title = $this->_staticDescription($this->_getWebsite()->getCode(), $request);
                $head = $observer->getLayout()->getBlock('head');
                $head->setDescription($title);
            }
        }
    }

    /**
     * @param $websitecode
     * @param $controllerTag
     * @return mixed|void
     */
    protected function _staticDescription($websiteCode, $request)
    {
        $controllerTag = "{$request->getControllerModule()}_{$request->getControllerName()}_{$request->getActionName()}";
        $ymmParam = unserialize($request->getParam('ymm_params'));
        $paramData = ['year' => "",'make' => "",'model' => "",'category' => "", 'part' => ""];
        $subCat = "";

        foreach ($ymmParam as $label => $key){
            if($label != 'part'){
                $paramData[$label] =  trim(ucfirst(Mage::Helper('hauto')->getAutoLabelById($key)));
            }else {
                $paramData[$label] = $key;
            }
        }

        $categoryTitle = '';
        $makeTitle = '';
        $makeModelTitle = '';
        switch ($controllerTag){
            case 'Homebase_Auto_partmodel_index':
                $makeModelArray = $paramData;
               unset($makeModelArray['part']);
                $makeModelTitle = trim(implode(" ",$makeModelArray));
                break;
            case 'Homebase_Autopart_model_ymm':
                $makeTitle = $paramData['make'];
                break;

            case 'Homebase_Autopart_model_cat':
                $categoryTitle = $paramData['category'];
                break;
        }

        if(isset($paramData['model'])){
            if($paramData['model'] == '1500'){
                $paramData['model'] = '1500 DS';
            }
        }

        $title = trim(implode(" ",array_filter($paramData)));
        $partName = '';
        if(isset($ymmParam['part'])){
            $partName = $ymmParam['part'];
        }

        $discountOrAffordable = array('discounted','affordable');
        $qualityOrTheBest = array('quality','the best');
        $genuineOrQuality = array('genuine','quality');
        $selectionOrCollection = array('selection','collection');
        $selectOrChoose = array('Select', 'Choose');
        $vehicleOrCar = array('vehicle','car');
        $MakeLincolnOrFord = array(
            'Lincoln' => "Buy genuine Lincoln parts & accessories at Levittown Ford Parts. Get high-quality deals for your {$this->_getMakeDesc()} & more. Shop now!",
            'Ford' => "Shop genuine Ford parts & accessories for your vehicle from Levittown Ford Parts. Choose the best deals for your {$this->_getMakeDesc()} & more. Buy now!",
                        );
        $randomValue = rand(0,1);

        $customDescription = array();

        switch ($websiteCode){
            case 'lfp':

                $customDescription['lfp'] = array(
                    'Homebase_Auto_category_index'   => "Shop quality {$title} for your vehicle. Get {$discountOrAffordable[$randomValue]} parts & accessories: {$this->_getSubCategoryDesc()} & more. Shop now!",
                    'Homebase_Auto_part_index'   => "Buy {$qualityOrTheBest[$randomValue]} {$title} for your Ford & Lincoln vehicle. Choose from a wide selection of {$discountOrAffordable[$randomValue]} Ford & Lincoln {$title} & other accessories.",
                    'Homebase_Auto_partmake_index'  => "Browse {$title} & other parts & accessories from Levittown Ford Parts. {$selectOrChoose[$randomValue]} from a wide collection of parts & accessories.",
                    'Homebase_Auto_partmodel_index'  => "Get {$genuineOrQuality[$randomValue]} {$title} online for your car. Levittown Ford has a wide {$selectionOrCollection[$randomValue]} of parts & accessories for your {$makeModelTitle}. Shop Now!",
                    'Homebase_Auto_partymm_index'  => "Shop {$title} for your vehicle. Get {$genuineOrQuality[$randomValue]} {$partName} for your car from Levittown Ford Parts. Shop now!",
                    'Homebase_Autopart_model_index' => $MakeLincolnOrFord[$title],
                    'Homebase_Autopart_model_model'  => "Shop {$genuineOrQuality[$randomValue]} {$title} parts and accessories. Get the best parts & accessories for {$this->_getModelDesc($title)}  & more from Levittown Ford Parts",
                    'Homebase_Autopart_model_ymm' => "Get {$qualityOrTheBest[$randomValue]} {$title} accessories from Levittown Ford. Discount {$makeTitle} parts & accessories like {$this->_getYmmDesc()} & more.",
                    'Homebase_Autopart_model_cat' => "Shop {$title} for your vehicle. Get {$discountOrAffordable[$randomValue]} {$categoryTitle} for your {$vehicleOrCar[$randomValue]} from Levittown Ford Parts",
                );
                break;

            case 'mopar':

                $customDescription['mopar'] = array(
                    'Homebase_Auto_category_index'   => "Get {$this->_randomText(array('discounted','affordable'))} {$title} for your vehicle from Mopar Genuine Parts. Browse {$this->_randomText(array('quality','the best'))} performance parts & accessories online! Buy now!",
                    'Homebase_Auto_part_index'   => "Shop {$this->_randomText(array('oem','genuine'))} {$title} for your Mopar {$this->_randomText(array('truck','vehicle'))}. Get the {$this->_randomText(array('best deal','most affordable deal'))} for {$this->_randomText(array('Dodge & Jeep','Chrysler & Ram'))} vehicle at Mopar Genuine Parts.",
                    'Homebase_Auto_partmake_index'  => "Mopar Genuine Parts offers {$this->_randomText(array('discounted','affordable'))} {$title} online. The best {$partName} for your {$this->_getMetaDataModel()} vehicles. Shop now! ",
                    'Homebase_Auto_partmodel_index'  => "Find {$this->_randomText(array('the best','high quality'))} {$title} at Mopar Genuine Parts. Browse our online catalog for {$this->_getMetaDataYear($paramData)} parts & accessories. Buy Now!",
                    'Homebase_Auto_partymm_index'  => "Buy {$title} online for your Mopar {$this->_randomText(array('vehicle','car','truck'), 2)}. Choose from a {$this->_randomText(array('wide','huge'))} selection of {$this->_randomText(array('oem','performance'))} parts & accessories. Shop now!",
                    'Homebase_Autopart_model_index' => "Shop {$this->_randomText(array('quality','oem','genuine'), 2)} {$title} Parts & Accessories from Mopar Genuine Parts. We offer {$this->_randomText(array('affordable','discounted'))} {$paramData['make']} accessories for models: {$this->_getMakeDesc()}, & more.",
                    'Homebase_Autopart_model_model'  => "Buy OEM {$title} Parts & Accessories for your {$paramData['make']} {$this->_randomText(array('vehicle','car'))} at Mopar Genuine Parts. Get {$this->_randomText(array('great','the best'))} deals on {$title} accessories from Mopar today.",
                    'Homebase_Autopart_model_ymm' => "Get great deals on {$this->_randomText(array('oem accessories','performance parts'))} for the {$title} here at Mopar Genuine Parts. We offer a variety of {$paramData['make']} {$paramData['model']} accessories & parts.",
                    'Homebase_Autopart_model_cat' => "Buy {$this->_randomText(array('original','quality','the best'),2)} {$title} here at Mopar Genuine Parts. Find {$this->_randomText(array('discounted','affordable'))} {$categoryTitle} for your {$paramData['year']} {$paramData['make']} {$paramData['model']}. Shop now!",
                );
                break;

            case 'sop':
                $sopMakeModel = "";
                if(!empty($paramData['make']) && !empty($paramData['model'])){
                    $sopMakeModel = "{$paramData['make']} {$paramData['model']}";
                }

                $customDescription['sop'] = array(
                    'Homebase_Auto_category_index'   => "Get quality {$title} for your Subaru cars. Buy {$discountOrAffordable[$randomValue]} parts & accessories: {$this->_getSubCategoryDesc(true)} & more. Shop now!",
                    'Homebase_Auto_part_index'   => "Shop {$this->_randomText(array('affordable','the best'))} deals on Subaru {$title} from SubaruOnlineParts.com. We offer guaranteed {$this->_randomText(array('genuine','oem'))} Subaru {$title} for your {$this->_randomText(array('car','vehicle'))}. Get yours now!",
                    'Homebase_Auto_partmake_index'  => "Buy {$this->_randomText(array('low price','affordable'))}, high quality {$title} for your car. Select from the {$this->_randomText(array('largest','biggest'))} online {$this->_randomText(array('selection','collection'))} of {$title} & other accessories! ",
                    'Homebase_Auto_partmodel_index'  => "Find {$this->_randomText(array('quality','genuine'))} {$title} for your car at SubaruOnlineParts. We sell {$this->_randomText(array('discount','affordable'))} performance parts & accessories online. Shop {$this->_randomText(array('oem','original'))} parts now!",
                    'Homebase_Auto_partymm_index'  => "Get your {$title} from SubaruOnlineParts. We {$this->_randomText(array('offer','sell'))} only {$this->_randomText(array('genuine','oem'))} subaru parts & accessories for your {$sopMakeModel}. Buy now!",
                    'Homebase_Autopart_model_model' => "Buy {$this->_randomText(array('genuine','oem'))} {$title} Parts & Accessories online! Choose {$this->_randomText(array('affordable','the best'))} deals for your {$this->_getMetaDataYear(array(), 3)} {$sopMakeModel} & more. Buy now!",
                    'Homebase_Autopart_model_ymm' => "Get {$this->_randomText(array('quality','the best'))} deals on parts & accessories for your {$title}. Great prices on {$this->_randomText(array('genuine','oem','original'),2)} {$sopMakeModel} {$this->_randomText(array('performance','maintenance'))} parts. Buy now!",
                    'Homebase_Autopart_model_cat' => "Shop {$title} at a great {$this->_randomText(array('price','deal'))} from SubaruOnlineParts. Get {$this->_randomText(array('quality','the best'))} {$this->_randomText(array('part','accessories'))} for your {$this->_randomText(array('car','vehicle'))}. Buy now!",
                );
                break;

            case 'mop':
                $makeModel = "";
                $ymm = "";
                $make = !empty($paramData['make']) ? $paramData['make'] : '';
                if(!empty($paramData['make']) && !empty($paramData['model'])){
                    $makeModel = "{$paramData['make']} {$paramData['model']}";
                }
                if(!empty($paramData['year']) && !empty($paramData['make']) && !empty($paramData['model'])){
                    $ymm = "{$paramData['year']} {$paramData['make']} {$paramData['model']}";
                }

                $customDescription['mop'] = array(
                    'Homebase_Auto_category_index'   => "Shop {$this->_randomText(array('affordable','discounted'))} {$title} for your {$this->_randomText(array('cars','vehicle'))} at MoparOnlineParts. Buy {$this->_randomText(array('quality','qenuine'))} parts & accessories: {$this->_getSubCategoryDesc(true)} & more. Shop now!",
                    'Homebase_Auto_part_index'   => "Get {$this->_randomText(array('low price','affordable'))} deals on mopar {$title} at MoparOnlineParts. We offer guaranteed  {$this->_randomText(array('genuine','oem'))} mopar {$title} for your {$this->_randomText(array('car','vehicle'))}. Buy now!",
                    'Homebase_Auto_partmake_index'  => "Buy {$this->_randomText(array('affordable','the best'))}, high quality {$title} at MoparOnlineParts. Choose from the {$this->_randomText(array('largest','biggest'))} {$this->_randomText(array('mopar catalog','online collection'))} of {$title} & other oem parts! ",
                    'Homebase_Auto_partmodel_index'  => "Order genuine {$title} from MoparOnlineParts. Select only {$this->_randomText(array('high quality','authentic'))} parts & accessories to keep your {$make}'s performance at its best. ",
                    'Homebase_Auto_partymm_index'  => "Get your {$title} at MoparOnlineParts.  We are your quality leader for {$this->_randomText(array('genuine','oem'))} {$makeModel} parts & accessories. Buy yours now!",
                    'Homebase_Autopart_model_index' => "Buy {$title} Parts & Accessories at a {$this->_randomText(array('discounted','low'))} price at MoparOnlineParts. Massive {$this->_randomText(array('collection','selection'))} of {$this->_randomText(array('quality oem','genuine'))} parts for your {$make} vehicles. Buy now!",
                    'Homebase_Autopart_model_model' => "Find fantastic {$title} Parts & Accessories deals at MoparOnlineParts. We offer only {$this->_randomText(array('genuine','oem'))} parts for your {$this->_getMetaDataYear(array(), 3)   } {$makeModel}. Buy now!",
                    'Homebase_Autopart_model_ymm' => "Shop {$title} Parts & Accessories online! MoparOnlineParts carries hundreds of {$makeModel} {$this->_randomText(array('performance','maintenance'))} parts at the {$this->_randomText(array('lowest','best'))} price. Shop now!",
                    'Homebase_Autopart_model_cat' => "Get a great deal on {$title} online. We {$this->_randomText(array('have','carry'))} only {$this->_randomText(array('oem','genuine'))} {$ymm} parts & accessories here at MoparOnlineParts. ",
                );
                break;

            case 'spp':
                $sopMakeModel = "";
                if(!empty($paramData['make']) && !empty($paramData['model'])){
                    $sopMakeModel = "{$paramData['make']} {$paramData['model']}";
                }

                $customDescription['spp'] = array(
                    'Homebase_Auto_category_index'   => "{$this->_randomText(array('Shop','Buy'))} {$this->_randomText(array('car','auto','Subaru'))} {$title} at SubaruPartsPros.com. Choose from a variety of {$this->_randomText(array('quality','affordable'))} performance parts & {$this->_randomText(array('original','genuine'))} accessories. Order online now! ",
                    'Homebase_Auto_part_index'   => "{$this->_randomText(array('Buy','Browse'))} {$this->_randomText(array('affordable','discounted'))} Subaru {$title} online. {$this->_randomText(array('Shop','Get'))} genuine {$this->_randomText(array('car','auto'))} tune-up parts & performance accessories from Subaru Parts Pros. Get yours now! ",
                    'Homebase_Auto_partmake_index'  => "Get {$this->_randomText(array('genuine','quality'))} {$title} online. We offer {$this->_randomText(array('quality','discounted','original'))} auto parts & accessories for {$this->_getMetaDataModel(2)} & more. Order online now! ",
                    'Homebase_Auto_partmodel_index'  => "Get {$this->_randomText(array('discounted','the best priced'))} {$title} at subarupartspros.com. Browse {$this->_randomText(array('genuine','a variety of'))} Subaru parts & accessories from our online catalog. Shop now!",
                    'Homebase_Auto_partymm_index'  => "Browse {$title} from Subaru Parts Pros. We offer the {$this->_randomText(array('latest','newest'))} auto accessories & performance parts for your Subaru vehicle. Shop now! ",
                    'Homebase_Autopart_model_model' => "{$this->_randomText(array('Shop','Buy'))} genuine {$title} parts & accessories from Subaru Parts Pros. Choose the best deals for your {$this->_getMetaDataYear(array(),2)}, {$sopMakeModel} & more. Buy online now! ",
                    'Homebase_Autopart_model_ymm' => "Shop {$this->_randomText(array('discounted','affordable'))} car parts & accessories for your {$title}. Get {$this->_randomText(array('best','affordable'))} deals on {$this->_randomText(array('original','genuine'))} {$sopMakeModel} {$this->_randomText(array('tune-up','performance'))} parts. Buy now!",
                    'Homebase_Autopart_model_cat' => "Shop {$title} online. Choose Subaru Parts Pros for its {$this->_randomText(array('affordable','discounted'))} {$this->_randomText(array('prices','deals'))} of {$this->_randomText(array('auto','car'))} parts & accessories. Buy now!",
                );
                break;

            case 'mogp':
                $sopMakeModel = "";
                if(!empty($paramData['make']) && !empty($paramData['model'])){
                    $sopMakeModel = "{$paramData['make']} {$paramData['model']}";
                }

                switch ($controllerTag){
                    case 'Homebase_Auto_category_index':
                       if(strpos($title,'Mopar') === false){
                           $title = 'Mopar ' . $title;
                       }
                        break;
                }

                $make = "";
                if(!empty($paramData['make'])){
                    $make = $paramData['make'];
                }

                $customDescription['mogp'] = array(
                    'Homebase_Auto_category_index'   => "Buy  {$title} at moparoriginalparts.com. {$this->_randomText(array('Browse','Choose'))} from a variety of discounted replacement parts & accessories for your {$this->_randomText(array('vehicle','car'))} online. Shop now!",
                    'Homebase_Auto_part_index'   => "{$this->_randomText(array('Browse','Shop'))} genuine Mopar {$title} online at moparoriginalparts.com. We offer {$this->_randomText(array('the best price','affordable'))} tune-up & performance parts & accessories online. Buy now! ",
                    'Homebase_Auto_partmake_index'  => "{$this->_randomText(array('Shop','Get'))} discounted {$title} from Mopar Original Parts. Browse from our online catalog of {$this->_randomText(array('affordable','authentic'))} parts for {$make} {$this->_getMetaDataModel(2)}. Shop online now!",
                    'Homebase_Auto_partmodel_index'  => "{$this->_randomText(array('Get','Buy'))} {$this->_randomText(array('the best priced','genuine'))} {$title} online. {$this->_randomText(array('Browse','Choose'))} from a variety of affordable performance parts for your {$paramData['model']} vehicle. Shop now! ",
                    'Homebase_Autopart_model_index' => "{$this->_randomText(array('Browse','Choose'))} from affordable {$title} Parts & Accessories from Mopar Original Parts. We offer discounted mopar car parts & accessories for your {$this->_randomText(array('vehicle','car','auto'))}. Shop now!",
                    'Homebase_Autopart_model_model' => "{$this->_randomText(array('Shop','Buy'))} genuine {$title} Parts & Accessories. Choose from {$this->_randomText(array('quality','affordable','discounted'))} Mopar car parts for your {$this->_getMetaDataYear(array(),2)} {$title} Parts & Accessories & more. Shop now! ",
                    'Homebase_Autopart_model_ymm' => "Browse affordable {$title} parts at moparoriginalparts.com. {$this->_randomText(array('Get','Choose'))} the best deals for your {$paramData['make']} {$paramData['model']} {$this->_randomText(array('replacement','performance'))} parts & more. Grab yours now!",
                    'Homebase_Auto_partymm_index'  => "Shop {$title} at moparoriginalparts.com. We offer the {$this->_randomText(array('latest','newest'))} auto performance parts & car accessories online. Buy online now!",
                    'Homebase_Autopart_model_cat' => "Shop {$title}. {$this->_randomText(array('Choose','Browse'))} Mopar Original Parts' catalog & get discounted prices for {$this->_randomText(array('genuine','authentic'))} auto parts. Buy now!",
                );
                break;

            case 'base':
                $sopMakeModel = "";
                if(!empty($paramData['make']) && !empty($paramData['model'])){
                    $ampMakeModel = "{$paramData['make']} {$paramData['model']}";
                }

                $domain = 'allmoparparts.com';
                $customDescription['base'] = array(
                    'Homebase_Auto_category_index'   => "{$this->_randomText(array('Get','Buy'))} original Mopar {$title} from {$domain}. We offer {$this->_randomText(array('the best price','affordable'))} Mopar {$title} online for your Mopar {$this->_randomText(array('car','auto'))}. Shop now!",
                    'Homebase_Auto_part_index'   => "{$this->_randomText(array('Shop','Get'))} Mopar {$title} online at All Mopar Parts. Choose from our online catalog of Mopar {$title} for your {$this->_getMakeLabel()} & more. Buy now!",
                    'Homebase_Auto_partmake_index'  => "Shop {$title} from {$domain}. Choose the best dealer of {$this->_randomText(array('genuine','affordable'))} Mopar {$this->_randomText(array('maintenance','performance'))} parts & accessories online. {$this->_randomText(array('Grab','Get'))} yours now!",
                    'Homebase_Auto_partmodel_index'  => "Buy {$this->_randomText(array('genuine','original'))} Mopar {$title} at {$domain}. Get {$this->_randomText(array('discounted','affordable'))} performance parts for your {$this->_getMetaDataYear(array(),1)} {$title} & more. Shop now!",
                    'Homebase_Auto_partymm_index'  => "Shop {$title} from {$domain}. We {$this->_randomText(array('offer','sell'))} {$this->_randomText(array('genuine','original'))} Mopar parts & accessories for your {$paramData['make']} {$paramData['model']}. Buy now! ",
                    'Homebase_Autopart_model_index' => "Buy {$title} parts & accessories from All Mopar Parts. Choose from a variety of {$this->_randomText(array('tune-up','performance'))} & maintenance parts for your {$paramData['make']} {$this->_getMakeDesc(1)} & more. Shop now!",
                    'Homebase_Autopart_model_model' => "{$this->_randomText(array('Get','Buy'))} {$title} parts & accessories from {$domain}. We offer a variety of Mopar parts & accessories for your {$this->_getMetaDataYear(array(),1)} {$ampMakeModel} & more. Grab yours now! ",
                    'Homebase_Autopart_model_ymm' => "Get {$this->_randomText(array('the best','affordable'))} deals on your {$title} at All Mopar Parts. Shop {$this->_randomText(array('genuine','original'))} performance & maintenance parts for your Mopar vehicle. Buy now!",
                    'Homebase_Autopart_model_cat' => "Shop {$title} from {$domain}. Get {$this->_randomText(array('the best deals','affordable prices'))} of Mopar parts & accessories for your {$this->_randomText(array('car','vehicle'))}. Buy now!",
                );
                break;
        }

        if(isset($customDescription[$websiteCode])){

            if(isset($customDescription[$websiteCode][$controllerTag])){
                    return $customDescription[$websiteCode][$controllerTag];
            }else{
                return;
            }
        }else{
            return;
        }
    }

    /**
     * Get Make Label
     * @param int $limit
     * @return string
     */
    protected function _getMakeLabel($limit = 2)
    {
        $makeLabel = Mage::registry('data_make_label');
        $makeLabelArray = array();
        if(!empty($makeLabel)){
            foreach (array_slice($makeLabel, 0, $limit) as $label){
                $makeLabelArray[] =  ucfirst($label);
            }
        }

        return implode(", ", $makeLabelArray);
    }

    /**
     * Filter By Make
     * @return string
     */
    protected function _getMakeDesc($limit = 3)
    {
        $makeModelMetaData = Mage::registry('make_model_meta');
        $makeModelMetaArray = array();
        if(!empty($makeModelMetaData)){
            foreach (array_slice($makeModelMetaData, 0, $limit) as $label){
                $makeModelMetaArray[] =  ucfirst($label);
            }
        }

        return implode(", ", $makeModelMetaArray);
    }

    /**
     * Filter by Part
     * @return string
     */
    protected function _getSubCategoryDesc($noLabel = false)
    {
        $partMetaData = Mage::registry('sub_category_meta');
        $partMetaArray = array();

        if(!$noLabel){
            if(!empty($partMetaData)){
                foreach ($partMetaData as $partName){
                    $partMetaArray[] =  $partName['label'];
                }
            }
        }else{
            $partMetaArray = $partMetaData;
        }

        return implode(", ", $partMetaArray);
    }

    protected function _getMetaDataModel($limit = 3)
    {
        $makeModelMetaData = Mage::registry('meta_model_data');
        if(count($makeModelMetaData) < 3){
            $limit = count($makeModelMetaData);
        }
        $rand_keys = array_rand($makeModelMetaData, $limit);
        $makeModelMetaArray = array();
        if(!empty($makeModelMetaData)){
            foreach ($rand_keys as $key){
                $makeModelMetaArray[] =  ucfirst($makeModelMetaData[$key]);
            }
        }

        return implode(", ", $makeModelMetaArray);
    }

    protected function _getMetaDataYear($paramData = array(),$limit = 2)
    {

        $makeModelYearMetaData = Mage::registry('meta_year_data');

        $makeModelYearMetaArray = array();
        $make = "";
        $model = "";
        if(!empty($makeModelYearMetaData)){
            if(isset($paramData['make'])){
                $make = $paramData['make'];
            }
            if(isset($paramData['model'])){
                $model = $paramData['model'];
            }

            foreach (array_slice($makeModelYearMetaData, 0, $limit) as $label){
                $makeModelYearMetaArray[] =  ucfirst($label);
            }

            if(isset($makeModelYearMetaData[3]) && !empty($paramData)){
                $makeModelYearMetaArray[] =  ucfirst($makeModelYearMetaData[3]) . " {$make} {$model}";
            }
        }

        return implode(", ", $makeModelYearMetaArray);
    }

    /**
     * Filter By Model
     * @param $title
     * @return string
     */
    protected function _getModelDesc($title = null)
    {
        $modelMetaData = Mage::registry('model_meta_data');
        $modelMetaArray = [];
        if(!empty($modelMetaData)){
            foreach ($modelMetaData as $item){
                $modelMetaArray[] = $item . " " . trim($title);
            }
        }
        return implode(", ", $modelMetaArray);
    }

    /**
     * Filter by Year
     * @return string
     */
    protected function _getYmmDesc()
    {
        $ymmMetaData = Mage::registry('ymm_meta_data');
        $ymmMetaArray = [];
        if(!empty($ymmMetaData)){
            array_pop($ymmMetaData);
            foreach ($ymmMetaData as $item){
                $ymmMetaArray[] = $item;
            }
        }
         return implode(", ", $ymmMetaArray);
    }

    /**
     * @return Mage_Core_Model_Website
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getWebsite()
    {
        return Mage::app()->getStore()->getWebsite();
    }
}
