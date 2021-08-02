<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 9/12/18
 * Time: 3:37 PM
 */

class Growthrocket_Event_Helper_Data extends Mage_Core_Helper_Abstract{
    const EVENT_NAME_YMM = 'ymm_combobox_process';
    public function hasPendingEvent(){
        /** @var Growthrocket_Event_Model_Resource_Event $resource */
        $resource = Mage::getResourceModel('grevent/event');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $resource->getReadConnection();
        $table = $resource->getMainTable();
        $select = $reader->select()
            ->from($table);

        if($select->query()->rowCount() == 0){
            return false;
        }else{
            $select = $reader->select()
                ->from($table)
                ->where('updated_at IS NULL')
                ->where('event_name = ?', self::EVENT_NAME_YMM)
                ->where('status = ? ', 1);
            if($select->query()->rowCount() == 0){
                return false;
            }else{
                return true;
            }
        }
    }
    public function queueYmmComboEvent(){
        $event = Mage::getModel('grevent/event');
        $event->setData(array(
            'event_name' => self::EVENT_NAME_YMM,
        ));
        $event->save();
    }
    public function completeYmmComboEvent(){
        /** @var Growthrocket_Event_Model_Resource_Event $resource */
        $resource = Mage::getResourceModel('grevent/event');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $resource->getReadConnection();
        $table = $resource->getMainTable();
        $reader->update($table, array(
            'status' => 0,
            'updated_at' => Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s')
        ),'status = 1');
    }
    public function removeCompletedEvents(){
        /** @var Growthrocket_Event_Model_Resource_Event $resource */
        $resource = Mage::getResourceModel('grevent/event');
        /** @var Magento_Db_Adapter_Pdo_Mysql $reader */
        $reader = $resource->getReadConnection();
        $table = $resource->getMainTable();
        $sql = sprintf('DELETE FROM %s WHERE updated_at IS NOT NULL AND status = 0', $table);
        $reader->exec($sql);
    }
}