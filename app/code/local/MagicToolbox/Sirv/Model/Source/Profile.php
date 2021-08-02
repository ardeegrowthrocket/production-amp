<?php

class MagicToolbox_Sirv_Model_Source_Profile
{

    public function toOptionArray()
    {
        $options = array(
            array('value' => '', 'label' => '-')
        );
        $sirv = Mage::getSingleton('sirv/adapter_s3');
        if ($sirv->isEnabled()) {
            $profiles = $sirv->getProfiles();
            if (is_array($profiles)) {
                foreach ($profiles as $profile) {
                    $options[] = array('value' => $profile, 'label' => $profile);
                }
            }
        }

        return $options;
    }
}
