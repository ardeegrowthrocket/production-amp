<?php

class Aitoc_Aitcbp_Model_Observer
{

    protected $_updated = null;

    protected function updateAttribute($entityTypeId, $id, $values)
    {
        $eav = Mage::getModel('eav/entity_attribute');
        /* @var $eav Mage_Eav_Model_Entity_Attribute */
        $eav->loadByCode($entityTypeId, $id);

        foreach ($values as $key => $value) {
            $eav->setData($key, $value);
        }
        $eav->save();
    }

    protected function _updateAttribute($flag = true)
    {
        if (!is_null($this->_updated)) {
            return;
        }
        $this->updateAttribute(
            'catalog_product', 'cbp_group', array(
            'is_visible'              => $flag,
            'source_model'            => ($flag) ? 'aitcbp/price_group' : '',
            'frontend_input_renderer' => ($flag) ? 'aitcbp/adminhtml_group_renderer' : '',
        )
        );
        $this->_updated = true;
    }

    public function uninstallModule($observer)
    {
        if (!$this->isDoInstallDeinstall($observer)) {
            return;
        }
        $this->_updateAttribute(false);
        Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price')
            ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        Mage::getResourceSingleton('catalogrule/rule')->applyAllRulesForDateRange();
    }

    public function installModule($observer)
    {
        if (!$this->isDoInstallDeinstall($observer)) {
            return;
        }
        $this->_updateAttribute(true);
        Mage::getModel('aitcbp/product_price_index')->reindexAll();
        Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price')
            ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);

        Mage::getResourceSingleton('catalogrule/rule')->applyAllRulesForDateRange();

        return;
    }

    protected function isDoInstallDeinstall($observer)
    {
        $aStatus              = $observer->getData('data');
        $iCurrentModuleStatus = $aStatus['Aitoc_Aitcbp'];
        $aStatus              = $observer->getData('status_hash');
        $iPrevModuleStatus    = $aStatus['Aitoc_Aitcbp'];

        return !($iCurrentModuleStatus == $iPrevModuleStatus);
    }

    public function updateProductCollectionPrice($observer)
    {
        $request = Mage::app()->getFrontController()->getRequest();
        if ($request->getModuleName() == 'admin'
            && $request->getControllerName() == 'sales_order_create'
            && in_array($request->getActionName(), array('index'))
        ) {
            return;
        }

        if (!Mage::helper('aitcbp')->isEnabled()) {
            return;
        }

        $collection = $observer->getEvent()->getCollection();
        foreach ($collection->getItems() as $item) {
            /* @var $item Mage_Catalog_Model_Product */
            $item->load($item->getId());
        }
    }

    public function updateProductPrice($observer)
    {
        if (!Mage::helper('aitcbp')->isEnabled()) {
            return;
        }

        $product = $observer->getEvent()->getProduct();
        /* @var $product Mage_Catalog_Model_Product */

        $request = Mage::app()->getFrontController()->getRequest();
        if ($request->getModuleName() == 'admin'
            && $request->getControllerName() == 'catalog_product'
            && in_array($request->getActionName(), array('edit', 'duplicate', 'save'))
        ) {
        } else {
            if ($product->getTypeId() == 'grouped') {
                $children     = Mage::getModel('catalog/product_type_grouped')
                    ->getAssociatedProductCollection($product);
                $minimalPrice = 0;
                foreach ($children as $child) {
                    if (!$minimalPrice) {
                        $minimalPrice = $child->getPrice();
                    }
                    if ($child->getPrice() > 0 && $minimalPrice > $child->getPrice()) {
                        $minimalPrice = $child->getPrice();
                    }
                    if ($child->getSpecialPrice() > 0 && $minimalPrice > $child->getSpecialPrice()) {
                        $minimalPrice = $child->getSpecialPrice();
                    }
                    if ($child->getFinalPrice() > 0 && $minimalPrice > $child->getFinalPrice()) {
                        $minimalPrice = $child->getFinalPrice();
                    }
                }
                $product->setMinimalPrice($minimalPrice);
            } else {
                /* if cost is not set */
                if (!($product->getCost() > 0)) {
                    return;
                }
                $groupId = $product->getCbpGroup();

                /* if no group is assigned */
                if (!$groupId) {
                    return;
                }

                $group = Mage::helper('aitcbp')->getGroup($groupId);
                /* if group is not exist */
                if (!$group) {
                    return;
                }
                /* if group is disabled */
                if (!$group->getIsActive()) {
                    return;
                }
                /* if group is not associated with customer group */
                $originalPrice = $product->getPrice();
                /* get new price based on automatic price groups */
                $price = Mage::helper('aitcbp')->getPrice($product, $group);
                $price = number_format($price, 4, '.', '');

                $cpbGroupId = $product->getCbpGroup();
                if ($cpbGroupId) {
                    $collection = Mage::getModel('aitcbp/group')->load($cpbGroupId);
                    $utilityHelper = Mage::helper('hutility');

                    if ($collection->getIsRound() && !$utilityHelper->hasActiveSpecialPricing($product->getId())) {
                        $price = round($price, 0);
                        $product->setFinalPrice($price);
                    }
                }
                /* set new price base on cost */
                $product->setPrice($price);

                if (!$product->getTierPriceCount()) {
                    $product->setMinimalPrice($price);
                } else {
                    if (!in_array($product->getTypeId(), array('bundle'))) {
                        $tierPrices   = $product->getTierPrice();
                        $minimalPrice = $price;

                        foreach ($tierPrices as $key => $value) {
                            $percent                   = $value['price'] / $originalPrice;
                            $tierPrices[$key]['price'] = $tierPrices[$key]['website_price'] = round(
                                $price * $percent, 4
                            );
                            if (Mage::getModel('aitcbp/group')->load($product->getCbpGroup())->getIsRound()) $tierPrices[$key]['price'] = $tierPrices[$key]['website_price'] = round($price * $percent, 0);
                            if ($minimalPrice > $tierPrices[$key]['price']) {
                                $minimalPrice = $tierPrices[$key]['price'];
                            }
                        }

                        $product->setTierPrice($tierPrices);
                        $product->setMinimalPrice($minimalPrice);
                    }
                }
            }
        }
    }

    public function reindexProductPrice($observer)
    {
        if (!Mage::helper('aitcbp')->isEnabled()) {
            return null;
        }

        $productId = $observer->getEvent()->getProduct();

        if (!is_numeric($productId)) {
            $productId = $productId->getId();
        }

        Mage::getModel('aitcbp/product_price_index')->reindexEntity($productId);
    }

    public function salesOrderAfterSave($observer)
    {
        if (!Mage::helper('aitcbp')->isEnabled()) {
            return null;
        }

        $order      = $observer->getDataObject();
        $orderState = $order->getData('state');

        if (!in_array(
            $orderState, array(
            Mage_Sales_Model_Order::STATE_PROCESSING,
            Mage_Sales_Model_Order::STATE_CLOSED
        )
        )
        ) {
            return;
        }

        $orderTotal = $order->getData('grand_total') * (($orderState == Mage_Sales_Model_Order::STATE_CLOSED) ? -1 : 1);

        $rules    = Mage::getModel('aitcbp/rule')->getCollection()->addFilter('is_active', 1);
        $groupIds = array();

        foreach ($rules as $rule) {
            $rule->load($rule->getId());
            $avgSale = Mage::helper('aitcbp')->getAvgSale($rule);

            if ((false !== $avgSale) && (false !== $rule->getRevenueSum())) {
                $ruleCondAfter = false;
                $evalCondAfter = '$ruleCondAfter = doubleval(' . $avgSale . ') ' . $rule->getRevenueClause()
                    . ' doubleval(' . $rule->getRevenueSum() . ');';
                eval($evalCondAfter);
                $ruleCondBefore = false;
                $evalCondBefore = '$ruleCondBefore = doubleval(' . ($avgSale - $orderTotal) . ') '
                    . $rule->getRevenueClause() . ' doubleval(' . $rule->getRevenueSum() . ');';
                eval($evalCondBefore);

                if ($ruleCondAfter !== $ruleCondBefore) {
                    $groupIds = array_merge($groupIds, explode(',', $rule->getInGroups()));
                }
            }
        }

        $productIds = array();

        foreach ($groupIds as $groupId) {
            $group      = Mage::helper('aitcbp')->getGroup($groupId);
            $productIds = array_merge($productIds, $group->getAssociatedProducts());
        }

        if (count($productIds)) {
            Mage::getModel('aitcbp/product_price_index')->reindexEntity($productIds);
            Mage::getResourceSingleton('catalogrule/rule')
                ->applyAllRulesForDateRange();
            $event = Mage::getSingleton('index/event')
                ->setNewData(array('reindex_price_product_ids' => $productIds));
            Mage::getResourceSingleton('catalog/product_indexer_price')
                ->aitocCatalogProductMassAction($event);
        }
    }
}

?>