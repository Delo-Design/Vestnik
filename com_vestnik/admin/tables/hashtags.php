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

use Joomla\CMS\Table\Table;

class VestnikTableHashtags extends Table
{
	/**
	 * Constructor.
	 *
	 * @param   JDatabaseDriver &$db  Database connector object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	function __construct(&$db)
	{
		parent::__construct('#__vestnik_hashtags', 'id', $db);

		// Set the alias since the column is called state
		$this->setColumnAlias('published', 'state');
	}
}