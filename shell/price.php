<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 07/03/2018
 * Time: 7:18 PM
 */

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{

    /**
     * Run script
     *
     */
    public function run()
    {
        // TODO: Implement run() method.
        $dir = $this->getDir();

        $filename = 'CH_Master_Mar2017.csv';
        $ctr = 0;
        $msrpCount = 0;
        echo "Script run " . now();
        if($filename = $this->getArg('file') && $this->getArg('old')){
            if($this->getArg('price')){
                $fh = fopen($dir . $filename,'r');
                while($datum = fgetcsv($fh,0)){
                    /** @var Mage_Catalog_Model_Product $_product */
                    $_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$datum[0]);
                    if($_product && $_product->getId()){
                        echo $_product->getSku().',';
//                        Mage::log($_product->getSku(), null, 'affected_sku.log',true);
                        $_product->setCost($datum[4]);
                        $_product->save();
                        $ctr++;
                    }
                }
                fclose($fh);
            }
            echo "\n";
            if($this->getArg('msrp')){
                $fh = fopen($dir . $filename,'r');
                while($datum = fgetcsv($fh,0)){
                    if(!empty($datum[3])){
                        $_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$datum[0]);
                        if(is_numeric($datum[3])){
                            if($_product && $_product->getId()){
                                echo $_product->getSku().',';
                                $msrp = floatval($datum[3]);
                                $_product->setMsrp($msrp);
                                $_product->save();
                            }
                        }
                    }
                    $msrpCount++;
//                    if($msrpCount > 25)
//                        break;
                }
                fclose($fh);
            }
        }
        else if($this->getArg('run') && $this->getArg('file')){
            $model = Mage::getModel('grupdater/csv');
            $filename = $this->getArg('file');
            $filepath = $dir . $filename;
            if(file_exists($filepath)){
                $model->process($filepath);
            }
        }
        else if($this->getArg('extract')){
            /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->addAttributeToSelect(array('cost','msrp'));
            $filepath = $this->getDir() . 'extract' . date("Y-m-d",time()) . '.csv';
            $_csv = new Varien_File_Csv();
            $records = array();
            foreach($collection as $item){
                $record = array(
                    'sku' => $item->getSku(),
                    'cost' => $item->getCost(),
                    'msrp' => $item->getMsrp(),
                );
                array_push($records,$record);
            }
            $_csv->saveData($filepath,$records);
        }
        else if($this->getArg('process') && $this->getArg('file')){
            $filename = $this->getArg('file');
            $newFilename = 'processed' . time() .'.csv';
            $filepath = $this->getDir() . $filename;
            $_csv = new Varien_File_Csv();
            if(!file_exists($filepath)){
                echo "\nFile does not exists.\n";
                return;
            }
            /** @var Mage_ImportExport_Model_Import_Adapter_Csv $importAdapter */
            $importAdapter = Mage_ImportExport_Model_Import_Adapter::findAdapterFor($filepath);
            $newData = array();
            while($row = $importAdapter->current()){
                if($id = $this->getProductId($row['CH-MASTER'])){
                    $_product = Mage::getModel('catalog/product')->load($id);
                    $row['COST+EXCHG'] = floatval($row['COST']) + floatval($row['EXCHG']);
                    $row['MSRP']  = $_product->getMsrp();
                    array_push($newData,$row);
                }
                $importAdapter->next();
            }
            $_csv->saveData($this->getDir() . $newFilename,$newData);
        }
        $log = sprintf('Cost pricing update %s  and %s msrp update of products completed',$ctr,$msrpCount);
        echo $log . "\n";
        echo "Script end " . now();
//        Mage::log($log, null, 'price_update.log',true);
    }
    public function getDir()
    {
        return Mage::getBaseDir('var') . DS . 'price' . DS;
    }
    public function getProductId($sku){

        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $readConnection */
        $readConnection = $resource->getConnection('core_read');
        $tableName = $resource->getTableName('catalog_product_entity');
        $select = $readConnection->select()
            ->from($tableName)
            ->where('sku = ?', trim($sku));

        return $readConnection->fetchOne($select);
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();