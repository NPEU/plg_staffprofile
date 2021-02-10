<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  user.plg_staffprofile
 *
 * @copyright   Copyright (C) 2012 Andy Kirk.
 * @author      Andy Kirk
 * @license     License GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);


$app     = JFactory::getApplication();
$jinput  = $app->input;
$task    = $jinput->get('task');
$view    = $jinput->get('view');

/**
 * A plugin to to new profile fields for staff members.
 *
 */
class plgUserStaffProfile extends JPlugin {
    protected $staff_group_id;
    protected $avatar_dir;
    #protected $avatar_size;

    /**
     * Constructor
     *
     */
    public function __construct(&$subject, $config) {

        parent::__construct($subject, $config);
        $this->loadLanguage();

        $db    = JFactory::getDBO();
        $query = $db->getQuery(true);

        // Count the objects in the user group.
        $query->select('id')
              ->from($db->quoteName('#__usergroups'))
              ->where('title = "Staff"');
        $db->setQuery($query);

        $result = $db->loadResult();
        // Returning here is fine:
        #return;
        #echo "<pre>\n"; var_dump($result); echo "</pre>\n";
        #echo "<pre>\n"; var_dump($this->staff_group_id); echo "</pre>\n";
        $this->staff_group_id = (int) $result;

        // Returning here is not! (WHY?!?!?
        #return;
        if ($this->staff_group_id == 0) {
            return;
        }

        $app = JFactory::getApplication();
        // @TODO - investigate using a modal that's already available in admin. Not sure why I have
        // to load this:
        if ($app->isAdmin()) {
            // Add modal script (Squeezebox) for admin
            $doc = JFactory::getDocument();
            $doc->addScript('/media/system/js/mootools-core.js');
            $doc->addScript('/media/system/js/mootools-more.js');
            $doc->addScript('/media/system/js/modal.js');

            $doc->addScriptDeclaration("
                jQuery(function($) {
                    SqueezeBox.initialize({});
                    SqueezeBox.assign($('a.modal').get(), {
                        parse: 'rel'
                    });
                });
            ");

            $doc->addStyleDeclaration('
                #sbox-overlay[aria-hidden="false"] {
                    background-color: #000000;
                    height: 3337px;
                    left: 0;
                    position: absolute;
                    top: -20px;
                    width: 100%;
                }
                #sbox-window[aria-hidden="false"] {
                    left: 50% !important;
                    margin-left: -350px;
                    position: fixed;
                    top: 50px !important;
                    padding: 0 !important;
                }
            ');
        }

        $avatar_dir              = $this->params->get('avatar_dir', false);
        $upload_file_permissions = octdec($this->params->get('upload_file_permissions', false));
        $upload_file_group       = $this->params->get('upload_file_group', false);
        $upload_file_owner       = $this->params->get('upload_file_owner', false);

        if ($avatar_dir) {
            $this->avatar_dir = $avatar_dir;
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $avatar_dir)) {
                mkdir($_SERVER['DOCUMENT_ROOT'] . $avatar_dir);

                chmod($avatar_dir, $upload_file_permissions);
                chgrp($avatar_dir, $upload_file_group);
                chown($avatar_dir, $upload_file_owner);
            }
        } else {
            JError::raiseWarning(100, JText::_('PLG_USER_STAFFPROFILE_ERROR_FILE_NO_AVATAR_DIR'));
        }
    }

    /**
     * onContentPrepareData
     */
    public function onContentPrepareData($context, $data) {
        #echo "<pre>\n"; var_dump($context); echo "</pre>\n";#exit;
        #echo "<pre>\n"; var_dump($data); echo "</pre>\n";exit;
        // Check we are manipulating a valid form.
        if (!in_array($context, array('com_users.profile', 'com_users.user', 'com_admin.profile'))) {
            return true;
        }

        if (is_object($data)) {
            $user_id = isset($data->id) ? $data->id : 0;

            if (!isset($data->profile) and $user_id > 0) {
                #$data->staff_profile = false;
                // Check the user is a staff member before adding this profile:
                $groups = JFactory::getUser($user_id)->getAuthorisedGroups();
                #echo "<pre>\n"; var_dump($groups); echo "</pre>\n";exit;
                if (!in_array($this->staff_group_id, $groups)) {
                    return true;
                }
                #$data->staff_profile = true;

                // Sometimes this event handler is passed JUST the user_id, other times it's passed
                // the whole user object. We need the email address in this plugin, so it's not
                // present, we need to get it:
                if (!isset($data->email)) {
                    $user = new JUser($data->id);
                    $data->email = $user->get('email');
                }

                // Load the profile data from the database.
                $db = JFactory::getDbo();
                $db->setQuery(
                    'SELECT profile_key, profile_value FROM #__user_profiles' .
                    ' WHERE user_id = '.(int) $user_id." AND profile_key LIKE 'staffprofile.%'" .
                    ' ORDER BY ordering'
                );

                try {
                    $results = $db->loadRowList();
                }
                catch (RuntimeException $e) {
                    $this->_subject->setError($e->getMessage());
                    return false;
                }

                if (!isset($data->profile)) {
                    $data->profile = array();
                }

                foreach ($results as $v) {
                    $k = str_replace('staffprofile.', '', $v[0]);
                    $data->profile[$k] = json_decode($v[1], true);
                    if ($data->profile[$k] === null)
                    {
                        $data->profile[$k] = $v[1];
                    }
                }
                // Add alias stuff:
                // (regenerate this every time in case of name change)
                $alias = JApplication::stringURLSafe($data->name) . '-' . $data->id;
                $data->profile['alias'] = $alias;


                // Add ImageEdit stuff:
                if (!isset($data->profile['avatar_img'])) {
                    #$data->profile['avatar_img'] = $alias . '-avatar';
                    $data->profile['avatar_img'] = '/img/avatars/' . $alias . '-avatar.jpg';
                    #$data->profile['avatar_img'] = urldecode(str_replace(' ', '_', $data->name) . '_' . $data->id);
                    #echo "<pre>\n"; var_dump($data->profile['imageedit']); echo "</pre>\n"; exit;
                }

                if (!JHtml::isRegistered('users.imageedit')) {
                    JHtml::register('users.imageedit', array(__CLASS__, 'imageedit'));
                }

                #echo "<pre>\n"; var_dump(JFactory::getApplication()->input); echo "</pre>\n";exit;
                $path = JUri::getInstance()->getPath();
                #echo "<pre>\n"; var_dump($juri = JUri::getInstance()); echo "</pre>\n";exit;
                #echo "<pre>\n"; var_dump($path); echo "</pre>\n";exit;
                #echo "<pre>\n"; var_dump(preg_match('#/user-profile-edit/\d+#', $path)); echo "</pre>\n";exit;
                // Article stuff:
                $is_edit = true;
                if (
                    (!JFactory::getApplication()->input->get('layout')
                 || JFactory::getApplication()->input->get('layout') != 'edit')
                 && preg_match('#/user-profile-edit/\d+#', $path) === 0
                ) {
                    $is_edit = false;
                }

                #echo "<pre>\n"; var_dump($is_edit); echo "</pre>\n";
                #echo "<pre>\n"; var_dump($data); echo "</pre>\n";

            }
        }
        #echo "<pre>\n"; var_dump($data); echo "</pre>\n"; exit;
        return true;
    }

    // Add ImageEdit stuff:
    public static function imageedit($value) {

        // On the front end the params don't seem to get auto-loaded, so check for that:
        $plugin       = JPluginHelper::getPlugin('user', 'staffprofile');
        $params = new JRegistry($plugin->params);

        // This function appears to be passed the user alias, but I've no idea
        // why or where it's being called, so hack the correct value:

        $avatar_dir = $params->get('avatar_dir', false);

        $src = $value;
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $src)) {
            #$src = 'https://www.placehold.it/80x80/EFEFEF/AAAAAA&text=no+image';
            $src = $avatar_dir . '/_none.jpg';
        }
        return '<img src="' . $src . '" height="80" width="80" alt="" />';
    }

    /**
     * onContentPrepareForm
     */
    public function onContentPrepareForm($form, $data) {
        #echo "<pre>\n"; var_dump($data); echo "</pre>\n"; exit;
        if (!($form instanceof JForm)) {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        // Check we are manipulating a valid form.
        $name = $form->getName();

        if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile'))) {
            return true;
        }

        $user_id = JFactory::getApplication()->input->get('id', 0);

        if (is_object($data)) {
            $user_id = $data->id;
        }
        $groups = JFactory::getUser($user_id)->getAuthorisedGroups();

        if (!in_array($this->staff_group_id, $groups)) {
            return true;
        }
        #echo "<pre>\n"; var_dump($form); echo "</pre>\n";# exit;
        #echo "<pre>\n"; var_dump(dirname(__FILE__) . '/profiles'); echo "</pre>\n"; exit;

        // Add the profile fields to the form.
        JForm::addFormPath(dirname(__FILE__) . '/profiles');
        $form->loadFile('profile', false);
        #echo "<pre>\n"; var_dump($form); echo "</pre>\n"; exit;

        // Add fields:
        JForm::addFieldPath(dirname(__FILE__).'/fields');

        // Hacky stuff to add save handler for editor:
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            //return true;
            $doc = JFactory::getDocument();
            $script = array();

            $context = 'profile';
            if ($app->input->get('option', false) == 'com_users') {
                $context = 'user';
            }

            $style = array();

            $style[] = '.form-horizontal .control-label {';
            $style[] = '    width: 160px;';
            $style[] = '}';

            $str = implode("\n", $style);

            $doc->addStyleDeclaration($str);
        }
        #return false;
        return true;
    }


    /**
     * onUserAfterSave
     */
    function onUserAfterSave($data, $isNew, $result, $error) {
        #echo "<pre>\n"; var_dump($data); echo "</pre>\n"; exit;
        $user_id = JArrayHelper::getValue($data, 'id', 0, 'int');

        if ($user_id && $result && isset($data['profile']) && (count($data['profile']))) {
            try {
                $db = JFactory::getDbo();
                $db->setQuery(
                    'DELETE FROM #__user_profiles WHERE user_id = '.$user_id .
                    " AND profile_key LIKE 'staffprofile.%'"
                );
                $db->execute();

                $tuples = array();
                $order  = 1;

                foreach ($data['profile'] as $k => $v) {
                    if (is_array($v)) {
                        $v = json_encode($v);
                    }
                    $tuples[] = '('.$user_id.', '.$db->quote('staffprofile.'.$k).', '.$db->quote($v).', '.$order++.')';
                }

                $db->setQuery('INSERT INTO #__user_profiles VALUES '.implode(', ', $tuples));
                $db->execute();
                #echo "<pre>\n"; var_dump($data); echo "</pre>\n"; exit;
            }
            catch (RuntimeException $e) {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Remove all user profile information for the given user ID
     *
     * Method is called after user data is deleted from the database
     *
     */
    public function onUserAfterDelete($user, $success, $msg) {

        if (!$success) {
            return false;
        }

        $user_id = JArrayHelper::getValue($user, 'id', 0, 'int');

        if ($user_id) {
            try {
                $db = JFactory::getDbo();
                $db->setQuery(
                    'DELETE FROM #__user_profiles WHERE user_id = '.$user_id .
                    " AND profile_key LIKE 'staffprofile.%'"
                );

                $db->execute();
            }
            catch (Exception $e) {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }
}
