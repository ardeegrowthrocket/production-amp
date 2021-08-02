<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/27/18
 * Time: 1:29 PM
 */

class Growthrocket_Content_Helper_Data extends Mage_Core_Helper_Abstract{
    const XML_NODE_CATEGORY_TEMPLATE_FILTER = 'global/grcontent/category/template_filter';
    const XML_NODE_MODEL_TEMPLATE_FILTER = 'global/grcontent/model/template_filter';
    const XML_NODE_PART_TEMPLATE_FILTER = 'global/grcontent/part/template_filter';
    const XML_NODE_PART_MAKE_TEMPLATE_FILTER = 'global/grcontent/partmake/template_filter';
    const XML_NODE_PART_MODEL_TEMPLATE_FILTER = 'global/grcontent/partmodel/template_filter';

    public function getCategoryTemplateProcessor(){
        $model = (string) Mage::getConfig()->getNode(self::XML_NODE_CATEGORY_TEMPLATE_FILTER);
        return Mage::getModel($model);
    }

    public function getPartTemplateProcessor(){
        $model = (string) Mage::getConfig()->getNode(self::XML_NODE_PART_TEMPLATE_FILTER);
        return Mage::getModel($model);
    }

    public function getPartMakeTemplateProcessor(){
        $model = (string) Mage::getConfig()->getNode(self::XML_NODE_PART_MAKE_TEMPLATE_FILTER);
        return Mage::getModel($model);
    }
    public function getPartModelTemplateProcessor(){
        $model = (string) Mage::getConfig()->getNode(self::XML_NODE_PART_MODEL_TEMPLATE_FILTER);
        return Mage::getModel($model);
    }
    public function getModelTemplateProcessor(){
        $model = (string) Mage::getConfig()->getNode(self::XML_NODE_MODEL_TEMPLATE_FILTER);
        return Mage::getModel($model);
    }
}