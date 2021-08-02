<?php
class Smartwave_Megamenu_IndexController extends Mage_Core_Controller_Front_Action
{
	public function showpopupAction()
	{
		if($this->getRequest()->isXmlHttpRequest()){ //Check if it was an AJAX request
			$response = array();
            $category_id = $this->getRequest()->getParam("category_id");
			$html = array();
            $level = 0;
            $catModel = Mage::getModel('catalog/category')->load($category_id);
            $_menuHelper = Mage::helper('megamenu');
            $block = $_menuHelper->getMegamenuBlock();
            $blockType = $block->_getBlocks($catModel, 'sw_cat_block_type');
            if (!$blockType || $blockType == 'default')
                $blockType = $_menuHelper->getConfig('general/wide_style');    //Default Format is wide style.
            $activeChildren = $_menuHelper->getActiveChildren($catModel, $level);
            
            $block_top = $block_left = $block_right = $block_bottom = false;
            if ($blockType == 'wide' || $blockType == 'staticwidth') {
                // ---Get Static Blocks for category, only format is wide style, it is enable.
                if ($level == 0) {
                //  block top of category
                    $block_top = $block->_getBlocks($catModel, 'sw_cat_block_top');
                //  block left of category
                    $block_left = $block->_getBlocks($catModel, 'sw_cat_block_left');
                //  block left width of category
                    $block_left_width = (int)$block->_getBlocks($catModel, 'sw_cat_left_block_width');
                    if (!$block_left_width)
                        $block_left_width = 3;
                //  block right of category
                    $block_right = $block->_getBlocks($catModel, 'sw_cat_block_right');
                //  block left width of category
                    $block_right_width = (int)$block->_getBlocks($catModel, 'sw_cat_right_block_width');
                    if (!$block_right_width)
                        $block_right_width = 3;
                //  block bottom of category
                    $block_bottom = $block->_getBlocks($catModel, 'sw_cat_block_bottom');
                }
            }
            
            if ($level == 0 && ($blockType == 'wide' || $blockType == 'staticwidth') ) {
                if ($block_top)
                    $html[] = '<div class="top-mega-block">' . $block_top . '</div>';
                $html[] = '<div class="mega-columns row '.count(Mage::getModel('catalog/category')->getCategories($catModel->getId())).'">';
                if ($block_left)
                    $html[] = '<div class="left-mega-block col-sm-'.$block_left_width.'">' . $block_left . '</div>';
                if (count($activeChildren)) {
                    //columns for category
                    $columns = (int)$catModel->getData('sw_cat_block_columns');
                    if (!$columns)
                        $columns = 6;
                    
                    //columns item width    
                    $columnsWidth = 12;
                    if ($block_left)
                        $columnsWidth = $columnsWidth - $block_left_width;
                    if ($block_right)
                        $columnsWidth = $columnsWidth - $block_right_width;
                        
                    //draw category menu items
                    $html[] = '<div class="block1 col-sm-'.$columnsWidth.'">';
                    $html[] = '<div class="row">';                    
                    $html[] = '<ul>';
                    $html[] = $block->drawColumns($activeChildren, $columns, count($activeChildren),'', 'wide');
                    $html[] = '</ul>';
                    $html[] = '</div>';
                    $html[] = '</div>';
                }
                if ($block_right)
                    $html[] = '<div class="right-mega-block col-sm-'.$block_right_width.'">' . $block_right . '</div>';
                $html[] = '</div>';
                /* Fixed from version 1.0.1 */
                //verion 1.0.1 start
                if ($block_bottom)
                    $html[] = '<div class="bottom-mega-block">' . $block_bottom . '</div>';
                //version 1.0.1 end
            } else if ($level == 0 && $blockType == 'narrow') {                
                $html[] = '<ul>';
                $html[] = $block->drawColumns($activeChildren, '', count($activeChildren),'','narrow', $mode);
                $html[] = '</ul>';
            }
            $html = implode("\n", $html);
            
            $response['popup_content'] = $html;
			$response['status'] = 'SUCCESS';
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
            
			return;
		} else {
			$this->_forward('noRoute');
		}
	}
    public function showmobilemenuAction()
    {
        if($this->getRequest()->isXmlHttpRequest()){ //Check if it was an AJAX request
            $response = array();
            $_menuHelper = Mage::helper('megamenu');
            
            $response['popup_content'] = $_menuHelper->getMobileMenuContent();
            $response['status'] = 'SUCCESS';
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
            
            return;
        } else {
            $this->_forward('noRoute');
        }
    }
}
