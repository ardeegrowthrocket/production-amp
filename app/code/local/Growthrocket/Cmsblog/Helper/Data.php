<?php
class Growthrocket_Cmsblog_Helper_Data extends Mage_Core_Helper_Abstract
{

    function slugify($text){
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicated - symbols
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public function getModulePath()
    {
        return Mage::getConfig()->getNode('frontend/routers/cmsblog/args/frontName');
    }

    public function limitContent($content, $limit = 300)
    {
        // strip tags to avoid breaking any html
       $body =  Mage::helper('cms')->getBlockTemplateProcessor()->filter($content->getBody());
        $string = strip_tags($body);
        if (strlen($string) > $limit) {

            // truncate string
            $stringCut = substr($string, 0, $limit);
            $endPoint = strrpos($stringCut, ' ');

            //if the string doesn't contain any space then it will cut without word basis.
            $string = $endPoint? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
            $string .= '... <a href="' . $this->getLink($content) . '">Read More</a>';
        }

        return $string;
    }

    public function getLink($item)
    {
        return Mage::getBaseUrl() . 'blog/' . $item->getIdentifier() . '.html';
    }

    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isEnableBlog()
    {
        return (bool) Mage::getStoreConfig('cmsblog/info/enable_module',Mage::app()->getStore());
    }

    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isEnableDateDisplay()
    {
        return (bool) Mage::getStoreConfig('cmsblog/info/enable_date',Mage::app()->getStore());
    }

}
	 