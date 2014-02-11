<?php
/**
 * @package JFusion
 * @author JFusion development team
 * @copyright Copyright (C) 2009 JFusion. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Jfusion_Joomla_Helper_Module extends Mage_Core_Helper_Abstract 
{	
    /**
     * Get the specified joomla module
     * 
     * @param string $type
     * @param string $title
     * @param int $id
     * @param string $style
     * @param boolean $debug
     * @return string
     */
	function getJoomlaModule($type = '', $title = '', $id = null, $style = 'none', $debug = false)
	{
		static $count = 0;
		
		Varien_Profiler::start ( 'JFUSION JOOMLA MODULE: moduletitle_' . $type );
		
		$configData = Mage::helper ( 'joomla' );
		$cookiesHelper = Mage::helper ( 'joomla/cookies' );
		$storeCode = Mage::app ()->getStore ()->getCode ();
		$cookies = $cookiesHelper->getCookies ( 'string' );
		
		/* Construct the url */
		$urlParams = '';
		if(isset($title) && $title != '')	$urlParams .= '&title='.$title;
		if(isset($id) && $id != '')			$urlParams .= '&id='.$id;
		if(isset($style) && $style != '')	$urlParams .= '&style='.$style;
		if(isset($type) && $type != '')		$urlParams .= "&modulename=".$type;
		
		$url = $configData->getJSecureBaseUrl() . 'index.php?option=com_jfusion&controller=connect&task=module&lang=' . $storeCode . '&secret=' . $configData->getSecretKey() . $urlParams;
		
		$curl = Mage::helper ( 'joomla/adapter_curl' );
		$curl->init ();
		$curlOptions = array ();
		$curlOptions [] = array ('key' => CURLOPT_URL, 'value' => $url );
		//$curlopt [] = array ('key' => CURLOPT_URL, 'value' => $configData->getBaseUrl () . 'index.php?tmpl=' . $configData->getTemplateModule () . '&lang=' . $store_code . '&secret=' . $configData->getSecretKey () . "&modulename=$type&title=$title&style=$style" );
		$curlOptions [] = array ('key' => CURLOPT_USERAGENT, 'value' => 'PHP_CURL' ); // Provide a user agent 'CURL' to identify who try to connect to the jfusion controller for statistics by example.
		$curlOptions [] = array ('key' => CURLOPT_COOKIE, 'value' => $cookies );
		$curlOptions [] = array ('key' => CURLOPT_SSL_VERIFYPEER, 'value' => 0 );
		$curlOptions [] = array ('key' => CURLOPT_SSL_VERIFYHOST, 'value' => 2 );
		$curlOptions [] = array ('key' => CURLOPT_FAILONERROR, 'value' => true );
		$curlOptions [] = array ('key' => CURLOPT_MAXREDIRS, 'value' => 4 );
		$curlOptions [] = array ('key' => CURLOPT_RETURNTRANSFER, 'value' => true );
        $curlOptions [] = array ('key' => CURLOPT_HEADERFUNCTION, 'value' => array('Jfusion_Joomla_Helper_Cookies','getHeader'));
		if ((ini_get ( 'open_basedir' ) == '') && (ini_get ( 'safe_mode' ) == '')) {
			$curlOptions [] = array ('key' => CURLOPT_FOLLOWLOCATION, 'value' => true );
		}
		
		$count ++;
		$curl->setOptions( $curlOptions );
		$data = $curl->exec();
		
		Varien_Profiler::stop('JFUSION JOOMLA MODULE: moduletitle_' . $type);
		
		if ($curl->getErrno()) {
            Mage::log($curl->getError() . ': ' . $curl->getError(), Zend_Log::ERR);
            $curl->close();
            return false;
        }
		
		if ($debug) {
            print_r($curl->getInfo());
        }
        
        $data = $this->_prepareUrlRewrite($data);
        $curl->close();
        return $data;
	}
	
	/**
	 * Prepare the relatives url to fit to the Joomla base url
	 * 
	 * @param string $data
	 */
	protected function _prepareUrlRewrite($data)
	{
	    if($data){
	        /* @var $configData Jfusion_Joomla_Helper_Data */
    		$configData = Mage::helper ( 'joomla' );
    		// Base Url from Config - $configData->getJSecureBaseUrl() = Base url from config + secure or not (depending on current statement)
    		$baseUrl = str_replace('/','\/',rtrim($configData->getData('baseurl'), '/') . '/');
    		
    		$search = array(
    			'/\s(href=\"(index.php|images|templates))(\S+)/',
    			'/\s(src=\"(index.php|images|templates))(\S+)/',
    			'/\s(src=\"'.$baseUrl.'(index.php|images|templates))(\S+)/',
    			'/&/'
    		);
    		$replace = array(
    			' href="'.$configData->getJSecureBaseUrl().'\\2\\3',
    			' src="'.$configData->getJSecureBaseUrl().'\\2\\3',
    			' src="'.$configData->getJSecureBaseUrl().'\\2\\3',
    			'&amp;'
    		);
    		return preg_replace($search, $replace, $data);
	    }
	    return false;
	}
}