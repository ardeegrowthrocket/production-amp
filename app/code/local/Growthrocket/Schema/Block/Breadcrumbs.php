<?php
/**
 * Created by PhpStorm.
 * User: ocastro
 * Date: 10/12/18
 * Time: 2:13 PM
 */

class Growthrocket_Schema_Block_Breadcrumbs extends Growthrocket_Schema_Block_Schema{
    const BREADCRUMB_TYPE = 'BreadcrumbList';
    const LIST_TYPE = 'ListItem';
    protected function getSchema(){
        $breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs');
        if($breadcrumbsBlock && $breadcrumbsBlock->getId()){
            $crumbs = $breadcrumbsBlock->getAllCrumbs();
            $position = 1;
            $breadcrumbs = array();
            foreach($crumbs as $crumb){
                $item = array(
                    '@type' => self::LIST_TYPE,
                    'position' => $position,
                    'item' => array(
                        'name'  => $crumb['label'],
                        '@id' => empty($crumb['link']) ? $this->getCurrentUrl() : $crumb['link']
                    ),
                );
                array_push($breadcrumbs, $item);
                $position++;
            }

            $data = array(
                '@context' => self::CONTEXT,
                '@type' => self::BREADCRUMB_TYPE,
                'itemListElement' => $breadcrumbs,
            );
            return json_encode($data);
        }
    }
    public function isAllowed(){
        return true;
    }
    public function getCurrentUrl(){
        return $this->helper('core/url')->getCurrentUrl();
    }
}