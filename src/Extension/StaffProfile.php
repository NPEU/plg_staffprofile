<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.StaffProfile
 *
 * @copyright   Copyright (C) NPEU 2023.
 * @license     MIT License; see LICENSE.md
 */

namespace NPEU\Plugin\User\StaffProfile\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

//use NPEU\Plugin\User\StaffProfile\Field;

/**
 * NPEU User Staff Profile plugin.
 */
// Note the SubscriberInterface and corresponding getSubscribedEvents() method seem to be a problem
// in the context of a Profile plugin as the UserHelper sends a reference to the data which is
// expected to be modified by the Profile plugins, but the getSubscribedEvents seems to break this
// connection as the $data that gets passedfrom the helper gets put into an Event object, and not
// passed directly.
class StaffProfile extends CMSPlugin implements SubscriberInterface
#class StaffProfile extends CMSPlugin
{
    protected $autoloadLanguage = false;

    /**
     * An internal flag whether plugin should listen any event.
     *
     * @var bool
     *
     * @since   4.3.0
     */
    protected static $enabled = false;

    /**
     * Constructor
     *
     */
    public function __construct($subject, array $config = [], bool $enabled = true)
    {
        // The above enabled parameter was taken from teh Guided Tour plugin but it ir always seems
        // to be false so I'm not sure where this param is passed from. Overriding it for now.
        $enabled = true;

        $this->autoloadLanguage = $enabled;
        self::$enabled          = $enabled;

        parent::__construct($subject, $config);
        $db    = Factory::getDBO();
        $query = $db->getQuery(true);

        // Count the objects in the user group.
        $query->select('id')
              ->from($db->quoteName('#__usergroups'))
              ->where('title = "Staff"');
        $db->setQuery($query);

        $result = $db->loadResult();
        // Returning here is fine:
        #return;
        $this->staff_group_id = (int) $result;

        // Returning here is not! (WHY?!?!?
        #return;
        if ($this->staff_group_id == 0) {
            return;
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
            throw new GenericDataException(JText::_('PLG_USER_STAFFPROFILE_ERROR_FILE_NO_AVATAR_DIR'), 100);
        }
    }

    /**
     * function for getSubscribedEvents : new Joomla 4 feature
     *
     * @return array
     *
     * @since   4.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return self::$enabled ? [
            'onContentPrepareData' => 'onContentPrepareData',
            'onContentPrepareForm' => 'onContentPrepareForm',
            'onUserAfterSave'      => 'onUserAfterSave',
            'onUserAfterDelete'    => 'onUserAfterDelete'
        ] : [];
    }

    /**
     * onContentPrepareData
     *
     * @param   Event  $event
     *
     * @return  boolean
     */
    #public function onContentPrepareData($context, $data) {
    public function onContentPrepareData(Event $event): void
    {
        [$context, $data] = array_values($event->getArguments());

        // Check we are manipulating a valid form.
        if (!in_array($context, ['com_users.profile', 'com_users.user', 'com_admin.profile'])) {
            return;
        }

        if (is_object($data)) {
            $user_id = isset($data->id) ? $data->id : 0;

            if (!isset($data->profile) and $user_id > 0) {
                // Check the user is a staff member before adding this profile:
                $groups = Factory::getUser($user_id)->getAuthorisedGroups();
                #echo "<pre>\n"; var_dump($groups); echo "</pre>\n";exit;
                if (!in_array($this->staff_group_id, $groups)) {
                    return;
                }

                // Sometimes this event handler is passed JUST the user_id, other times it's passed
                // the whole user object. We need the email address in this plugin, so it's not
                // present, we need to get it:
                if (!isset($data->email)) {
                    $user = new User($data->id);
                    $data->email = $user->get('email');
                }

                // Load the profile data from the database.
                $db = Factory::getDbo();
                $db->setQuery(
                    'SELECT profile_key, profile_value FROM #__user_profiles' .
                    ' WHERE user_id = '.(int) $user_id." AND profile_key LIKE 'staffprofile.%'" .
                    ' ORDER BY ordering'
                );

                try {
                    $results = $db->loadRowList();
                } catch (RuntimeException $e) {
                    throw new GenericDataException($e->getErrorMsg(), 500);
                    return;
                }

                if (!isset($data->profile)) {
                    $data->profile = [];
                }

                foreach ($results as $v) {
                    $k = str_replace('staffprofile.', '', $v[0]);
                    $data->profile[$k] = json_decode($v[1], true);
                    if ($data->profile[$k] === null) {
                        $data->profile[$k] = $v[1];
                    }
                }
                // Add alias stuff:
                // (regenerate this every time in case of name change)
                $alias = OutputFilter::stringURLSafe($data->name) . '-' . $data->id;

                $data->profile['alias'] = $alias;


                // Add ImageEdit stuff:
                if (!isset($data->profile['avatar_img'])) {
                    $data->profile['avatar_img'] = '/img/avatars/' . $alias . '-avatar.jpg';
                }

                if (!HTMLHelper::isRegistered('users.imageedit')) {
                    HTMLHelper::register('users.imageedit', [__CLASS__, 'imageedit']);
                }
                $path = Uri::getInstance()->getPath();

                // Article stuff:
                $is_edit = true;
                if (
                    (!Factory::getApplication()->input->get('layout')
                 || Factory::getApplication()->input->get('layout') != 'edit')
                 && preg_match('#/user-profile-edit/\d+#', $path) === 0
                ) {
                    $is_edit = false;
                }
            }
        }
        return;
    }

    // Add ImageEdit stuff:
    public static function imageedit($value) {

        // On the front end the params don't seem to get auto-loaded, so check for that:
        $plugin       = PluginHelper::getPlugin('user', 'staffprofile');
        $params = new Registry($plugin->params);

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
     *
     * @param   Event  $event
     */
    public function onContentPrepareForm(Event $event): void
    {
        [$form, $data] = array_values($event->getArguments());

        if (!($form instanceof \Joomla\CMS\Form\Form)) {
            throw new GenericDataException(Text::_('JERROR_NOT_A_FORM'), 500);
            return;
        }

        // Check we are manipulating a valid form.
        $name = $form->getName();

        if (!in_array($name, ['com_admin.profile', 'com_users.user', 'com_users.profile'])) {
            return;
        }

        $user_id = Factory::getApplication()->input->get('id', 0);

        if (is_object($data)) {
            $user_id = $data->id;
        }
        $groups = Factory::getUser($user_id)->getAuthorisedGroups();

        if (!in_array($this->staff_group_id, $groups)) {
            return;
        }
        $plg_dir = dirname(dirname(dirname(__FILE__)));

        // Add the profile fields to the form.
        FormHelper::addFieldPrefix('NPEU\\Plugin\\User\\StaffProfile\\Field');
        FormHelper::addFormPath($plg_dir . '/forms');

        $form->loadFile('profile', false);

        return;
    }


    /**
     * onUserAfterSave
     *
     * @param   Event  $event
     */
    public function onUserAfterSave(Event $event): void
    {
        [$data, $isnew, $success, $msg] = array_values($event->getArguments());
        $user_id = ArrayHelper::getValue($data, 'id', 0, 'int');

        if ($user_id && $success && isset($data['profile']) && (count($data['profile']))) {
            try {
                $db = Factory::getDbo();
                $db->setQuery(
                    'DELETE FROM #__user_profiles WHERE user_id = '.$user_id .
                    " AND profile_key LIKE 'staffprofile.%'"
                );
                $db->execute();

                $tuples = [];
                $order  = 1;

                foreach ($data['profile'] as $k => $v) {
                    if (is_array($v)) {
                        $v = json_encode($v);
                    }
                    $tuples[] = '('.$user_id.', '.$db->quote('staffprofile.'.$k).', '.$db->quote($v).', '.$order++.')';
                }

                $db->setQuery('INSERT INTO #__user_profiles VALUES '.implode(', ', $tuples));
                $db->execute();
            }
            catch (RuntimeException $e) {
                throw new GenericDataException($e->getErrorMsg(), 500);
                return;
            }
        }

        return;
    }

    /**
     * Remove all user profile information for the given user ID
     *
     * Method is called after user data is deleted from the database
     *
     * @param   Event  $event
     *
     */
    public function onUserAfterDelete(Event $event): void
    {
        [$user, $success, $msg] = array_values($event->getArguments());

        if (!$success) {
            return;
        }

        $user_id = ArrayHelper::getValue($user, 'id', 0, 'int');

        if ($user_id) {
            try {
                $db = Factory::getDbo();
                $db->setQuery(
                    'DELETE FROM #__user_profiles WHERE user_id = '.$user_id .
                    " AND profile_key LIKE 'staffprofile.%'"
                );

                $db->execute();
            }
            catch (Exception $e) {
                throw new GenericDataException($e->getErrorMsg(), 500);
                return;
            }
        }

        return;
    }
}