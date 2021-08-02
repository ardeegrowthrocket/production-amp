<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/12/18
 * Time: 11:40 AM
 */

class Growthrocket_Schema_Block_Universal extends Growthrocket_Schema_Block_Schema{
    const WEB_TYPE = 'WebPage';
    const SERVICE_TYPE = 'Service';
    protected function getSchema(){

	$identifier = Mage::getSingleton('cms/page')->getIdentifier();

	if(Mage::getBlockSingleton('page/html_header')->getIsHomePage()) {
		return;
	}
        #return;
	$breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs');
	$data = array();
	if($breadcrumbsBlock){
        $crumbs = $breadcrumbsBlock->getAllCrumbs();
        $crumbSection = array();
        foreach($crumbs as $crumb){
            array_push($crumbSection, $crumb['label']);
        }
        $data = array(
            '@context' => self::CONTEXT,
            '@type' => self::WEB_TYPE,
            'breadcrumb' => empty($crumbSection) ? '' : implode(' / ', $crumbSection),
            'mainEntity' => array(
                '@type' => self::SERVICE_TYPE,
                'url' => $this->__getCurrentUrl(),
            ),
        );
    }

        return json_encode($data);
        
    }
    private function __getCurrentUrl(){
        return $this->helper('core/url')->getCurrentUrl();
    }
    protected function _prepareLayout()
    {
        return $this;
    }
}
