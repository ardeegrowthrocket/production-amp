<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 13/03/2017
 * Time: 12:10 AM
 */

class Homebase_Autopart_Block_Toggle extends Smartwave_Megamenu_Block_Toggle{
    public function _prepareLayout()
    {
        $layout = $this->getLayout();
        $topnav = $layout->getBlock('catalog.topnav');
        if (is_object($topnav)) {
            $topnav->setTemplate('smartwave/megamenu/html/topmenu.phtml');
            $head = $layout->getBlock('head');
            $head->addItem('skin_js', 'megamenu/js/megamenu.js');
            $head->addItem('skin_css', 'megamenu/css/font-awesome.min.css');
            $head->addItem('skin_css', 'megamenu/css/megamenu.css');
            $head->addItem('skin_css', 'megamenu/css/megamenu_responsive.css');
        }
    }
}