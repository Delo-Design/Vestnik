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
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;

class com_vestnikInstallerScript
{
	/**
	 * Extension params for check.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $extensionParams = array(
		'sef_advanced' => 1,
		'posts_limit'  => 10,
	);

	/**
	 * Runs right after any installation action.
	 *
	 * @param   string            $type    Type of PostFlight action.
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function postflight($type, $parent)
	{
		// Parse layouts
		$this->parseLayouts($parent->getParent()->getManifest()->layouts, $parent->getParent());

		// Parse cli
		$this->parseCLI($parent->getParent()->getManifest()->cli, $parent->getParent());

		// Check databases
		$this->checkTables($parent);

		// Check extension params
		$this->checkExtensionParams($parent);
	}

	/**
	 * Method to parse through a layout element of the installation manifest and take appropriate action.
	 *
	 * @param   SimpleXMLElement  $element    The XML node to process.
	 * @param   Installer         $installer  Installer calling object.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function parseLayouts(SimpleXMLElement $element, $installer)
	{
		if (!$element || !count($element->children())) return false;

		// Get destination
		$folder      = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
		$destination = Path::clean(JPATH_ROOT . '/layouts' . $folder);

		// Get source
		$folder = (string) $element->attributes()->folder;
		$source = ($folder && file_exists($installer->getPath('source') . '/' . $folder)) ?
			$installer->getPath('source') . '/' . $folder : $installer->getPath('source');

		// Prepare files
		$copyFiles = array();
		foreach ($element->children() as $file)
		{
			$path['src']  = Path::clean($source . '/' . $file);
			$path['dest'] = Path::clean($destination . '/' . $file);

			// Is this path a file or folder?
			$path['type'] = $file->getName() === 'folder' ? 'folder' : 'file';
			if (basename($path['dest']) !== $path['dest'])
			{
				$newdir = dirname($path['dest']);
				if (!Folder::create($newdir))
				{
					Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir), Log::WARNING, 'jerror');

					return false;
				}
			}

			$copyFiles[] = $path;
		}

		return $installer->copyFiles($copyFiles, true);
	}

	/**
	 * Method to parse through a cli element of the installation manifest and take appropriate action.
	 *
	 * @param   SimpleXMLElement  $element    The XML node to process.
	 * @param   Installer         $installer  Installer calling object.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function parseCLI(SimpleXMLElement $element, $installer)
	{
		if (!$element || !count($element->children())) return false;

		// Get destination
		$folder      = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
		$destination = Path::clean(JPATH_ROOT . '/cli' . $folder);

		// Get source
		$folder = (string) $element->attributes()->folder;
		$source = ($folder && file_exists($installer->getPath('source') . '/' . $folder)) ?
			$installer->getPath('source') . '/' . $folder : $installer->getPath('source');

		// Prepare files
		$copyFiles = array();
		foreach ($element->children() as $file)
		{
			$path['src']  = Path::clean($source . '/' . $file);
			$path['dest'] = Path::clean($destination . '/' . $file);

			// Is this path a file or folder?
			$path['type'] = $file->getName() === 'folder' ? 'folder' : 'file';
			if (basename($path['dest']) !== $path['dest'])
			{
				$newdir = dirname($path['dest']);
				if (!Folder::create($newdir))
				{
					Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir), Log::WARNING, 'jerror');

					return false;
				}
			}

			$copyFiles[] = $path;
		}

		return $installer->copyFiles($copyFiles, true);
	}

	/**
	 * Method to create database tables in not exist.
	 *
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function checkTables($parent)
	{
		if ($sql = file_get_contents($parent->getParent()->getPath('extension_administrator')
			. '/sql/install.mysql.utf8.sql'))
		{
			$db = Factory::getDbo();

			foreach ($db->splitSql($sql) as $query)
			{
				$db->setQuery($db->convertUtf8mb4QueryToUtf8($query));
				try
				{
					$db->execute();
				}
				catch (JDataBaseExceptionExecuting $e)
				{
					Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()), Log::WARNING, 'jerror');
				}
			}
		}
	}

	/**
	 * Method to check extension params and set if need.
	 *
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function checkExtensionParams($parent)
	{
		if (!empty($this->extensionParams))
		{
			$element = $parent->getElement();
			$folder  = (string) $parent->getParent()->manifest->attributes()['group'];

			// Get extension
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('extension_id', 'params'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote($element));
			if (!empty($folder)) $query->where($db->quoteName('folder') . ' = ' . $db->quote($folder));
			if ($extension = $db->setQuery($query)->loadObject())
			{
				$extension->params = new Registry($extension->params);

				// Check params
				$needUpdate = false;
				foreach ($this->extensionParams as $path => $value)
				{
					if (!$extension->params->exists($path))
					{
						$needUpdate = true;
						$extension->params->set($path, $value);
					}
				}

				// Update
				if ($needUpdate)
				{
					$extension->params = (string) $extension->params;
					$db->updateObject('#__extensions', $extension, 'extension_id');
				}
			}
		}
	}

	/**
	 * This method is called after extension is uninstalled.
	 *
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function uninstall($parent)
	{
		// Remove layouts
		$this->removeLayouts($parent->getParent()->getManifest()->layouts);

		// Remove cli
		$this->removeCLI($parent->getParent()->getManifest()->cli);
	}

	/**
	 * Method to parse through a layouts element of the installation manifest and remove the files that were installed.
	 *
	 * @param   SimpleXMLElement  $element  The XML node to process.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function removeLayouts(SimpleXMLElement $element)
	{
		if (!$element || !count($element->children())) return false;

		// Get the array of file nodes to process
		$files = $element->children();

		// Get source
		$folder = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
		$source = Path::clean(JPATH_ROOT . '/layouts' . $folder);

		// Process each file in the $files array (children of $tagName).
		foreach ($files as $file)
		{
			$path = Path::clean($source . '/' . $file);

			// Actually delete the files/folders
			if (is_dir($path)) $val = Folder::delete($path);
			else $val = File::delete($path);

			if ($val === false)
			{
				Log::add('Failed to delete ' . $path, Log::WARNING, 'jerror');

				return false;
			}
		}

		if (!empty($folder)) Folder::delete($source);

		return true;
	}

	/**
	 * Method to parse through a cli element of the installation manifest and remove the files that were installed.
	 *
	 * @param   SimpleXMLElement  $element  The XML node to process.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function removeCLI(SimpleXMLElement $element)
	{
		if (!$element || !count($element->children())) return false;

		// Get the array of file nodes to process
		$files = $element->children();

		// Get source
		$folder = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
		$source = Path::clean(JPATH_ROOT . '/cli' . $folder);

		// Process each file in the $files array (children of $tagName).
		foreach ($files as $file)
		{
			$path = Path::clean($source . '/' . $file);

			// Actually delete the files/folders
			if (is_dir($path)) $val = Folder::delete($path);
			else $val = File::delete($path);

			if ($val === false)
			{
				Log::add('Failed to delete ' . $path, Log::WARNING, 'jerror');

				return false;
			}
		}

		if (!empty($folder)) Folder::delete($source);

		return true;
	}
}