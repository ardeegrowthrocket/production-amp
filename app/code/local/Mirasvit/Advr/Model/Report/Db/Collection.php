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
 * @package   mirasvit/extension_advr
 * @version   1.2.13
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */



class Mirasvit_Advr_Model_Report_Db_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @return Mirasvit_Advr_Model_Report_Db_Select (extends Varien_Db_Select)
     */
    public function getSelect()
    {
        $dbSelectInstance = parent::getSelect();
        if (! $dbSelectInstance instanceof Mirasvit_Advr_Model_Report_Db_Select)
        {
            $adapter = $dbSelectInstance->getAdapter();
            $this->_select = new Mirasvit_Advr_Model_Report_Db_Select($adapter);

            return $this->_select;
        }

        return $dbSelectInstance;
    }
}