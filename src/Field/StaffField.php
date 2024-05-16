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
class StaffField extends ListField
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Staff';

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
        $options = array();
        $db = Factory::getDBO();
        $q  = 'SELECT u.id, u.name, up1.profile_value AS first_name, up2.profile_value AS last_name ';
        $q .= 'FROM `#__users` u ';
        $q .= 'JOIN `#__user_usergroup_map` ugm ON u.id = ugm.user_id ';
        $q .= 'JOIN `#__usergroups` ug ON ugm.group_id = ug.id ';
        $q .= 'JOIN `#__user_profiles` up1 ON u.id = up1.user_id AND up1.profile_key = "firstlastnames.firstname" ';
        $q .= 'JOIN `#__user_profiles` up2 ON u.id = up2.user_id AND up2.profile_key = "firstlastnames.lastname" ';
        $q .= 'WHERE ug.title = "Staff" ';
        $q .= 'AND u.block = 0 ';
        $q .= 'ORDER BY last_name, first_name;';

        $db->setQuery($q);
        if (!$db->execute($q)) {
            throw new GenericDataException(implode("\n", $errors), 500);
            return false;
        }

        $staff_members = $db->loadAssocList();

        #echo "<pre>\n"; var_dump($staff_members); echo "</pre>\n"; exit;

        $i = 0;
        foreach ($staff_members as $staff_member) {
            $options[] = HTMLHelper::_('select.option', $staff_member['id'], $staff_member['name']);
            $i++;
        }
        if ($i > 0) {
            // Merge any additional options in the XML definition.
            $options = array_merge(parent::getOptions(), $options);
        } else {
            $options = parent::getOptions();
            $options[0]->text = Text::_('PLG_USER_STAFFPROFILE_TEAM_FIELD_TEAM_NO_STAFF');
        }
        return $options;
    }

}