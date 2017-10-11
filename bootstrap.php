<?php

/**
 * Include this is every script to load Joomla framework and FaLang.
 */

// Set flag that this is a valid Joomla entry point
define('_JEXEC', 1);

// Load system defines
if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(dirname(dirname(__FILE__))) . '/web');
    require_once JPATH_BASE . '/includes/defines.php';
}
require_once JPATH_BASE . '/includes/framework.php';

// Fool Joomla into thinking we're in the administrator with com_falang as active component
$app = JFactory::getApplication('site');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = ' ';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('JPATH_COMPONENT', JPATH_BASE . '/components/com_falang');
define('JPATH_COMPONENT_SITE', JPATH_BASE . '/components/com_falang');
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_BASE . '/administrator/components/com_falang');

/* add loaders as necessary */
JLoader::discover('CH', JPATH_COMPONENT_ADMINISTRATOR.'/helpers');
JHtml::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/html');
JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/tables');
JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.'/models');
JFormHelper::addRulePath(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/rule');

$app->loadLanguage();
$lang = JFactory::getLanguage();
$lang->load('com_falang', JPATH_ADMINISTRATOR, 'en-GB', true);
$lang->load('com_falang', JPATH_ADMINISTRATOR, null, true);
/* end loaders */

//Global definitions
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once(JPATH_SITE .DS. 'components' .DS. 'com_falang' .DS. 'helpers' .DS. 'defines.php');
JLoader::register('FalangManager', FALANG_ADMINPATH .DS. 'classes' .DS. 'FalangManager.class.php');
JLoader::register('FalangExtensionHelper', FALANG_ADMINPATH .DS. 'helpers' .DS. 'extensionHelper.php');
JLoader::register('FalangVersion', FALANG_ADMINPATH .DS. 'version.php');

require_once(__DIR__ . '/LocalizationBaseClass.php');
require_once(__DIR__ . '/NbillWriteToFile.php');

require_once(__DIR__ . '/HJSON/HJSONException.php');
require_once(__DIR__ . '/HJSON/HJSONParser.php');
require_once(__DIR__ . '/HJSON/HJSONStringifier.php');
require_once(__DIR__ . '/HJSON/HJSONUtils.php');
