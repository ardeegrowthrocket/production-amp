<?php
class Homebase_Autosearch_Model_CatalogSearch_Resource_Fulltext_Engine extends Mage_CatalogSearch_Model_Resource_Fulltext_Engine
{
    public function saveEntityIndexes($storeId, $entityIndexes, $entity = 'product')
    {
        $data    = array();
        $storeId = (int)$storeId;
        foreach ($entityIndexes as $entityId => $index) {
            $data[] = array(
                'product_id'    => (int)$entityId,
                'store_id'      => $storeId,
                'data_index'    => $this->appendFitment($index,$entityId)
            );
        }

        if ($data) {
            Mage::getResourceHelper('catalogsearch')
                ->insertOnDuplicate($this->getMainTable(), $data, array('data_index'));
        }
        return $this;
    }

    /**
     * @param $data_index
     * @param $entity_id
     *
     * Append fitment to catalogsearch_fulltext
     */
    public function appendFitment($data_index, $entity_id){

        /** @var Magento_Db_Adapter_Pdo_Mysql $_reader */
        $_reader = $this->getReadConnection();

        $fitmentTable = $this->getTable('hautopart/combination_list');
        $fitmentLabelTable = $this->getTable('hautopart/combination_label');

        $query = $_reader->select()
            ->from(array('fitment' => $fitmentTable),array('product_id'))
            ->join(array('year' => $fitmentLabelTable),'year.option=fitment.year','year.label as year')
            ->join(array('make' => $fitmentLabelTable),'make.option=fitment.make','make.label as make')
            ->join(array('model' => $fitmentLabelTable),'model.option=fitment.model','model.label as model')
            ->where('fitment.product_id=?', $entity_id);

        $results = $_reader->fetchAll($query);
        $data = array();
        foreach($results as $result){
            $fitmentArray = array(
                'year' => $result['year'],
                'make' => $result['make'],
                'model' => $result['model']
            );
            $data[] = implode(' ', $fitmentArray);
        }
        $dataIndx = implode('|', $data);

        return $data_index . '|' . $dataIndx;
    }
}
		