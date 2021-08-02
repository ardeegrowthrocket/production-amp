<?php
class Growthrocket_Grsitemap_Block_Sitemap extends Mage_Core_Block_Template{


    protected $_resource;

    protected $_readConnection;

    protected $_store;

    protected $_collection;

    protected $_tableName;

    protected $_route;

    protected $_combinationLabel;

    protected function _construct()
    {
        $this->_store =  Mage::app()->getStore();
        $this->_tableName = "fitment_route_store_{$this->_store->getId()}";
        $this->_resource = Mage::getSingleton('core/resource');
        $this->_readConnection = $this->_resource->getConnection('core_read');
        $this->_getCombinationLabel();
        $this->_getRouteValue();

        $query = "SELECT id FROM {$this->_tableName} WHERE route = '{$this->_route}'";
        $results = $this->_readConnection->fetchAll($query);
        $collection = new Varien_Data_Collection();
        foreach($results as $row){
            $rowObj = new Varien_Object();
            $rowObj->setData($row);
            $collection->addItem($rowObj);
        }

        $this->setCollection($collection);

        parent::_construct();
    }

    protected function _getRouteValue()
    {
        $routes = $this->getRoute();
        $routeParam = $this->getRequest()->getParam('route');
        if(array_key_exists($routeParam, $routes)){
            $this->_route = $routeParam;
        }else{
            $this->_route = 'part';
        }

        return $this->_route;
    }

    public function getPartCollection()
    {
        $page=($this->getRequest()->getParam('p'))? $this->getRequest()->getParam('p') : 1;
        $pageSize= ($this->getRequest()->getParam('limit'))? $this->getRequest()->getParam('limit') : 100;
        $offset = ($page - 1) * $pageSize;

        $query = "SELECT route,path,combination FROM {$this->_tableName} WHERE route = '{$this->_route}' ORDER BY path ASC LIMIT $offset, $pageSize ";
        $results = $this->_readConnection->fetchAll($query);
        $collection = new Varien_Data_Collection();
        foreach($results as $row){
            $rowObj = new Varien_Object();
            $rowObj->setData($row);
            $collection->addItem($rowObj);
        }

        return $collection;
    }


    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $pager = $this->getLayout()->createBlock('page/html_pager', 'custom.pager');
        $pager->setAvailableLimit(array(100=>100));
        $pager->setCollection($this->getCollection());
        $this->setChild('pager', $pager);
        $this->getCollection()->load();
        return $this;
    }

    public function getName($item)
    {
        $data = unserialize($item['combination']);
        $route = $item['route'];
        $label = $this->_combinationLabel;
        $name = array();
        switch ($route){
            case 'partmake':
                $name[] = $label[$data['make']];
                $name[] = $data['part'];
                break;

            case 'partmodel':
                $name[] = $label[$data['make']];
                $name[] = $label[$data['model']];
                $name[] = $data['part'];
                break;

            case 'partymm':
                $name[] = $label[$data['year']];
                $name[] = $label[$data['make']];
                $name[] = $label[$data['model']];
                $name[] = $data['part'];
                break;

            default:
                $name[] = $data['part'];
        }

        return implode(' ', $name);
    }

    /**
     * @param string $route
     * @param array $path
     * @return string
     */
    public function getUrl($route, $path)
    {
        $routeData = $this->getRoute();
        $routeUrl = str_replace(' ','-', strtolower($routeData[$route]));
        return Mage::getBaseUrl() . "{$routeUrl}/{$path}.html";
    }

    /**
     * @return array
     */
    public function getRoute()
    {
        $route = array(
            'part' => 'Part',
            'partmake' => 'Part Make',
            'partmodel' => 'Part Model',
            'partymm' => 'Part YMM',
        );

        return $route;
    }

    /**
     * @param $value
     * @return string
     * @throws Exception
     */
    public function getRouteUrl($value)
    {
        $request = $this->getRequest();
        $urlWithoutParameters = $this->getBaseUrl() . 'sitemap.html';
        return $urlWithoutParameters .= "?route=$value";
    }

    /**
     * get Combination Label
     */
    protected function _getCombinationLabel()
    {
        $query = 'SELECT `option`,`label` FROM `auto_combination_list_labels`';
        $results = $this->_readConnection->fetchAll($query);
        foreach ($results as $row){
            $this->_combinationLabel[$row['option']] = ucfirst($row['label']);
        }
    }

    public function getToolbarHtml()
    {
        return $this->getChildHtml('pager');
    }


}