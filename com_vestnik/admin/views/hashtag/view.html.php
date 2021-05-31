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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\ToolbarHelper;

class VestnikViewHashtag extends HtmlView
{
	/**
	 * Model state variables.
	 *
	 * @var  CMSObject
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $state;

	/**
	 * Form object.
	 *
	 * @var  Form
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $form;

	/**
	 * The active item.
	 *
	 * @var  object
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $item;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse.
	 *
	 * @throws  Exception
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');

		// Check for errors
		if (count($errors = $this->get('Errors'))) throw new Exception(implode('\n', $errors), 500);

		// Add title and toolbar
		$this->addToolbar();

		return parent::display($tpl);
	}

	/**
	 * Add title and toolbar.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function addToolbar()
	{
		$isNew = ($this->item->id == 0);
		$canDo = VestnikHelper::getActions('com_vestnik', 'hashtag', $this->item->id);

		// Disable menu
		Factory::getApplication()->input->set('hidemainmenu', true);

		// Set page title
		$title = ($isNew) ? Text::_('COM_VESTNIK_HASHTAG_ADD') : Text::_('COM_VESTNIK_HASHTAG_EDIT');
		ToolbarHelper::title(Text::_('COM_VESTNIK') . ': ' . $title, 'health');

		// Add apply & save buttons
		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::apply('hashtag.apply');
			ToolbarHelper::save('hashtag.save');
		}

		// Add save new button
		if ($canDo->get('core.create')) ToolbarHelper::save2new('hashtag.save2new');

		// Add cancel button
		ToolbarHelper::cancel('hashtag.cancel', 'JTOOLBAR_CLOSE');
	}
}