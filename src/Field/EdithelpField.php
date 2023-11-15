<?php
namespace NPEU\Plugin\User\StaffProfile\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\UsergrouplistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

#JFormHelper::loadFieldClass('list');

/**
 * Form field for a list of admin groups.
 */
class EditHelpField extends Field
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
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);
        #echo "<pre>\n"; var_dump(Factory::getApplication()->input->get('layout')); echo "</pre>\n"; exit;
        if (Factory::getApplication()->input->get('layout') != 'edit') {
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

        $heading = Text::_($this->element['heading']);
        $content = Text::_($this->element['content']);

        $html = array();

        $class = (empty($this->element['class']) ? '' : ' class="' . $this->element['class'] . '"');

        $html[] = '<details' . $class . '>';
        $html[] = '<summary><b>' . $heading . '</b></summary>';

        $html[] = '<div>';
        $html[] = $content;
        $html[] = '</div>';
        $html[] = '</details>';

        return implode('', $html);
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
