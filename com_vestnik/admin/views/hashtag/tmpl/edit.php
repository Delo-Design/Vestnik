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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.tabstate');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::stylesheet('com_vestnik/admin.min.css', array('version' => 'auto', 'relative' => true));

Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "hashtag.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
		{
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	};
');
?>
<form action="<?php echo Route::_('index.php?option=com_vestnik&view=hashtag&id=' . $this->item->id); ?>"
      method="post" name="adminForm" id="item-form" class="form-validate" enctype="multipart/form-data">
	<div class="row-fluid">
		<div class="span4">
			<fieldset class="well form-horizontal form-horizontal-desktop">
				<p class="lead"><?php echo Text::_('JGLOBAL_FIELDSET_CONTENT'); ?></p>
				<?php echo $this->form->renderFieldset('content'); ?>
			</fieldset>
		</div>
		<div class="span4">
			<fieldset class="well form-horizontal form-horizontal-desktop">
				<p class="lead"><?php echo Text::_('COM_VESTNIK_HASHTAG_DISPLAY'); ?></p>
				<?php echo $this->form->renderFieldset('display'); ?>
			</fieldset>
		</div>
		<div class="span4">
			<fieldset class="well form-horizontal form-horizontal-desktop">
				<?php echo $this->form->renderFieldset('global'); ?>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="return" value="<?php echo Factory::getApplication()->input->getCmd('return'); ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>