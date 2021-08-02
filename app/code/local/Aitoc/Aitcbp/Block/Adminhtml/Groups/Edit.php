<?php
class Aitoc_Aitcbp_Block_Adminhtml_Groups_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
    {
    	parent::__construct();
    	$this->_objectId = 'entity_id';
    	$this->_blockGroup = 'aitcbp';
    	$this->_controller = 'adminhtml_groups';
    	
    	$this->_updateButton('save', 'label', Mage::helper('aitcbp')->__('Save Group'));
        $this->_updateButton('delete', 'label', Mage::helper('aitcbp')->__('Delete Group'));
    	
    	$this->_addButton('saveandcontinue', array(
			'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);
        
        /*$this->_addButton('alert', array(
			'label'     => Mage::helper('adminhtml')->__('Alert'),
            'onclick'   => 'alertData()',
            'class'     => '',
        ), -100);*/

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
            
            function alertData() {
            	alert(groupProducts.toQueryString());
            }
        ";
    }
    
    public function getHeaderText()
    {
    	if( Mage::registry('aitacbp_groups_data') && Mage::registry('aitacbp_groups_data')->getId() ) {
    		return Mage::helper('aitcbp')->__("Edit Group '%s'", $this->htmlEscape(Mage::registry('aitacbp_groups_data')->getGroupName()));
    	} else {
    		return Mage::helper('aitcbp')->__('Add Group');
    	}
    }
}
?>