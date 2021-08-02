<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 25/06/2018
 * Time: 12:03 PM
 */
class Growthrocket_Defercss_Model_Observer{
    public function doDeferCss($observer){
        if(!Mage::getStoreConfig('grdefercss/defer/use')){
            return;
        }
        $response = $observer->getControllerAction()->getResponse();
        if(!$response) {
            return $this;
        }

        $html = $response->getBody();

        if($html == '')
            return;

        $cssPattern = '#(<\!--\[if[^\>]*>\s*<link.*\s*<\!\[endif\]-->)|(<link[^>]*rel="([stylesheet]*)"[^>]*>)#isU';
        preg_match_all($cssPattern, $html, $_matches);
        $_css_if = implode("\n", $_matches[0]);
        $html = preg_replace($cssPattern, '' , $html);

        $cssPattern = '#</body>\s*</html>#isU';

        preg_match_all($cssPattern, $html, $_matches);
        $_end = implode('', $_matches[0]);
        $html = preg_replace($cssPattern,'',$html);

        $html .= $_css_if.$_end;

        $response->setBody($html);
    }
}