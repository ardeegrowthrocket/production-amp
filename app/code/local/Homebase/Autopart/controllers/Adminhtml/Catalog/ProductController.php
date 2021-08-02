<?php
/**
 * Created by PhpStorm.
 * User: olivercastro
 * Date: 09/03/2017
 * Time: 8:49 PM
 */
require_once 'Mage/Adminhtml/controllers/Catalog/ProductController.php';

class Homebase_Autopart_Adminhtml_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController{
    public function saveAction()
    {
        $storeId        = $this->getRequest()->getParam('store');
        $redirectBack   = $this->getRequest()->getParam('back', false);
        $productId      = $this->getRequest()->getParam('id');
        $isEdit         = (int)($this->getRequest()->getParam('id') != null);

        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->_filterStockData($data['product']['stock_data']);

            $product = $this->_initProductSave();

            try {
                $product->save();
                $productId = $product->getId();
                Mage::dispatchEvent('adminhtml_catalog_product_save_after',array(
                    'product'   => $product,
                    'request'   => $this->getRequest()
                ));
                if (isset($data['copy_to_stores'])) {
                    $this->_copyAttributesBetweenStores($data['copy_to_stores'], $product);
                }

                $this->_getSession()->addSuccess($this->__('The product has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage())
                    ->setProductData($data);
                $redirectBack = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            }
        }

        if ($redirectBack) {
            $this->_redirect('*/*/edit', array(
                'id'    => $productId,
                '_current'=>true
            ));
        } elseif($this->getRequest()->getParam('popup')) {
            $this->_redirect('*/*/created', array(
                '_current'   => true,
                'id'         => $productId,
                'edit'       => $isEdit
            ));
        } else {
            $this->_redirect('*/*/', array('store'=>$storeId));
        }
    }

    public function combinationAction(){
        $_request = $this->getRequest();
        $id = $_request->getParam('id');
        $this->loadLayout();

        if($id){
            $_product = Mage::getModel('catalog/product')->load($id);
            $_entries = $this->getLayout()
                ->getBlock('admin.combination.options')
                ->getChild('combinations_box');
            $_entries->setProduct($_product);
        }
        $this->renderLayout();
    }
}