<?php
require_once "Mage/Sales/controllers/OrderController.php";
require_once Mage::getBaseDir('lib') . '/dompdf/autoload.inc.php';
require_once Mage::getBaseDir('lib') . '/dompdf/lib/Cpdf.php';

use Dompdf\Dompdf;

class Growthrocket_PrintOrder_Sales_OrderController extends Mage_Sales_OrderController{

    protected $_pdfTitle = "order_no";

    /**
     * Print Order Action
     */
    public function printAction()
    {

        if (!$this->_loadValidOrder()) {
            return;
        }
        $isAllowedPdfFormat =  (bool)Mage::getStoreConfig('sales_pdf/pdf_order/enable_order_pdf');
        if($isAllowedPdfFormat) {
            $this->_toPDF();
        }else {
            $this->loadLayout('print');
            $this->renderLayout();
        }
    }

    protected function _toPDF()
    {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled',true);

        $this->loadLayout();
        $layout = $this->getLayout();
        $block = $layout->getBlock("sales.order.print");

        if($block->getOrder()){
            $this->_pdfTitle = "Order_no_" . $block->getOrder()->getIncrementId();
        }

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($block->toHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($this->_pdfTitle,array("Attachment" => false));
    }


}
				