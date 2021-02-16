<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  plg_staff_profile
 *
 * @copyright   Copyright (C) 2013 Andy Kirk.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Form\Field;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;

#JFormHelper::loadFieldClass('editor');
#FormHelper::loadFieldClass('textarea');
FormHelper::loadFieldClass('editor');

/**
 * A textarea field for content creation
 *
 * @see    JEditor
 * @since  1.6
 */
class ReqEditorField extends EditorField
{

    /**
     * The form field type.
     *
     * @var    string
     * @since  1.6
     */
    public $type = 'ReqEditor';
    
    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   11.3
     */
    protected function getInput()
    {
        // Note, this is a clone of the core editor methods in:
        // /libraries/src/Form/Field/EditorField.php
        // with the addition of the required setting.
        
        #echo "<pre>\n"; var_dump($this->required); echo "</pre>\n"; exit;
        
        // Get an editor object.
        $editor = $this->getEditor();
        $params = array(
            'autofocus' => $this->autofocus,
            'readonly'  => $this->readonly || $this->disabled,
            'syntax'    => (string) $this->element['syntax'],
        );

        return $editor->display(
            $this->name,
            htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8'),
            $this->width,
            $this->height,
            $this->columns,
            $this->rows,
            $this->buttons ? (is_array($this->buttons) ? array_merge($this->buttons, $this->hide) : $this->hide) : false,
            $this->id,
            $this->asset,
            $this->form->getValue($this->authorField),
            $params
        );
    }
}