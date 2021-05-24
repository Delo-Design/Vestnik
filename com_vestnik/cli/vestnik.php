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

/**
 * Vestnik  CLI.
 *
 * This is a command-line script for Vestnik component.
 *
 * Called with no arguments: php vestnik.php
 *                           Performs task with the display of the dynamic status of each step.
 *
 * Called with --status=hide   `php vestnik.php --status=hide`
 *          or --status=false   or `php vestnik.php --status=false`
 *                              Performs cli with displaying only step titles.
 *
 *  Called with --task=X   `php vestnik.php --task=X`
 *                              Performs task by name.
 *
 */

// We are a valid entry point
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}
if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

define('JPATH_COMPONENT', JPATH_ROOT . '/components/com_vestnik');
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_vestnik');

// Get the framework
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

// System configuration
$config = new JConfig;
define('JDEBUG', $config->debug);

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CliApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

class VestnikCli extends CliApplication
{
	/**
	 * Minimum PHP version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $minimumPhp = '7.0';

	/**
	 * Steps string max length.
	 *
	 * @var  int.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $_stepTextMaxSize = 40;

	/**
	 * Show status or show only step title.
	 *
	 * @var  boolean.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $showStatus = true;

	/**
	 * Line space separator.
	 *
	 * @var  string.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $separator = '.';

	/**
	 * Entry point for DachaDacha CLI script.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function doExecute()
	{
		// Load language
		Factory::getLanguage()->load('com_vestnik', JPATH_ADMINISTRATOR, 'en-GB');

		// Check PHP version
		if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
		{
			$this->out(Text::sprintf('PKG_VESTNIK_ERROR_CLI_PHP_COMPATIBLE'));

			return;
		}

		// Remove the script time limit
		@set_time_limit(0);

		// Set show status
		$status           = $this->input->getString('status', 'show');
		$this->showStatus = ($status !== 'hide' && $status !== 'false');

		$task = $this->input->getString('task', '');
		if (empty($task) || !method_exists($this, $task))
		{
			$this->out(Text::sprintf('PKG_VESTNIK_ERROR_CLI_TASK_NOT_FOUND'));

			return;
		}

		// Print a blank line
		$this->out(Text::sprintf('PKG_VESTNIK_CLI', $task));
		$this->out('============================');
		$this->$task();

		$this->out();
	}

	/**
	 * Method to initialise SuperAdmin user.
	 *
	 * @throws Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function initialiseSuperUser()
	{
		// Set super admin user
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select(array('id', 'parent_id'))
			->from($db->quoteName('#__usergroups'));
		$rows   = $db->setQuery($query)->loadAssocList('id', 'parent_id');
		$rules  = Access::getAssetRules(1);
		$groups = array();
		foreach ($rows as $id => $parent_id)
		{
			$ids        = array($id);
			$parent_key = $parent_id;
			while (!empty($parent_key) && isset($rows[$parent_key]))
			{
				$ids[]      = $parent_key;
				$parent_key = $rows[$parent_key];
			}
			$ids = array_reverse($ids);

			if ($rules->allow('core.admin', $ids))
			{
				$groups[] = $id;
			}
		}

		$groups = ArrayHelper::toInteger($groups);
		$query  = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__users', 'u'))
			->leftJoin($db->quoteName('#__user_usergroup_map', 'm') . ' ON m.user_id = u.id')
			->where($db->quoteName('m.group_id') . ' IN (' . implode(',', $groups) . ')')
			->group(array('u.id'));
		$userId = $db->setQuery($query)->loadResult();
		Factory::getUser()->load($userId);
		Factory::getUser()->set('isRoot', true);
	}

	/**
	 * Method to output step status.
	 *
	 * @param   string  $constant  Step language constant.
	 * @param   string  $status    Status text.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function outStatus($constant = '', $status = '')
	{
		if ($this->showStatus)
		{
			$this->out($this->getStepText($constant) . $this->getStatusText($status), false);
		}
		else
		{
			$this->out(' - ' . $this->getStepText($constant), false);
		}
	}

	/**
	 * Method to update step status text.
	 *
	 * @param   string  $status  Status text.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function updateStatus($status = '')
	{
		if ($this->showStatus)
		{
			echo "\033[20D";
			echo str_pad($this->getStatusText($status), 20, ' ', STR_PAD_LEFT);
		}
	}

	/**
	 * Method to get step text with separators.
	 *
	 * @param   string  $constant  Step language constant.
	 *
	 * @return  string Step text with separator
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getStepText($constant = '')
	{
		$text = Text::_($constant);
		$size = strlen($text);
		if ($this->showStatus && $size < $this->_stepTextMaxSize)
		{
			$text .= str_repeat($this->separator, $this->_stepTextMaxSize - $size);
		}

		return $text;
	}

	/**
	 * Method to get status text with separators.
	 *
	 * @param   string  $status  Status text.
	 *
	 * @return  string Status text with separator
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getStatusText($status)
	{
		if ($this->showStatus)
		{
			$size = strlen($status);
			if ($size < 20)
			{
				$status = str_repeat($this->separator, 20 - $size) . $status;
			}
		}

		return $status;
	}
}

// Instantiate the application object.
CliApplication::getInstance('VestnikCli')->execute();