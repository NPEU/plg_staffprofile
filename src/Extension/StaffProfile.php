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

        #$this->loadLanguage();
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
        #echo "<pre>\n"; var_dump($this->staff_group_id); echo "</pre>\n";exit;

        // Returning here is not! (WHY?!?!?
        #return;
        if ($this->staff_group_id == 0) {
            return;
        }

        $app = Factory::getApplication();
        // @TODO - investigate using a modal that's already available in admin. Not sure why I have
        // to load this:
        if ($app->isClient('administrator')) {
            // Add modal script (Squeezebox) for admin
            $doc = Factory::getDocument();
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
            throw new GenericDataException(JText::_('PLG_USER_STAFFPROFILE_ERROR_FILE_NO_AVATAR_DIR'), 100);
        }

        #echo "<pre>\n"; var_dump($this->avatar_dir); echo "</pre>\n";exit;
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
     * @param   string  $context  The context for the data
     * @param   object  $data     An object containing the data for the form.
     *
     * @return  boolean
     */
    #public function onContentPrepareData($context, $data) {
    public function onContentPrepareData(Event $event) {
        $args    = $event->getArguments();
        $context = $args[0];
        $data    = $args[1];

        // Check we are manipulating a valid form.
        if (!in_array($context, array('com_users.profile', 'com_users.user', 'com_admin.profile'))) {
            return true;
        }

        if (is_object($data)) {
            $user_id = isset($data->id) ? $data->id : 0;

            if (!isset($data->profile) and $user_id > 0) {
                #$data->staff_profile = false;
                // Check the user is a staff member before adding this profile:
                $groups = Factory::getUser($user_id)->getAuthorisedGroups();
                #echo "<pre>\n"; var_dump($groups); echo "</pre>\n";exit;
                if (!in_array($this->staff_group_id, $groups)) {
                    return true;
                }
                #$data->staff_profile = true;

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
                    return false;
                }

                if (!isset($data->profile)) {
                    $data->profile = array();
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
                    #$data->profile['avatar_img'] = $alias . '-avatar';
                    $data->profile['avatar_img'] = '/img/avatars/' . $alias . '-avatar.jpg';
                    #$data->profile['avatar_img'] = urldecode(str_replace(' ', '_', $data->name) . '_' . $data->id);
                    #echo "<pre>\n"; var_dump($data->profile['imageedit']); echo "</pre>\n"; exit;
                }

                if (!HTMLHelper::isRegistered('users.imageedit')) {
                    HTMLHelper::register('users.imageedit', array(__CLASS__, 'imageedit'));
                }

                #echo "<pre>\n"; var_dump(Factory::getApplication()->input); echo "</pre>\n";exit;
                $path = Uri::getInstance()->getPath();
                #echo "<pre>\n"; var_dump($Uri = Uri::getInstance()); echo "</pre>\n";exit;
                #echo "<pre>\n"; var_dump($path); echo "</pre>\n";exit;
                #echo "<pre>\n"; var_dump(preg_match('#/user-profile-edit/\d+#', $path)); echo "</pre>\n";exit;
                // Article stuff:
                $is_edit = true;
                if (
                    (!Factory::getApplication()->input->get('layout')
                 || Factory::getApplication()->input->get('layout') != 'edit')
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
     */
    public function onContentPrepareForm(Event $event) {
        $args    = $event->getArguments();
        $form    = $args[0];
        $data    = $args[1];

        if (!($form instanceof \Joomla\CMS\Form\Form)) {
            throw new GenericDataException(Text::_('JERROR_NOT_A_FORM'), 500);
            return false;
        }

        // Check we are manipulating a valid form.
        $name = $form->getName();

        if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile'))) {
            return true;
        }

        $user_id = Factory::getApplication()->input->get('id', 0);

        if (is_object($data)) {
            $user_id = $data->id;
        }
        $groups = Factory::getUser($user_id)->getAuthorisedGroups();

        if (!in_array($this->staff_group_id, $groups)) {
            return true;
        }
        #echo "<pre>\n"; var_dump($form); echo "</pre>\n";# exit;
        #echo "<pre>\n"; var_dump(dirname(__FILE__) . '/profiles'); echo "</pre>\n"; exit;

        // Add the profile fields to the form.
        Form::addFormPath(dirname(__FILE__) . '/profiles');
        $form->loadFile('profile', false);
        #echo "<pre>\n"; var_dump($form); echo "</pre>\n"; exit;

        // Add fields:
        Form::addFieldPath(dirname(__FILE__).'/fields');

        // Hacky stuff to add save handler for editor:
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            //return true;
            $doc = Factory::getDocument();
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
        $user_id = ArrayHelper::getValue($data, 'id', 0, 'int');

        if ($user_id && $result && isset($data['profile']) && (count($data['profile']))) {
            try {
                $db = Factory::getDbo();
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
                throw new GenericDataException($e->getErrorMsg(), 500);
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
                return false;
            }
        }

        return true;
    }
}