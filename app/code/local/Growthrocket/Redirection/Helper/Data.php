<?php
class Growthrocket_Redirection_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getStoreCode()
    {
        return Mage::app()->getStore()->getCode();
    }

    public function getWebsiteId()
    {
        return Mage::app()->getStore()->getWebsiteId();
    }

    public function getTableName()
    {
        return 'gr_site_redirection';
    }

    /**
     * @param $categoryId
     * @return mixed
     */
    public function getRedirectUrlFromId($categoryId)
    {
        $table = $this->getTableName();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql        = "SELECT custom_url FROM {$table} WHERE type = 'category' and object_id = {$categoryId} AND website_id = {$this->getWebsiteId()}";
        return $connection->fetchCol($sql);
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function getProductPartNumberById($productId)
    {
        $table = $this->getTableName();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql        = "SELECT part_number FROM {$table} WHERE type = 'product' and object_id = {$productId} AND website_id = {$this->getWebsiteId()}";
        return $connection->fetchCol($sql);
    }

    /**
     * @return array
     */
    public function getModelToArray()
    {
        return array(
          'ascent' => 'ascent',
          'baja' => 'baja',
          'brz' => 'brz',
          'forester' => 'forester',
          'impreza' => 'impreza',
          'legacy' => 'legacy',
          'outback' => 'outback',
          'sti' => 'sti',
          'tribeca' => 'tribeca',
          'wrx' => 'wrx',
          'crosstrek' => 'xv-crosstrek'
        );
    }
}
	 