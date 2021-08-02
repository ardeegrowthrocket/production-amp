<?php
class Growthrocket_Customexport_Adminhtml_CustomexportbackendController extends Mage_Adminhtml_Controller_Action
{
    protected $_productsArray = array();

    protected $_csvHeader = array('sku','stores','weight','ship_length','ship_width','ship_height','ship_separately');

    protected $_websiteArray = array();

    protected $_website;

    protected $_params;

    protected $_pageSize = 1000;

    protected $_curPage = 1;

    protected $_fileLocation;

	protected function _isAllowed()
	{
		return true;
	}

	protected function _construct()
    {
        $this->_params = $this->getRequest()->getParams();
        $this->_website = (int) $this->_params['website'];

        $this->_websiteArray = json_decode($this->_params['website_data'], true);
        $this->_websiteArray[8]['code'] = 'mgp';
        $this->_websiteArray[1]['code'] = 'amp';

        $this->_fileLocation = Mage::getBaseDir('media'). DS . 'tmp' . DS . 'product_dimensional_data.csv';
        parent::_construct();
    }

    public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Export Shipping Dimensional Data"));
	   $this->renderLayout();
    }


    /**
     * Export product
     */
    public function exportProductAction()
    {
        $this->_curPage = (int ) $this->_params['next_page'] + 1;
        if(!is_null($this->_website)){
            if($this->_curPage == 1){
                $this->_productsArray[] = $this->_csvHeader;
                unlink($this->_fileLocation);  
                fopen($this->_fileLocation,"w");
            }

            $pageSizeCollection = Mage::getModel('catalog/product')->getCollection();
            $pageSizeCollection->addAttributeToSelect(array('ship_height', 'ship_length', 'ship_separately', 'weight','ship_width'));
            if(!empty($this->_website)){
                $pageSizeCollection->addStoreFilter($this->_website);
            }

            $totalSize = $pageSizeCollection->getSize();
            $totalPage =  ceil($pageSizeCollection->getSize() / $this->_pageSize);

            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->addAttributeToSelect(array('ship_height', 'ship_length', 'ship_separately', 'weight','ship_width'));
            if(!empty($this->_website)){
                $collection->addStoreFilter($this->_website);
            }
            $collection->setCurPage($this->_curPage)->setPageSize($this->_pageSize);
            foreach ($collection as $product){
                $this->_productsArray[$product->getId()] = array(
                    $product->getSku(),
                    $this->_getStore($this->_website, $product),
                    $product->getWeight(),
                    $product->getShipLength(),
                    $product->getShipWidth(),
                    $product->getShipHeight(),
                    !empty($product->getShipSeparately()) ? 'Yes' : 'No'
                );
            }
        }
        $response = array(
            'currentPage' => $this->_curPage,
            'totalPage' => $totalPage,
            'totalSize' => $totalSize,
        );

        $this->_writeCSV($this->_productsArray, $this->_fileLocation);
        echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        exit;
    }

    /**
     * Download PDF file
     */
    public function downloadPdfAction()
    {
        header('HTTP/1.1 200 OK');
        header('Cache-Control: no-cache, must-revalidate');
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=" . basename($this->_fileLocation));
        readfile($this->_fileLocation);
        exit;
    }

    /**
     * @param $data
     * @param $filename
     */
    protected function _writeCSV($data, $filename)
    {
        $file = fopen($filename,"a+");
        foreach ($data as $line) {
            fputcsv($file, $line);
        }
    }

    protected function _getStore($website, $product)
    {
        $storeArray = array();
        if(!empty($website)){
            return $this->_websiteArray[$website]['code'];
        }else{
            foreach ($product->getStoreIds() as $store){
                if(isset($this->_websiteArray[$store])) {
                    $storeArray[] =  $this->_websiteArray[$store]['code'];
                }
            }

            return implode('|', $storeArray);
        }
    }

}