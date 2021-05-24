<?php
/*
 * @package     Vestnik Package
 * @subpackage  plg_system_vestnik
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

class plgSystemVestnik extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var  JApplicationCms
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to override router for atoms connect.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterInitialise()
	{
		if ($this->app->isClient('site'))
		{
			$uri    = Uri::getInstance();
			$params = ComponentHelper::getParams('com_vestnik');
			$path   = $uri->getPath();
			$find   = false;
			$root   = Uri::root(true);

			$entry = $root . '/' . $params->get('post_prefix', 'p') . '/';
			if (preg_match('#^' . $entry . '#', $path))
			{
				$find = true;
				$key   = trim(str_replace($entry, '', $path), '/');

				$uri->setPath($root);
				$uri->setVar('option', 'com_vestnik');
				$uri->setVar('task', 'post.display');
				$uri->setVar('key', $key);
			}

			if ($find)
			{
				$uri->setVar('lang', Factory::getLanguage()->getTag());
				$this->app->input->set('nolangfilter', 1);
			}
		}
	}

	/**
	 * Add onVestnikPrepareForm trigger.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{
		$formName = $form->getName();
		if (
			in_array($formName, array('com_vestnik.hashtag', 'com_vestnik.post'))
			|| ($formName === 'com_config.component' && $this->app->input->get('component') === 'com_vestnik')
		)
		{
			PluginHelper::importPlugin('vestnik');
			$this->app->triggerEvent('onVestnikPrepareForm', array($form, $data));
		}
	}

	/**
	 * Add onVestnikAfterSaveConfig trigger.
	 *
	 * @param   string   $context  The extension.
	 * @param   Table    $table    DataBase Table object.
	 * @param   boolean  $isNew    If the extension is new or not.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onExtensionAfterSave($context, $table, $isNew)
	{
		if ($context === 'com_config.component' && $table->element === 'com_vestnik')
		{
			PluginHelper::importPlugin('vestnik');
			$this->app->triggerEvent('onVestnikAfterSaveConfig', array($table));
		}
	}
}