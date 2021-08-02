<?php

class Homebase_Auto_Block_Cart extends Mage_Checkout_Block_Cart{


    public function getContinueShoppingUrl()
    {
        if($this->_hasActiveFitment()){
            $url = $this->_getFitmentUrl();
        }else{
            $url =  $this->_getLastProductAdded();
        }

        if (is_null($url)) {
            $url = Mage::getSingleton('checkout/session')->getContinueShoppingUrl(true);
            if (!$url) {
                $url = Mage::getUrl();
            }
            $this->setData('continue_shopping_url', $url);
        }
        return $url;
    }

    /**
     * @return bool
     */
    protected function _hasActiveFitment()
    {
        return (bool) $this->_getActiveFitment() !== false;
    }

    /**
     * @return mixed
     */
    protected function _getActiveFitment()
    {
        $_cookie = Mage::getSingleton('core/cookie');
        return $_cookie->get('fitment');
    }

    /**
     * @return string
     */
    protected function _getFitmentUrl()
    {
        $url = Mage::getBaseUrl();
        if($this->_hasActiveFitment()){
            $url .= 'year/' . $this->_getActiveFitment() . '.html';
        }

        return $url;
    }

    protected function _getLastProductAdded()
    {
       $productQuote =  Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getItemsCollection()
            ->getLastItem()
            ->getProduct();

       if(!empty($productQuote->getAttributeText('auto_type'))){
          $autoType = $productQuote->getAttributeText('auto_type');
          $autoTypeValue = '';
          if(is_array($autoType)){
              $autoTypeValue = $autoType[0];
          }else{
              $autoTypeValue = $autoType;
          }

           $_helper = Mage::helper('hauto/path');
          
          if(!empty($autoTypeValue)){
              $autoTypeValue = $_helper->filterTextToUrl($autoTypeValue);
              return $_helper->generateLink($autoTypeValue,'category');
          }else {
              return Mage::getUrl();
          }
       }
    }
}