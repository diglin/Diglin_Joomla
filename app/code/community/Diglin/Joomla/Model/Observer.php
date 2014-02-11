<?php
class Jfusion_Joomla_Model_Observer
{
	/**
	 * Set the cookie of Joomfish to define the language to use in Joomla
	 * 
	 * Event
	 * 	- controller_action_predispatch
	 *  
	 * @todo improve the domain and path of cookie for Joomla, it's not always the same as the one of Magento
	 * @param Varien_Event_Observer $observer
	 */
	public function syncUserLanguage($observer)
	{
	    $locale = Mage::app()->getLocale();
	    $session = Mage::getSingleton('customer/session');
        $current_language = $session->getJoomlaLanguage();
        $lang = substr($locale->getLocaleCode(), 0, 2);
        
        if (!$current_language || $current_language != $lang) {
            $session->setJoomlaLanguage($lang);
            
			$cookiePath = Mage::app ()->getStore ()->getConfig ( 'web/cookie/cookie_path' );
			$cookieDomain = Mage::app ()->getStore ()->getConfig ( 'web/cookie/cookie_domain' );
			$cookieLifetime = Mage::app ()->getStore ()->getConfig ( 'web/cookie/cookie_lifetime' );
				
			$cookiesHelper = Mage::helper ( 'joomla/cookies' );
			$cookiesHelper->setCookies ( array (array ('jfcookie[lang]=' . $lang ) ), null, $cookiePath, $cookieLifetime, 0, 0 );
			
			/**
			 * The cookie below is created only if the magento config doesn't include store in URL but we want it for any case
			 * to test and define the url language in the index.php of Magento (custom code) or to inform Joomla about which language is used in Magento
			 */
			$cookiesHelper->setCookies ( array (array ('store=' . $lang ) ), $cookieDomain, $cookiePath, $cookieLifetime, 0, 0 );
        }
	}
}