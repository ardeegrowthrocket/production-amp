<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 6/8/17
 * Time: 2:24 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    /**
     * Run script
     *
     */
    public function run()
    {
        $swattributes = array(
            'sw_cat_block_bottom',
            'sw_cat_block_columns',
            'sw_cat_block_left',
            'sw_cat_block_right',
            'sw_cat_block_top',
            'sw_cat_block_type',
            'sw_cat_float_type',
            'sw_cat_hide_menu_item',
            'sw_cat_label',
            'sw_cat_left_block_width',
            'sw_cat_right_block_width',
            'sw_cat_static_width',
            'sw_font_icon',
            'sw_product_attribute_tab_1',
            'sw_product_attribute_tab_2',
            'sw_product_attribute_tab_3',
            'sw_product_attribute_tab_4',
            'sw_product_attribute_tab_5',
            'sw_product_staticblock_tab_1',
            'sw_product_staticblock_tab_2',
            'sw_product_staticblock_tab_3',
            'sw_product_staticblock_tab_4',
            'sw_product_staticblock_tab_5',
            'sw_product_staticblock_tab_6',
            'sw_product_staticblock_tab_7',
            'sw_product_staticblock_tab_8',
            'sw_product_staticblock_tab_9'
        );
        /** @var Mage_Eav_Model_Entity_Type $_entityType */
        $_entityType = Mage::getModel('eav/entity_type');
        $_entityType->loadByCode(Mage_Catalog_Model_Category::ENTITY);
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $_attributes */
        $_attributes = $_entityType->getAttributeCollection();
        $_attributes->addFilter('is_user_defined',1);
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $_attribute */
        foreach($_attributes as $_attribute){
           if(in_array($_attribute->getAttributeCode(),$swattributes)){
               $_attribute->delete();
           }
        }

        echo PHP_EOL;
        echo $_entityType->getEntityTable() . PHP_EOL;
    }
}
$shell = new Mage_Shell_Compiler();
$shell->run();