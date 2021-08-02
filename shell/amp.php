<?php

require_once 'abstract.php';

class Mage_Shell_Compiler extends Mage_Shell_Abstract{
	public function run(){
		$_products = Mage::getModel('catalog/product')->getCollection();
		foreach($_products as $_product){
			$_rproduct = Mage::getModel('catalog/product')->load($_product->getId());
			//Description
			$strip_desc = str_replace(array('Ë','â€œ','â€'),'"', $_rproduct->getDescription());
			$strip_desc = str_replace(array('Â'),' ', $strip_desc);
			$strip_desc = str_replace(array('â€'),'"',$strip_desc);

            $strip_desc = str_replace(array('_x000D_'),'',$strip_desc);

			$_rproduct->setDescription($strip_desc);
			$_rproduct->save();

			//Short Description
		//	$strip_desc = str_replace(array('Ë','â€œ','â€'),'"', $_rproduct->getShortDescription());
		//	$strip_desc = str_replace(array('Â'),' ', $strip_desc);
		//	$strip_desc = str_replace(array('â€'),'"',$strip_desc);
		//	$_rproduct->setShortDescription($strip_desc);
		//	$_rproduct->save();
//			$strip_name = str_replace(array('â€'),'"',$_rproduct->getName());
//			$_rproduct->setName($strip_name);
//			$_rproduct->save();

		}
		//$strip = str_replace('â€œ', '"', $_product->getDescription());

		//Zend_Debug::dump($_product->getDescription());
	}
}

$shell = new Mage_Shell_Compiler();
$shell->run();
