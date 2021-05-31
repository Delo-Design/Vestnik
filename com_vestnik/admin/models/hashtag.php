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
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

class VestnikModelHashtag extends AdminModel
{
	/**
	 * Method to get a single record.
	 *
	 * @param   int  $pk  The id of the primary key.
	 *
	 * @throws  Exception
	 *
	 * @return  CMSObject|boolean  Object on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the plugins field value to array
			$item->plugins = (new Registry($item->plugins))->toArray();
		}

		return $item;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name.
	 * @param   array   $config  Configuration array for model.
	 *
	 * @return  Table  A database object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getTable($type = 'Hashtags', $prefix = 'VestnikTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  The Table object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function prepareTable($table)
	{
		// Set ordering to the last item if not set
		if (empty($table->id) && empty($table->ordering))
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true)
				->select('ordering')
				->from($db->quoteName('#__vestnik_hashtags'))
				->order('ordering DESC');

			$table->ordering = (int) $db->setQuery($query, 0, 1)->loadResult() + 1;
		}
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @throws  Exception
	 *
	 * @return  Form|boolean  A Form object on success, false on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getForm($data = array(), $loadData = true)
	{
		if (!$form = $this->loadForm('com_vestnik.hashtag', 'hashtag',
			array('control' => 'jform', 'load_data' => $loadData))) return false;

		// Get item id
		$id = (int) $this->getState('hashtag.id', Factory::getApplication()->input->get('id', 0));

		// Modify the form based on Edit State access controls
		if ($id != 0 && !Factory::getUser()->authorise('core.edit.state', 'com_vestnik.hashtag.' . $id))
		{
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		$state = (int) $form->getValue('state');
		if ($state == 2)
		{
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @throws  Exception
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_vestnik.edit.hashtag.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_vestnik.hashtag', $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @throws  Exception
	 *
	 * @return  int|false  Item id on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function save($data)
	{
		$pk    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$table = $this->getTable();
		$isNew = true;

		// Load the row if saving an existing item
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		// Prepare alias field data
		$alias     = (!empty($data['alias'])) ? $data['alias'] : $data['title'];
		$languages = LanguageHelper::getInstalledLanguages();
		foreach (array_keys($languages[0]) as $language)
		{
			if ($clean = OutputFilter::stringURLSafe($alias, $language))
			{
				$alias = $clean;
				break;
			}
		}
		if (empty(($alias))) $alias = Factory::getDate()->toUnix();

		// Check alias is already exist
		$checkAlias = $this->getTable();
		$checkAlias->load(array('alias' => $alias));
		if (!empty($checkAlias->id) && ($checkAlias->id != $pk || $isNew)) $alias = $this->generateNewAlias($alias);
		$data['alias'] = $alias;

		// Prepare posting field data
		$hashtag         = (!empty($data['hashtag'])) ? $data['hashtag'] : $data['title'];
		$hashtag         = str_replace('#', '', $hashtag);
		$hashtag         = str_replace(' ', '_', $hashtag);
		$data['hashtag'] = $hashtag;

		// Prepare params field data
		if (isset($data['params'])) $data['params'] = (new Registry($data['params']))->toString('json');

		// Prepare plugins field data
		if (isset($data['plugins'])) $data['plugins'] = (new Registry($data['plugins']))->toString('json');

		if (parent::save($data))
		{
			$id = $this->getState($this->getName() . '.id');
			if (empty($id)) $id = $table->id;

			return $id;
		}

		return false;
	}

	/**
	 * Method to generate new alias if alias already exist.
	 *
	 * @param   string  $alias  The alias.
	 *
	 * @return  string  Contains the modified alias.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function generateNewAlias($alias)
	{
		$table = $this->getTable();
		while ($table->load(array('alias' => $alias))) $alias = StringHelper::increment($alias, 'dash');

		return $alias;
	}
}