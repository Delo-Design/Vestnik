<?php
/*
 * @package     Vestnik Package
 * @subpackage  com_vestnik
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

class VestnikHelper extends ContentHelper
{
	/**
	 * Configure the linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @throws Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(Text::_('COM_VESTNIK_POSTS'),
			'index.php?option=com_vestnik&view=posts',
			$vName === 'posts');

		JHtmlSidebar::addEntry(Text::_('COM_VESTNIK_HASHTAGS'),
			'index.php?option=com_vestnik&view=hashtags',
			$vName === 'hashtags');

		JHtmlSidebar::addEntry(Text::_('COM_VESTNIK_CONFIG'),
			'index.php?option=com_config&view=component&component=com_vestnik');
	}

	/**
	 * Method to get RadicalMart version.
	 *
	 * @return string RadicalMart version.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getVersion()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('manifest_cache')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('element') . ' = ' . $db->quote('com_vestnik'));

		return (new Registry($db->setQuery($query)->loadResult()))->get('version');
	}
}