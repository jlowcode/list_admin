<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

class PlgFabrik_ElementList_admin extends PlgFabrik_Element {
    private $listSelectedParams;
    private $elementsFromList;

    public function render($data, $repeatCounter = 0)
    {
        $input = $this->app->input;

        $displayData = new stdClass;
        $displayData->access = in_array('2', $this->user->getAuthorisedViewLevels());
        $displayData->view = $input->get('view');
        $displayData->attributes = $this->inputProperties($repeatCounter);
        $displayData->html = $this->getFabrikListsHtml();

        $layout = $this->getLayout('details');

        return $layout->render($displayData);
    }

    private function getFabrikListsHtml() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('id, label')->from('#__fabrik_lists');
        $db->setQuery($query);
        $lists = $db->loadObjectList();

        $dom = new DOMDocument();

        $div = $dom->createElement('div');

        $selectList = $dom->createElement('div');
        $selectList->setAttribute('id', 'list_admin_select');

        $label = $dom->createElement('label');
        $label->setAttribute('for', 'list_admin_select_list');
        $labelText = $dom->createDocumentFragment();
        $labelText->appendXML(JText::_('PLG_FABRIK_ELEMENT_LIST_ADMIN_CHOOSE_LIST'));
        $label->appendChild($labelText);

        $select = $dom->createElement('select');
        $select->setAttribute('id', 'list_admin_select_list');
        $select->setAttribute('name', 'list_admin_select_list');
        foreach($lists as $list) {
            $option = $dom->createElement('option');
            $option->setAttribute('value', $list->id);
            $optionText = $dom->createDocumentFragment();
            $optionText->appendXML($list->label);
            $option->appendChild($optionText);
            $select->appendChild($option);
        }

        $button = $dom->createElement('button');
        $button->setAttribute('id', 'list_selected');
        $button->setAttribute('type', 'button');
        $button->setAttribute('class', 'btn btn-secondary button');
        $buttonText = $dom->createDocumentFragment();
        $buttonText->appendXML(JText::_('PLG_FABRIK_ELEMENT_LIST_ADMIN_BUTTON_LABEL'));
        $button->appendChild($buttonText);

        $selectList->appendChild($label);
        $selectList->appendChild($select);
        $selectList->appendChild($button);

        $fields = $dom->createElement('div');
        $fields->setAttribute('id', 'list_admin_fields');

        $div->appendChild($selectList);
        $div->appendChild($fields);

        return $dom->saveHTML($div);
    }

    private function getListParams($id) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('params, template, order_by, order_dir')->from('#__fabrik_lists')->where("id = '{$id}'");
        $db->setQuery($query);
        $result = $db->loadObject();
        $result->params = (array) json_decode($result->params);

        return $result;
    }

    public function onCreateListFieldsHtml() {
        $input = $this->app->input;
        $this->setId($input->getInt('element_id'));
        $this->loadMeForAjax();

        $listId = $_POST["list_selected"];

        $html = $this->createHtmlOfFields($listId);

        echo json_encode($html);
    }

    private function createHtmlOfFields($listId) {
        $this->listSelectedParams = $this->getListParams($listId);
        $this->elementsFromList = $this->getElementsFromList($listId);

        $fields = array(
            'show-table-filters',
            'list_filter_cols',
            'template',
            'order',
        );

        $html = "<div class='fabrikSubElementContainer'><br><br>";
        foreach ($fields as $field) {
            $html .= "<div class='form-group'><br>";
            $html .= $this->createHtmlFromType($field);
            $html .= "</div>";
        }
        $html .= "</div>";

        return $html;
    }

    private function createHtmlFromType($field) {
        switch ($field) {
            case 'show-table-filters':
                $html = $this->createHtmlShowTableFilters();
                break;
            case 'list_filter_cols':
                $html = $this->createHtmlListFilterCols();
                break;
            case 'template':
                $html = $this->createHtmlTemplate();
                break;
            case 'order':
                $html = $this->createHtmlOrder();
                break;
        }

        return $html;
    }

    private function createHtmlShowTableFilters() {
        $params = $this->listSelectedParams;
        $selected = $params->params['show-table-filters'];

        $label = "<label for='list_admin_show-table-filters'>" . JText::_('PLG_FABRIK_ELEMENT_LIST_ADMIN_FILTERS') . "</label>";

        $fields = array(
            'Não',
            'Acima',
            'Acima (alternável)',
            'Títulos abaixo',
            'Títulos abaixo (alternável)',
            'Pop-up',
            'Lado esquerdo'
        );

        $html = $label;
        $html .= "<select name='list_admin_show-table-filters'>";
        foreach ($fields as $i => $field) {
            if ((int)$i === (int)$selected) {
                $html .= "<option value='{$i}' selected>{$field}</option>";
            }
            else {
                $html .= "<option value='{$i}'>{$field}</option>";
            }
        }
        $html .= "</select>";

        return $html;
    }

    private function createHtmlListFilterCols() {
        $params = $this->listSelectedParams;
        $selected = $params->params['list_filter_cols'];

        $label = "<label for='list_admin_list_filter_cols'>" . JText::_('PLG_FABRIK_ELEMENT_LIST_ADMIN_FILTER_COLUMNS') . "</label>";

        $html = $label;
        $html .= "<input type='text' name='list_admin_list_filter_cols' value='{$selected}'>";

        return $html;
    }

    private function createHtmlTemplate() {
        $params = $this->listSelectedParams;
        $selected = $params->template;

        $label = "<label for='list_admin_template'>" . JText::_('PLG_FABRIK_ELEMENT_LIST_ADMIN_TEMPLATE') . "</label>";

        $fields = array(
            '- Usar Padrão -',
            'bootstrap',
            'cards',
            'div',
            'mosaico',
            'workflow'
        );

        $html = $label;
        $html .= "<select name='list_admin_template'>";
        foreach ($fields as $i => $field) {
            if ($field === $selected) {
                $html .= "<option value='{$field}' selected>{$field}</option>";
            }
            else {
                $html .= "<option value='{$field}'>{$field}</option>";
            }
        }
        $html .= "</select>";

        return $html;
    }

    private function createHtmlOrder() {
        $params = $this->listSelectedParams;
        $elements = $this->elementsFromList;
        $order_by = json_decode($params->order_by);
        $order_dir = json_decode($params->order_dir);

        $label = "<label for='list_admin_order_by'>" . JText::_('PLG_FABRIK_ELEMENT_LIST_ADMIN_ORDER') . "</label>";

        $html = $label;

        $html .= "<select name='list_admin_order_by'>";
        foreach ($elements as $element) {
            if ($element->id === $order_by[0]) {
                $html .= "<option value='{$element->id}' selected>{$element->name}</option>";
            }
            else {
                $html .= "<option value='{$element->id}'>{$element->name}</option>";
            }
        }
        $html .= "</select>";

        $fields = array(
            'ASC' => 'Crescente',
            'DESC' => 'Decrescente'
        );
        $html .= "<select name='list_admin_order_dir'>";
        foreach ($fields as $i => $field) {
            if ($i === $order_dir[0]) {
                $html .= "<option value='{$i}' selected>{$field}</option>";
            }
            else {
                $html .= "<option value='{$i}'>{$field}</option>";
            }
        }
        $html .= "</select>";

        return $html;
    }

    private function getElementsFromList($id) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('form_id')->from('#__fabrik_lists')->where("id = '{$id}'");
        $db->setQuery($query);
        $formId = $db->loadResult();

        $query = $db->getQuery(true);
        $query->select('group_id')->from('#__fabrik_formgroup')->where("form_id = '{$formId}'");
        $db->setQuery($query);
        $groupsId = $db->loadColumn();

        $elements = array();
        foreach ($groupsId as $groupId) {
            $query = $db->getQuery(true);
            $query->select('id, name')->from('#__fabrik_elements')->where("group_id = '{$groupId}'");
            $db->setQuery($query);
            $elements = array_merge($elements, $db->loadObjectList());
        }

        return $elements;
    }

    public function elementJavascript($repeatCounter)
    {
        $app = $this->app;

        $opts = $this->getElementJSOptions($repeatCounter);

        $opts->view = $app->input->get('view');
        $opts->element_id = $this->getId();

        $opts = json_encode($opts);

        $jsFiles = array();
        $jsFiles['Fabrik'] = 'media/com_fabrik/js/fabrik.js';
        $jsFiles['FbListAdmin'] = 'plugins/fabrik_element/list_admin/list_admin.js';

        $script = "new FbListAdmin($opts);";
        FabrikHelperHTML::script($jsFiles, $script);
    }

    private function updateTable() {
        $formModel = $this->getFormModel();
        $tableName = $formModel->getTableName();
        $elementName = $this->element->name;

        $db = JFactory::getDbo();
        $query = "ALTER TABLE {$tableName} MODIFY {$elementName} text";
        $db->setQuery($query);
        $db->execute();
    }

    public function onAfterProcess() {
        $this->updateTable();

        $formModel = $this->getFormModel();
        $formData = $formModel->formData;

        $listId = $formData['list_admin_select_list'];
        $config = $this->getListParams($listId);
        $config->params['show-table-filters'] = $formData['list_admin_show-table-filters'];
        $config->params['list_filter_cols'] = $formData['list_admin_list_filter_cols'];
        $config->template = $formData['list_admin_template'];
        $config->order_by = json_encode(array($formData['list_admin_order_by']));
        $config->order_dir = json_encode(array($formData['list_admin_order_dir']));
        $config->params = (object) $config->params;
        $config->params = json_encode($config->params);
        $config->id = $listId;

        JFactory::getDbo()->updateObject('#__fabrik_lists', $config, 'id');
    }
}