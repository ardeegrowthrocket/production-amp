<?php
class Growthrocket_Compatibilitychecker_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getLabelById($optionId = null)
    {
        $cacheId = 'combination_label_controller';
        $combinationLabel = array();

        if (($data_to_be_cached = Mage::app()->getCache()->load($cacheId))) {
            $combinationLabel = unserialize($data_to_be_cached);
        } else {

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = 'SELECT * FROM ' . $resource->getTableName('hautopart/combination_label') . ' WHERE store_id = 0';
            $results = $readConnection->fetchAll($query);

            foreach ($results as $item) {
                $combinationLabel[$item['option']] = $item['name'];
            }

            Mage::app()->getCache()->save(serialize($combinationLabel), $cacheId);
        }

        if(isset($combinationLabel[$optionId])){
            return $combinationLabel[$optionId];
        }else{
            return null;
        }
    }

    /**
     * @param $ymmLabel
     * @return mixed|string
     */
    public function formatUrl($ymmLabel)
    {

        $ymmLabel = strtolower($ymmLabel);
        $ymmLabel = str_replace(' ','-',$ymmLabel);
        $ymmLabel = str_replace('&','and',$ymmLabel);

        return $ymmLabel;
    }

}
	 