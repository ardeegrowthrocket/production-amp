<?php
require_once 'abstract.php';

class Gr_Prodimport extends Mage_Shell_Abstract
{
		    protected $data = array();
		    protected $headdata;
    public function run()
    {



    	$csv = $this->getArg('csv');
    	$store = $this->getArg('store');
    	if($csv==''){
    		echo "No CSV is setup \n";
    		exit();
    	}

    	if(!file_exists($csv)){
    		echo "file not exists \n";
    		exit();
    	}


			$file = fopen($csv,"r");
	        $count = 0;
	        $countdata = 0;			
		        while(! feof($file))
		        {
		          $datafile = fgetcsv($file);
		          if($count==0){
		            $this->headdata = $datafile;
		            $count++;
		            
		          }else{

		              $datacount = 0;
		              foreach($this->headdata as $k){
		                $this->data[$countdata][$k] = $datafile[$datacount];   
		                $datacount++;         
		              } 

		              $countdata++;
		          }

					

			}
			fclose($file);

			foreach($this->data as $datas){

				if(!empty($datas['sku'])){




				$product_id = Mage::getModel("catalog/product")->getIdBySku($datas['sku']);
				$datasx = $datas;

				if($product_id){
					unset($datasx['_store']);
					echo $datas['sku']."\n";
						if($store){
							$updater = Mage::getModel("catalog/product")->load($product_id);
							$updater->setStoreId(4)->addData($datasx);
							$updater->save();

						}		
						$updater = Mage::getModel("catalog/product")->load($product_id);
						$updater->setStoreId(0)->addData($datasx);
						$updater->save();	

				}





				}

			}
   }
    
}

$shell = new Gr_Prodimport();
$shell->run();
