<?php
/**
 * @package JFusion
 * @author JFusion development team
 * @copyright Copyright (C) 2009 JFusion. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Jfusion_Joomla_Block_Module extends Mage_Core_Block_Template
{
    const CACHE_GROUP = 'joomla_html';

    /**
	 * Save the data of each modules before to render them
	 * $type: it should be the name of the type of module loaded (exple: mod_mainmenu -> $type=mainmenu) - required
	 * $title: the title provided in the backend of Joomla for the specified module (exple: Footer) - optionnal
	 * $id: it's the id of the module, you can find it in the module list in your backend, in the right column ID - optionnal
	 * $style: xhtml, none or chrome style of your template
	 * If you don't provide id or title but type, it will generate a dummy module of the module's type
	 *
	 * @param string $type
	 * @param string $title
	 * @param string $id
	 * @param string $style
	 * @param string $debug
	 * @return object Jfusion_Joomla_Block_Module
	 */
    public function loadJModule($type = '', $title = '', $id = null, $style = 'none', $debug = false, $cache = true)
    {
        $key = $title = urlencode($title);
        $this->_data['modules'][$type . '/' . $key]['type'] = $type;
        $this->_data['modules'][$type . '/' . $key]['title'] = $title;
        $this->_data['modules'][$type . '/' . $key]['id'] = $id;
        $this->_data['modules'][$type . '/' . $key]['style'] = $style;
        $this->_data['modules'][$type . '/' . $key]['debug'] = $debug;
        
        if (! Mage::helper('joomla')->isCacheActivated() && ! $cache) {
            $this->setCacheLifetime(null);
        } else {
            $ssl = (Mage::app()->getStore()->isCurrentlySecure()) ? 'ssl' : '';
            $session = Mage::getSingleton('customer/session');
            if ($session->isLoggedIn()) {
                //Some module depends on customer information (rights) like menus
                $loggedIn = 'logged' . $session->getId();
            } else {
                $loggedIn = '';
            }
            
            $this->setCacheLifetime(86400);
            $this->setCacheKeyInfo(array(
                'JOOMMOD' , Mage::app()->getStore()
                ->getCode() , $type , $id , $title , $ssl , $loggedIn
            ));
        }
        
        return $this;
    }

    /**
	 * Load the joomla module from the joomla installation
	 *
	 * @return string $output
	 */
    public function getModule()
    {
        $out = '';
        if (is_array($this->_data['modules'])) {
            foreach ($this->_data['modules'] as $module) {
                $out .= $this->helper('joomla/module')->getJoomlaModule($module['type'], $module['title'], $module['id'], $module['style'], $module['debug']);
            }
        }
        
        if ($this->getPrepareLayoutMenu() == '1' && strlen($out) > 0) {
            if (strpos($out, '<ul') == 0) {
                $pos = strpos($out, '>');
                //remove first tag <ul> and </ul>
                $out = substr($out, $pos + 1);
                $out = substr($out, 0, (strlen($out) - 5));
            }
        }
        return $out;
    }

    /**
	* This method allows you to get only li tags from a Joomla menu or any module using only <ul><li>...</li></ul>
	* Very usefull to integrate a menu module to Magento for example. You need then to bring the module into the catalog/navigation/top.html for example
	*/
    public function setPrepareLayoutMenu($value)
    {
        parent::setPrepareLayoutMenu($value); // use magic setter of parent class
        return;
    }

    protected function _toHtml()
    {
        if (! $this->_beforeToHtml()) {
            return '';
        }
        
        return $this->getModule();
        ;
    }

    /**
     * Get Key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        if ($this->hasData('cache_key_info')) {
            return $this->getData('cache_key_info');
        }
        return parent::getCacheKeyInfo();
    }

    /**
     * Prepare url for save to cache
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeCacheUrl()
    {
        if (Mage::app()->useCache(self::CACHE_GROUP)) {
            Mage::app()->setUseSessionVar(true);
        }
        return $this;
    }

    /**
     * Replace URLs from cache
     *
     * @param string $html
     * @return string
     */
    protected function _afterCacheUrl($html)
    {
        if (Mage::app()->useCache(self::CACHE_GROUP)) {
            Mage::app()->setUseSessionVar(false);
            Varien_Profiler::start('CACHE_URL');
            $html = Mage::getSingleton('core/url')->sessionUrlVar($html);
            Varien_Profiler::stop('CACHE_URL');
        }
        return $html;
    }

    /**
     * Get tags array for saving cache
     *
     * @return array
     */
    public function getCacheTags()
    {
        if (! $this->hasData('cache_tags')) {
            $tags = array();
        } else {
            $tags = $this->getData('cache_tags');
        }
        $tags[] = self::CACHE_GROUP;
        return $tags;
    }

    /**
     * Load block html from cache storage
     *
     * @return string | false
     */
    protected function _loadCache()
    {
        if (is_null($this->getCacheLifetime()) || ! Mage::app()->useCache(self::CACHE_GROUP)) {
            return false;
        }
        return Mage::app()->loadCache($this->getCacheKey());
    }

    /**
     * Save block content to cache storage
     *
     * @param string $data
     * @return Mage_Core_Block_Abstract
     */
    protected function _saveCache($data)
    {
        if (is_null($this->getCacheLifetime()) || ! Mage::app()->useCache(self::CACHE_GROUP)) {
            return false;
        }
        Mage::app()->saveCache($data, $this->getCacheKey(), $this->getCacheTags(), $this->getCacheLifetime());
        return $this;
    }
}