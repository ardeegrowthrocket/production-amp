<?php
class Homebase_Autopart_Model_System_Sortby{

    public function toOptionArray(){

        $options = array(
            array(
                'value' => 'alphabetical',
                'label' => 'Alphabetical'
            ),
            array(
                'value' => 'bestseller',
                'label' => 'Bestseller'
            )
        );

        return $options;
    }
}