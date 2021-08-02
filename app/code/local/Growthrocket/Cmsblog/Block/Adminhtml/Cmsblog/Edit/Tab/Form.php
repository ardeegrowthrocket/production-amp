<?php
class Growthrocket_Cmsblog_Block_Adminhtml_Cmsblog_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected $_blogData;

    /**
     * Load Wysiwyg on demand and Prepare layout
     */
    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        if(!empty(Mage::registry('cmsblog_data'))){
            $this->_blogData = Mage::registry('cmsblog_data');
        }
        return parent::_prepareLayout();
    }


    protected function _prepareForm()
    {

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("cmsblog_form", array("legend"=>Mage::helper("cmsblog")->__("Item information"), 'class' => 'fieldset-wide'));

        $fieldset->addField("title", "text", array(
        "label" => Mage::helper("cmsblog")->__("Title"),
        "class" => "required-entry",
        "required" => true,
        "name" => "title",
        ));

        if(!empty($this->_getBlogUrl())){
            $afterElementHtml = '<p class="nm"><small>view page: <a href="'.$this->_getBlogUrl().'" target="_blank">' .$this->_getBlogUrl(). '</a></small></p>';
        }

        $fieldset->addField("identifier", "text", array(
            "label" => Mage::helper("cmsblog")->__("Identifier"),
            "class" => "",
            "required" => false,
            "name" => "identifier"
        ));

        $fieldset->addField('is_active', 'select', array(
            'label'     => Mage::helper('cmsblog')->__('Status'),
            'title'     => Mage::helper('cmsblog')->__('Status'),
            'name'      => 'is_active',
            'required'  => true,
            'options'   => array(
                '0' => Mage::helper('cmsblog')->__('Disabled'),
                '1' => Mage::helper('cmsblog')->__('Enabled'),
            ),
        ));

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $field =$fieldset->addField('store_ids', 'select', array(
                'name'      => 'store_ids[]',
                'label'     => Mage::helper('cmsblog')->__('Store View'),
                'title'     => Mage::helper('cmsblog')->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $field->setRenderer($renderer);
        }

        $configSettings['add_variables'] = false;
        $fieldset->addField("body", "editor", array(
            "label" => Mage::helper("cmsblog")->__("Content"),
            "name" => "body",
            'required'  => true,
            'wysiwyg' => true,
            'style' => 'height:500px;',
            'config'    => Mage::getSingleton('cmsblog/wysiwyg_config')->getConfig($configSettings),

        ));


        if (Mage::getSingleton("adminhtml/session")->getCmsblogData())
        {
            $form->setValues(Mage::getSingleton("adminhtml/session")->getCmsblogData());
            Mage::getSingleton("adminhtml/session")->setCmsblogData(null);
        }
        elseif(Mage::registry("cmsblog_data")) {
            $form->setValues(Mage::registry("cmsblog_data")->getData());
        }
        return parent::_prepareForm();
    }

    protected function _getBlogUrl()
    {
        $baseUrl  =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true );
        $frontName =  Mage::getConfig()->getNode('frontend/routers/cmsblog/args/frontName');
        if(empty($this->_blogData->getId())) {
            return;
        }
        $identifier = $this->_blogData['identifier'];
        return $baseUrl . $frontName . '/' . $identifier . '.html';
    }
}
