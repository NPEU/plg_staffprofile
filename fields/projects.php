<?php
/**
 * @package		ProjectsList
 * @subpackage	plg_user_staffprofile
 * @copyright	Copyright (C) 2012 Andy Kirk.
 * @author		Andy Kirk
 * @license		License GNU General Public License version 2 or later
 */

// No direct access
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Framework.
 *
 * @package		Extras
 * @subpackage	com_extras
 */
class JFormFieldProjects extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 */
	protected $type = 'Projects';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
	    // Load  language in case this is used for other extensions
	    $lang = JFactory::getLanguage();
	    $lang->load('com_projects', JPATH_ADMINISTRATOR);

		$options = array();
		$db = JFactory::getDBO();
		$q  = 'SELECT c.id, c.title ';
        $q .= 'FROM `#__categories` c ';
		$q .= 'JOIN `#__fields_values` fv ON c.id = fv.item_id ';
		$q .= 'WHERE fv.field_id = 6 '; // Note this hard-coded value isn't robust/transferable.
		$q .= 'AND fv.value = "yes" ';
		$q .= 'AND c.published = 1 ';
		$q .= 'AND c.access = 1 ';
        $q .= 'ORDER BY c.title;';

		$db->setQuery($q);
		if (!$db->execute($q)) {
			JError::raiseError( 500, $db->stderr() );
			return false;
		}

		$projects = $db->loadAssocList();

		#echo "<pre>\n"; var_dump($projects); echo "</pre>\n"; exit;

		$i = 0;
		foreach ($projects as $project) {
			$options[] = JHtml::_('select.option', $project['id'], $project['title']);
			$i++;
		}
		if ($i > 0) {
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		} else {
			$options = parent::getOptions();
			$options[0]->text = JText::_('PLG_USER_STAFFPROFILE_PROJECTS_FIELD_PROJECTS_NO_PROJECTS');
		}
		return $options;
	}
}