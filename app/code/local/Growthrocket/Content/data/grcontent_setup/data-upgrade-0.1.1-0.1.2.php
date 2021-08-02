<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 04/10/2018
 * Time: 1:19 PM
 */

$defaultData = array(
    array(
        'name' => 'convenience2',
        'value' => 'convenient|hassle-free|stress-free'
    )
);

foreach($defaultData as $datum) {
    $variable = Mage::getModel('core/variable')->loadByCode($datum['name']);

    if(is_null($variable->getVariableId())){
        Mage::getModel('core/variable')
            ->setCode($datum['name'])
            ->setName($datum['name'])
            ->setPlainValue($datum['value'])
            ->setHtmlValue($datum['value'])
            ->save();
    }
}