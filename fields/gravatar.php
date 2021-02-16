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
 * Pseudo form field that displays an image from gravatar.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.3
 */
class JFormFieldGravatar extends JFormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  11.1
     */
    protected $type = 'Gravatar';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   11.3
     */
    protected function getInput()
    {
        return '<img src="//www.gravatar.com/avatar/'.htmlspecialchars($this->value).'" height="80" width="80" alt="" />';
    }
}
