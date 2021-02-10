<?php
/**
 * @package		Extras
 * @subpackage	com_extras
 * @copyright	Copyright (C) 2012 Andy Kirk.
 * @author		Andy Kirk
 * @license		License GNU General Public License version 2 or later
 */

// No direct access
defined('_JEXEC') or die;
JFormHelper::loadFieldClass('checkbox');


/**
 * Checkbox with hidden field for default value.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @link        http://www.w3.org/TR/html-markup/input.checkbox.html#input.checkbox
 * @see         JFormFieldCheckboxes
 * @since       11.1
 */
class JFormFieldCheckboxdefault extends JFormFieldCheckbox
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'Checkboxdefault';

	/**
	 * Method to get the field input markup.
	 * The checked element sets the field to selected.
	 *
	 * @return  string   The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$return = parent::getInput();

		if (isset($this->element['default'])) {
			$return = '<input type="hidden" name="' . $this->name . '" value="' . $this->element['default'] . '" />' . $return;
		}

		return $return;

		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$checked = ((string) $this->element['value'] == $this->value) ? ' checked="checked"' : '';

		// Initialize JavaScript field attributes.
		$onclick = $this->element['onclick'] ? ' onclick="' . (string) $this->element['onclick'] . '"' : '';

		return '<input type="checkbox" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
			. htmlspecialchars((string) $this->element['value'], ENT_COMPAT, 'UTF-8') . '"' . $class . $checked . $disabled . $onclick . '/>';
	}
}
