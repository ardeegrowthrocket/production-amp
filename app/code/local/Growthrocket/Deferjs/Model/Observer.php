<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25/06/2018
 * Time: 12:03 PM
 */
class Growthrocket_Deferjs_Model_Observer{
    public function doDeferScript($observer){
        if(!Mage::getStoreConfig('grdeferjs/defer/use')){
            return;
        }
        $response = $observer->getControllerAction()->getResponse();
        if(!$response) {
            return $this;
        }
        $html = $response->getBody();
        if($html == '')
            return;
        $jsPattern = '#(<\!--\[if[^\>]*>\s*<script.*</script>\s*<\!\[endif\]-->)|(<\!--\s*<script(?! nodefer).*</script>\s*-->)|(<script(?! nodefer).*</script>)#isU';
        preg_match_all($jsPattern, $html, $_matches);

        foreach ($_matches[0] as $key => $jsCode) {

            if (strpos($jsCode, 'googletagmanager.com') !== false) {
                unset($_matches[0][$key]);
            } else {
                $html = str_replace($jsCode, "", $html);
            }
        }

        $_end = implode('', $_matches[0]);
        //$html = preg_replace($jsPattern,'',$html);

        $html .= $_end;

        $response->setBody($html);
    }
}