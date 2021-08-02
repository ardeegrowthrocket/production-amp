<?php
class Growthrocket_Slider_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param $image
     * @return string
     */
    public function getMediaUrl($image)
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $image;
    }

    /**
     * @return mixed
     */
    public function getSliderSpeed()
    {
        $configValue = Mage::getStoreConfig('gr_slider_section/gr_slider_group/speed');
        return $configValue;
    }

    /**
     * @return string
     */
    public function isAutoPlay()
    {
        $configValue = Mage::getStoreConfig('gr_slider_section/gr_slider_group/autoplay');
        return (boolval($configValue) ? 'true' : 'false');
    }

    /**
     * @return string
     */
    public function isSliderLoop()
    {
        $configValue = Mage::getStoreConfig('gr_slider_section/gr_slider_group/loop');
        return (boolval($configValue) ? 'true' : 'false');
    }
}
	 