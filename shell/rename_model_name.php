<?php

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    public function run()
    {

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        $combinationLabelTable = "auto_combination_list_labels";

        /** Drop Column */
        $checkColumn = "SHOW COLUMNS FROM {$combinationLabelTable} LIKE 'name'";
        if(!empty($writeConnection->fetchOne($checkColumn))) {
            $dropQuery = "ALTER TABLE {$combinationLabelTable} DROP name";
            $writeConnection->query($dropQuery);
        }

        /** Add Column */
        $AddColumnquery = "ALTER TABLE  {$combinationLabelTable} ADD name varchar(255) AFTER label";
        $writeConnection->query($AddColumnquery);

        /** set Column value */
        $setDataToColumn = "UPDATE {$combinationLabelTable} SET name=label";
        $writeConnection->query($setDataToColumn);

        /** set Column value */
        $deleteDodge1500Model = "DELETE FROM auto_combination WHERE make=303 and model=311";
        $writeConnection->query($deleteDodge1500Model);


        /**
         * Update Model name by option ID
         */

        $updateModel = array(
            311 => '1500 DS'
        );

        if(!empty($updateModel)) {

            foreach ($updateModel as $optionId => $name) {
                $updateDataColumn = "Update {$combinationLabelTable} SET `name`='{$name}' WHERE `option` = '{$optionId}' ";
                $writeConnection->query($updateDataColumn);
            }
        }

    }




}
$shell = new Mage_Shell_Compiler();
$shell->run();