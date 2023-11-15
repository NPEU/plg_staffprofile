<?php
namespace NPEU\Plugin\User\StaffProfile\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

/**
 * Form field for a list of admin groups.
 */
class Projects extends ListField
{
    /**
     * The form field type.
     *
     * @var     string
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
        $lang = Factory::getLanguage();
        $lang->load('com_projects', JPATH_ADMINISTRATOR);

        $options = array();
        $db = Factory::getDBO();
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
            throw new GenericDataException(implode("\n", $errors), 500);
            return false;
        }

        $projects = $db->loadAssocList();

        #echo "<pre>\n"; var_dump($projects); echo "</pre>\n"; exit;

        $i = 0;
        foreach ($projects as $project) {
            $options[] = HTMLHelper::_('select.option', $project['id'], $project['title']);
            $i++;
        }
        if ($i > 0) {
            // Merge any additional options in the XML definition.
            $options = array_merge(parent::getOptions(), $options);
        } else {
            $options = parent::getOptions();
            $options[0]->text = Text::_('PLG_USER_STAFFPROFILE_PROJECTS_FIELD_PROJECTS_NO_PROJECTS');
        }
        return $options;
    }
}