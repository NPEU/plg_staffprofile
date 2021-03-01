<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  plg_staff_profile
 *
 * @copyright   Copyright (C) 2013 Andy Kirk.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Pseudo form field that displays an avatar.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.3
 */
class JFormFieldImageEdit extends JFormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  11.1
     */
    protected $type = 'ImageEdit';
    protected $user;
    protected $session_user;
    protected $users_groups;
    protected $users_alias;
    protected $session_users_groups;
    protected $admin_user_group_id;
    protected $super_user_group_id;
    protected $image_exists = false;

    protected $default_src = '_none.jpg';
    protected $savepath;
    protected $savename;
    protected $el_id;
    protected $src;

    public function __construct ($form = null) {

        parent::__construct($form);


        $this->user = JFactory::getUser(JFactory::getApplication()->input->get('id'));
        $this->users_groups = $this->user->getAuthorisedGroups();
        $this->users_alias = JApplication::stringURLSafe($this->user->name) . '-' . $this->user->id;

        $this->session_user = JFactory::getUser();
        $this->session_users_groups = $this->session_user->getAuthorisedGroups();

        $plugin         = JPluginHelper::getPlugin('user', 'staffprofile');
        $plugin_params  = new JRegistry($plugin->params);
        $this->savepath = $plugin_params->get('avatar_dir');
        #echo "<pre>\n"; var_dump($this->savepath); echo "</pre>\n";exit;


        $db    = JFactory::getDBO();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from($db->quoteName('#__usergroups'))
              ->where('title = "Administrator"');
        $db->setQuery($query);
        $result = $db->loadResult();
        $this->admin_user_group_id = (int) $result;
        #echo "<pre>\n"; var_dump($this->admin_user_group_id); echo "</pre>\n"; exit;

        $query = $db->getQuery(true);
        $query->select('id')
              ->from($db->quoteName('#__usergroups'))
              ->where('title = "Super Users"');
        $db->setQuery($query);
        $result = $db->loadResult();
        $this->super_user_group_id = (int) $result;
        #echo "<pre>\n"; var_dump($this->super_user_group_id); echo "</pre>\n"; exit;


        $this->savename = $this->users_alias . '-avatar';
        $this->el_id = $this->savename;
        $this->src = $this->savepath  . '/' . $this->savename . '.jpg';

        // e.g. /assets/images/avatars/andy-kirk-602-avatar.jpg
        #echo "<pre>\n"; var_dump($this->value); echo "</pre>\n"; #exit;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $this->src)) {
            $this->image_exists = true;
        }
    }

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   11.3
     */
    protected function getInput()
    {
        $app = JFactory::getApplication();

        #echo "<pre>\n"; var_dump($this->user); echo "</pre>\n"; exit;
        #echo "<pre>\n"; var_dump($this->users_groups); echo "</pre>\n"; exit;

        #echo "<pre>\n"; var_dump($this->value); echo "</pre>\n"; exit;

        $value = $this->src;
        if (!$this->image_exists || $this->value == '') {
            $this->src =  $this->savepath  . '/' . $this->default_src . '?';// . '&';
            $value = '';
            #$src = 'http://www.placehold.it/80x80/EFEFEF/AAAAAA&text=no+image';
        } else {
            $this->src .= '?';
        }

        $output = '<img src="' . $this->src . 's=80&' . time() . '" height="80" width="80" alt="" id="' . $this->el_id . '-preview" /> ';
        $output .= '<input type="hidden" name="jform[profile][avatar_img]" id="' . $this->savename . '" value="' . $value . '" />';

        if (in_array($this->super_user_group_id, $this->session_users_groups) || in_array($this->admin_user_group_id, $this->session_users_groups)) {
            
            $imageedit_path = '/plugins/user/staffprofile/libraries/ImageEdit/j-image-edit.php?savename=' . $this->savename . '&amp;savedir=' . $this->savepath . '&amp;el_id=' . $this->el_id . '&isadmin=' . ($app->isAdmin() ? '1' : '0');

            if ($app->isAdmin()) {
                $output .= '<button type="button" class="btn btn-primary" onclick="SqueezeBox.fromElement(this, {handler:\'iframe\', size: {x: 700, y: 600}, url:\'' . $imageedit_path .'\'})"> ' . JText::_('PLG_USER_STAFFPROFILE_PUBLIC_FIELD_IMAGEEDIT_BUTTON') . '</button> ';
            } else {
                $output .= '<button type="button" class="btn btn-primary" onclick="document.getElementById(\'avatar-image-editor\').setAttribute(\'src\', \'' . $imageedit_path . '\')" data-a11y-dialog-show="avatar-dialog"> ' . JText::_('PLG_USER_STAFFPROFILE_PUBLIC_FIELD_IMAGEEDIT_BUTTON') . '</button> ';
            }
            
            
            $output .= '<button type="button" class="btn" onclick="document.getElementById(\'' . $this->el_id . '-preview\').src=\'' .  $this->savepath  . '/' . $this->default_src . '\';document.getElementById(\'' . $this->savename . '\').value=\'\'">Remove image</button>';
        }
        return $output;
    }

    /**
     * Method to get the field label markup for a spacer.
     * Use the label text or name from the XML element as the spacer or
     * Use a hr="true" to automatically generate plain hr markup
     *
     * @return  string  The field label markup.
     *
     * @since   11.1
     */
    protected function getLabel()
    {
        if (in_array($this->super_user_group_id, $this->session_users_groups) || in_array($this->admin_user_group_id, $this->session_users_groups)) {
            return JText::_($this->element['label_can_edit']);
        } else if (!$this->image_exists || $this->value == '') {
            return JText::_($this->element['label_empty']);
        }

        return JText::_($this->element['label']);
    }

}
