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
class EditMsgpField extends Field
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  11.1
     */
    protected $type = 'EditMsg';

    protected $layout;

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

        $app = Factory::getApplication();
        $t = $app->getTemplate(true);

        /*
        if ($t->template != 'npeu5') {
            return;
        }
        */


        $return    = parent::setup($element, $value, $group);
        $layout    = Factory::getApplication()->input->get('layout');

        #echo "<pre>\n"; var_dump($menu_item->query['layout']); echo "</pre>\n"; exit;
        if (!$layout) {
            if ($t->template == 'npeu5') {
                $menu_item = get_menu_item();
            } else {
                $menu_item = TplNPEU6Helper::get_menu_item();
            }
            if (isset($menu_item->query['layout'])) {
                $layout = $menu_item->query['layout'];
            }
        }
        #echo "<pre>\n"; var_dump(Factory::getApplication()->input->get('layout')); echo "</pre>\n"; exit;
        #echo "<pre>\n"; var_dump(Factory::getApplication()); echo "</pre>\n"; exit;

       # echo "<pre>\n"; var_dump($menu_item); echo "</pre>\n"; exit;
        if ($layout != 'edit') {
            $this->hidden = true;
        }

        $this->layout = $layout;
        return $return;
    }

    /**
     * Method to get the field input markup for a spacer.
     * The spacer does not have accept input.
     *
     * @return  string  The field input markup.
     *
     * @since   11.1
     */
    protected function getInput()
    {
        return ' ';
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
        #if (Factory::getApplication()->input->get('layout') != 'edit') {
        if ($this->layout != 'edit') {
            return '';
        }

        $html = array();
        #$class = $this->element['class'] ? (string) $this->element['class'] : '';
        $class = 'input-block-level input-xxlarge well pagination-centered invalid';

        $html[] = '<span class="' . $class . '">';
        // Get the label text from the XML element, defaulting to the element name.
        $text   = $this->element['label'] ? (string) $this->element['label'] : (string) $this->element['name'];
        $text   = $this->translateLabel ? Text::_($text) : $text;
        $html[] = $text;

        $html[] = '</span>';

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
