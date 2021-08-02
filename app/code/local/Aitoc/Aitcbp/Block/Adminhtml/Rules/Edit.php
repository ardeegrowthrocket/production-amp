<?php
class Aitoc_Aitcbp_Block_Adminhtml_Rules_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
    {
    	parent::__construct();
    	$this->_objectId = 'entity_id';
    	$this->_blockGroup = 'aitcbp';
    	$this->_controller = 'adminhtml_rules';
    	
    	$this->_updateButton('save', 'label', Mage::helper('aitcbp')->__('Save Rule'));
        $this->_updateButton('delete', 'label', Mage::helper('aitcbp')->__('Delete Rule'));
    	
    	$this->_addButton('saveandcontinue', array(
			'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('aitagents_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'aitagents_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'aitagents_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }
    
    public function getHeaderText()
    {
    	if( Mage::registry('aitacbp_rules_data') && Mage::registry('aitacbp_rules_data')->getId() ) {
    		return Mage::helper('aitcbp')->__("Edit Rule '%s'", $this->htmlEscape(Mage::registry('aitacbp_rules_data')->getRuleName()));
    	} else {
    		return Mage::helper('aitcbp')->__('Add Rule');
    	}
    }
}
?>