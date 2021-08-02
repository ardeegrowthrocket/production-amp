<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/8/18
 * Time: 3:53 PM
 */

Mage::getModel('core/variable')
    ->setCode('comfort')
    ->setName('comfort')
    ->setPlainValue('comfort and convenience|safety')
    ->setHtmlValue('comfort and convenience|safety')
    ->save();