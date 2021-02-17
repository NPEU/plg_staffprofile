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
 * Form Field class for the Joomla Platform.
 * Provides spacer markup to be used in form layouts.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.3
 */
class JFormFieldEditHelp extends JFormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  11.1
     */
    protected $type = 'EditHelp';

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
     * @param   mixed             $value    The form field value to validate.
     * @param   string            $group    The field name group control value. This acts as as an array container for the field.
     *                                      For example if the field has name="foo" and the group value is set to "bar" then the
     *                                      full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @since   11.1
     */
    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);
        #echo "<pre>\n"; var_dump(JFactory::getApplication()->input->get('layout')); echo "</pre>\n"; exit;
        if (JFactory::getApplication()->input->get('layout') != 'edit') {
            $this->hidden = true;
        }
        return $return;
    }


    protected function getLabel()
    {
        return '';
    }

    protected function getInput()
    {
     
        $heading = JText::_($this->element['heading']);
        $content = JText::_($this->element['content']);
     
        $html = array();
        
        $class = (empty($this->element['class']) ? '' : ' class="' . $this->element['class'] . '"');
        
        $html[] = '<details' . $class . '>';
        $html[] = '<summary><b>' . $heading . '</b></summary>';
        
        $html[] = '<div>';
        $html[] = $content;
        $html[] = '</div>';
        $html[] = '</details>';
        
        return implode('', $html);
        
        
        $text = $this->element['label'] ? (string) $this->element['label'] : (string) $this->element['name'];
        $text = $this->translateLabel ? JText::_($text) : $text;
        if (JFactory::getApplication()->input->get('layout') != 'edit') {
            
            return $text;
        }
        $class = 'input-block-level well';

        $html = array();

        $html[] = '</div>';
        $html[] = '<div class="accordion" id="' . $this->id . '-help" data-icon-only="true" data-expand-text="show" data-collapse-text="hide">';
        $html[] = '<div class="accordion-group">';
        $html[] = '<div class="accordion-heading" data-button-wrap="true" data-button-pos="append" data-target="' . $this->id . '-help-body">';
        $html[] = '<a class="accordion-toggle" data-toggle="collapse" data-parent="' . $this->id . '-help" href="#' . $this->id . '-help-body">' . JText::_('PLG_USER_STAFFPROFILE_PUBS_FIELD_PUBLICATIONS_HELP_HEADING'). '</a>';
        $html[] = '</div>';
        $html[] = '<div id="' . $this->id . '-help-body" class="accordion-body  collapse  in">';
        $html[] = '<div class="accordion-inner">';


        $html[] = '<div class="' . $class . '">';
        if ((string) $this->element['hr'] == 'true')
        {
            $html[] = '<hr class="' . $class . '" />';
        }
        else
        {
            $label = '';

            // Get the label text from the XML element, defaulting to the element name.
            #$text = $this->element['label'] ? (string) $this->element['label'] : (string) $this->element['name'];
            #$text = $this->translateLabel ? JText::_($text) : $text;

            // Build the class for the label.
            $class = !empty($this->description) ? 'hasTip' : '';
            $class = $this->required == true ? $class . ' required' : $class;

            // Add the opening label tag and main attributes attributes.
            $label .= '<div id="' . $this->id . '-lbl" class="' . $class . '"';

            // If a description is specified, use it to build a tooltip.
            if (!empty($this->description))
            {
                $label .= ' title="'
                    . htmlspecialchars(
                    trim($text, ':') . '::' . ($this->translateDescription ? JText::_($this->description) : $this->description),
                    ENT_COMPAT, 'UTF-8'
                ) . '"';
            }

            // Add the label text and closing tag.
            $label .= '>' . $text . '</div>';
            $html[] = $label;
        }
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<div>';


        return implode('', $html);




















        #$class = $this->element['class'] ? (string) $this->element['class'] : '';
        /*$class = 'input-block-level input-xxlarge well';

        $html[] = '<span class="spacer">';
        $html[] = '<span class="before"></span>';
        $html[] = '<span class="' . $class . '">';
        if ((string) $this->element['hr'] == 'true')
        {
            $html[] = '<hr class="' . $class . '" />';
        }
        else
        {
            $label = '';

            // Get the label text from the XML element, defaulting to the element name.
            $text = $this->element['label'] ? (string) $this->element['label'] : (string) $this->element['name'];
            $text = $this->translateLabel ? JText::_($text) : $text;

            // Build the class for the label.
            $class = !empty($this->description) ? 'hasTip' : '';
            $class = $this->required == true ? $class . ' required' : $class;

            // Add the opening label tag and main attributes attributes.
            $label .= '<label id="' . $this->id . '-lbl" class="' . $class . '"';

            // If a description is specified, use it to build a tooltip.
            if (!empty($this->description))
            {
                $label .= ' title="'
                    . htmlspecialchars(
                    trim($text, ':') . '::' . ($this->translateDescription ? JText::_($this->description) : $this->description),
                    ENT_COMPAT, 'UTF-8'
                ) . '"';
            }

            // Add the label text and closing tag.
            $label .= '>' . $text . '</label>';
            $html[] = $label;
        }
        $html[] = '</span>';
        $html[] = '<span class="after"></span>';
        $html[] = '</span>';

        return implode('', $html);*/
    }

    /**
     * Method to get the field title.
     *
     * @return  string  The field title.
     *
     * @since   11.1
     */
    protected function getTitle()
    {
        return $this->getLabel();
    }
}
