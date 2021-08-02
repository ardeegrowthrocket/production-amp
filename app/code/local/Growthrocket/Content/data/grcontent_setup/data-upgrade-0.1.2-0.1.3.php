<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/5/18
 * Time: 10:05 AM
 */

Mage::getModel('core/variable')
    ->setCode('oemtype')
    ->setName('oemtype')
    ->setPlainValue('premium|top-of-the-line')
    ->setHtmlValue('premium|top-of-the-line')
    ->save();