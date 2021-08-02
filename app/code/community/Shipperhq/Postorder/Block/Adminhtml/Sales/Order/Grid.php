<?php
/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */


class Shipperhq_Postorder_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{

    /*
     * PICKUP-65 : Necessary to override order grid as no event available between when collection is created below and calling parent
     * _prepareCollection function, which loads the collection.
     *
     */
    protected function _prepareCollection()
    {

        $collection = Mage::getResourceModel($this->_getCollectionClass());

        $this->setCollection($collection);
        $collection->addFilterToMap('created_at', 'main_table.created_at');
        $collection->addFilterToMap('status', 'main_table.status');
        $collection->addFilterToMap('increment_id','main_table.increment_id');
        $collection->addFilterToMap('base_grand_total', 'main_table.base_grand_total');
        $collection->addFilterToMap('grand_total', 'main_table.grand_total');
        $collection->addFilterToMap('store_id', 'main_table.store_id');

        //call grandparent function to complete
        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

}
