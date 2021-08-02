<?php

class Homebase_Finder_Helper_Data extends Mage_Core_Helper_Abstract {
    public function getCategoryId($year, $make, $model){
        $_record = Mage::getModel('finder2/finder2')->getCollection()
                ->addFieldToFilter('year', $year)
                ->addFieldToFilter('make', $make)
                ->addFieldToFilter('model', $model)
                ->getFirstItem();
//        $map = array(
//            '2017' => array(
//                'Chrysler' => array(
//                    'Pacifica' => 392
//                ),
//            ),
//            '2016' => array(
//                'Chrysler' => array(
//                    '200'   => 357,
//                    '300'   => 78,
//                    'Town & Country' => 81
//                ),
//            )
//        );
        return $_record->getCategory();
    }
}