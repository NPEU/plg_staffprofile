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
 * Form field for a list of NPEU projects.
 */
class ProjectsField extends ListField
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Projects';

    protected $layout = 'joomla.form.field.list-fancy-select';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions()
    {
        // Load  language in case this is used for other extensions
        //$lang = Factory::getLanguage();
        //$lang->load('com_projects', JPATH_ADMINISTRATOR);
        #echo "<pre>\n"; var_dump('wer'); echo "</pre>\n"; exit;
        $options = [];
        $db = Factory::getDBO();
        $q  = 'SELECT id, name as title ';
        $q .= 'FROM `#__brands` ';
        $q .= 'WHERE catid = 171 '; // Note this hard-coded value isn't robust/transferable.
        $q .= 'ORDER BY name;';

        $db->setQuery($q);
        if (!$db->execute($q)) {
            throw new GenericDataException(implode("\n", $errors), 500);
            return false;
        }

        $projects = $db->loadAssocList();

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