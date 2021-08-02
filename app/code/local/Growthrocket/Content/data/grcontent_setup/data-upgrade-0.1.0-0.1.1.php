<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/1/18
 * Time: 12:47 PM
 */

$defaultData = array(
    array(
        'name' => 'selection',
        'value' => 'options|accessories|products'
    ),
    array(
        'name' => 'pricing',
        'value' => 'discounted|affordable|competitive'
    ),
    array(
        'name' => 'convenience',
        'value' => 'easier|more convenient'
    ),
    array(
        'name' => 'type',
        'value' => 'premium|top-of-the-line'
    ),
    array(
        'name' => 'condition',
        'value' => 'genuine|authentic'
    ),
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