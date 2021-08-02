<?php

class MagicToolbox_Sirv_Block_System_Config_Form_Field_Button_Flush extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Remove scope label
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getScriptHtml() . $this->getButtonHtml();
    }

    /**
     * Return js script
     *
     * @return string
     */
    protected function getScriptHtml()
    {
        $request = Mage::app()->getRequest();
        $section = $request->getParam('section');
        $website = $request->getParam('website');
        $store   = $request->getParam('store');

        $url = 'adminhtml/sirv/flush';
        if ($section !== null) {
            $url .= '/section/'.$section;
        }
        if ($website !== null) {
            $url .= '/website/'.$website;
        }
        if ($store !== null) {
            $url .= '/store/'.$store;
        }
        $url = Mage::helper('adminhtml')->getUrl($url);

        $html = "
        <script type=\"text/javascript\">
        //<![CDATA[
            function sirvFlush()
            {
                var button = $('sirv_flush_button');

                Form.Element.disable('sirv_flush_button');
                button.addClassName('disabled');

                new Ajax.Request('{$url}', {
                    parameters: {},
                    onSuccess: function(response) {
                        try {
                            var response = response.responseJSON || response.responseText.evalJSON(true) || {};
                            if (response.success) {
                                button.removeClassName('fail').addClassName('success')
                            } else {
                                button.removeClassName('success').addClassName('fail')
                            }
                        } catch (e) {
                            button.removeClassName('success').addClassName('fail')
                        }
                        Form.Element.enable('sirv_flush_button');
                        button.removeClassName('disabled');
                    }
                });
            }
        //]]>
        </script>
        ";
        return $html;
    }

    /**
     * Generate button html
     *
     * @return string
     */
    protected function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'        => 'sirv_flush_button',
                'label'     => $this->helper('adminhtml')->__('Flush cache'),
                'onclick'   => 'javascript:sirvFlush(); return false;'
            ));

        return $button->toHtml();
    }
}
