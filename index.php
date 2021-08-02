<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
    $cfIP = $_SERVER["HTTP_CF_CONNECTING_IP"];
    if(filter_var($cfIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
        $forwardIps = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $remoteIps = $_SERVER['REMOTE_ADDR'];
        if(!empty($forwardIps)){
            $getIps = explode(',', $forwardIps);
            $cfIP = trim(end($getIps));
        }elseif(!empty($remoteIps)){
            $getIps = explode(',', $remoteIps);
            $cfIP = trim(end($getIps));
        }
    }
    $_SERVER['REMOTE_ADDR'] = $cfIP;
}

$actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
if (strpos($actual_link, 'wp-login') !== false) {
    exit('');
}
if (strpos($actual_link, 'wlwmanifest') !== false) {
    exit('');
}

if(!empty($_REQUEST['PageSpeed']))  
{
    exit();
}

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

$blockWithExtension = array('tar.bz2', 'tar', 'rar', 'tgz', 'sql.bz', 'sql', 'zip', 'bz','bz2','gz');
$redirectUrL = array('/1.html', '/year/1.html');
$uriLink = $_SERVER['REQUEST_URI'];
$domainLink = 'http://'.$_SERVER['HTTP_HOST'];
$actual_link = 'http://'.$_SERVER['HTTP_HOST'].$uriLink;

$ext = pathinfo(strtok($uriLink, "?"), PATHINFO_EXTENSION);
foreach ($blockWithExtension as $excludeExt){
    if($ext == $excludeExt){
        header('Location: ' . $domainLink, true, 301);
        exit; 
    }
}
foreach ($redirectUrL as $url){
    if($url == $uriLink){
        header('Location: ' . $domainLink, true, 301);
        exit;
    }
}
if (strpos($actual_link, 'admin') !== false) {
    if (strpos($actual_link, '/secureadminamp') !== false) {
    }else{
        exit();
    }
}


if(!empty($_REQUEST['p']))  
{

    if (strpos($_REQUEST['p'], 'SELECT') !== false) {
         exit();
    }
    if (strpos($_REQUEST['p'], 'UNION') !== false) {
         exit();
    }

     if (strpos($_REQUEST['p'], 'SLEEP') !== false) {
         exit();
    }   

    if(!is_numeric($_REQUEST['p'])){
        exit();
    }else{
        if (!ctype_digit($_REQUEST['p'])) {
            exit();
        }
    }
  
}


$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if (strpos($actual_link, 'www.allmoparparts.com/shipping/tracking/popup/hash/') !== false) {
    $hash = str_replace(array('http://www.allmoparparts.com/shipping/tracking/popup/hash/','https://www.allmoparparts.com/shipping/tracking/popup/hash/','/?___store=default'),array('','',''),$actual_link);
    header("Location: https://www.allmoparparts.com/shippinghelper/index/?hash=$hash");
    die();
}
if (version_compare(phpversion(), '5.3.0', '<')===true) {
    echo  '<div style="font:12px/1.35em arial, helvetica, sans-serif;">
<div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;">
<h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">
Whoops, it looks like you have an invalid PHP version.</h3></div><p>Magento supports PHP 5.3.0 or newer.
<a href="http://www.magentocommerce.com/install" target="">Find out</a> how to install</a>
 Magento using PHP-CGI as a work-around.</p></div>';
    exit;
}

/**
 * Compilation includes configuration file
 */
define('MAGENTO_ROOT', getcwd());

$compilerConfig = MAGENTO_ROOT . '/includes/config.php';
if (file_exists($compilerConfig)) {
    include $compilerConfig;
}

$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
$maintenanceFile = 'maintenance.flag';

if (!file_exists($mageFilename)) {
    if (is_dir('downloader')) {
        header("Location: downloader");
    } else {
        echo $mageFilename." was not found";
    }
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'];
$allowed = array(
    '49.147.192.20',
'116.50.136.34',
'207.148.74.115'
);

if (file_exists($maintenanceFile) && !in_array($ip, $allowed) ) {
    ?>
    <div id="main" class="col-main" style="font-size: 16px">
        <!-- [start] content -->
        <div class="page-title">
            <h1>Down for Maintenance</h1>
        </div>
        <p>The server is temporarily unable to service your request due to maintenance downtime. Please try again later.</p>
        <p>This Downtime will last 1 hour.</p>
        <!-- [end] content -->
    </div>
    <?php
    exit;
}

require MAGENTO_ROOT . '/app/bootstrap.php';
require_once $mageFilename;

#Varien_Profiler::enable();

if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
    Mage::setIsDeveloperMode(true);
}
//Mage::setIsDeveloperMode(true);
#ini_set('display_errors', 1);

umask(0);

/* Store or website code */
$mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : '';

/* Run store or run website */
$mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';

Mage::run($mageRunCode, $mageRunType);

Mage::log(Mage::helper('core/url')->getCurrentUrl() . '-' . $ip, null, 'visitor.log', true);
#Mage::log(Mage::helper('core/url')->getCurrentUrl(), null, 'visitor.log', true);
