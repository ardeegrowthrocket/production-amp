<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/extension_mcore
 * @version   1.0.24
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */



class Mirasvit_MstCore_Helper_String extends Varien_Object
{
    public function generateRandHeavy($length) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%^&*()_+|?><~';
        return $this->generateRand($length, $characters);
    }

    public function generateRandNum($length) {
        $characters = '0123456789';
        return $this->generateRand($length, $characters);
    }

    public function generateRandString($length) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return $this->generateRand($length, $characters);
    }

    public function generateRand($length, $characters)
    {
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}

