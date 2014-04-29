<?php

namespace Drupal\search_api\Plugin\SearchApi\Processor;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * @SearchApiProcessor(
 *   id = "search_api_add_aggregation_processor",
 *   label = @Translation("Aggregation processor"),
 *   description = @Translation("Create aggregate fields to be additionally indexed.")
 * )
 */
class AddAggregation extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /*
    $fields = $this->index->getFields(FALSE);
    $field_options = array();
    $field_properties = array();
    foreach ($fields as $name => $field) {
      $field_options[$name] = String::checkPlain($field['name']);
      $field_properties[$name] = array(
        '#attributes' => array('title' => $name),
        '#description' => String::checkPlain($field['description']),
      );
    }
    $additional = empty($this->configuration['fields']) ? array() : $this->configuration['fields'];

    $types = $this->getTypes();
    $type_descriptions = $this->getTypes('description');
    $tmp = array();
    foreach ($types as $type => $name) {
      $tmp[$type] = array(
        '#type' => 'item',
        '#description' => $type_descriptions[$type],
      );
    }
    $type_descriptions = $tmp;

    $form['description'] = array(
      '#markup' => t('<p>This data alteration lets you define additional fields that will be added to this index. ' .
        'Each of these new fields will be an aggregation of one or more existing fields.</p>' .
        '<p>To add a new aggregated field, click the "Add new field" button and then fill out the form.</p>' .
        '<p>To remove a previously defined field, click the "Remove field" button.</p>' .
        '<p>You can also change the names or contained fields of existing aggregated fields.</p>'),
    );
    $form['fields']['#prefix'] = '<div id="search-api-alter-add-aggregation-field-settings">';
    $form['fields']['#suffix'] = '</div>';
    if (isset($this->changes)) {
      $form['fields']['#prefix'] .= '<div class="messages warning">All changes in the form will not be saved until the <em>Save configuration</em> button at the form bottom is clicked.</div>';
    }
    foreach ($additional as $name => $field) {
      $form['fields'][$name] = array(
        '#type' => 'fieldset',
        '#title' => $field['name'] ? $field['name'] : t('New field'),
        '#collapsible' => TRUE,
        '#collapsed' => (boolean) $field['name'],
      );
      $form['fields'][$name]['name'] = array(
        '#type' => 'textfield',
        '#title' => t('New field name'),
        '#default_value' => $field['name'],
        '#required' => TRUE,
      );
      $form['fields'][$name]['type'] = array(
        '#type' => 'select',
        '#title' => t('Aggregation type'),
        '#options' => $types,
        '#default_value' => $field['type'],
        '#required' => TRUE,
      );
      $form['fields'][$name]['type_descriptions'] = $type_descriptions;
      foreach (array_keys($types) as $type) {
        $form['fields'][$name]['type_descriptions'][$type]['#states']['visible'][':input[name="callbacks[search_api_alter_add_aggregation][settings][fields][' . $name . '][type]"]']['value'] = $type;
      }
      $form['fields'][$name]['fields'] = array_merge($field_properties, array(
        '#type' => 'checkboxes',
        '#title' => t('Contained fields'),
        '#options' => $field_options,
        '#default_value' => array_combine($field['fields'], $field['fields']),
        '#attributes' => array('class' => array('search-api-alter-add-aggregation-fields')),
        '#required' => TRUE,
      ));
      $form['fields'][$name]['actions'] = array(
        '#type' => 'actions',
        'remove' => array(
          '#type' => 'submit',
          '#value' => t('Remove field'),
          '#submit' => array('_search_api_add_aggregation_field_submit'),
          '#limit_validation_errors' => array(),
          '#name' => 'search_api_add_aggregation_remove_' . $name,
          '#ajax' => array(
            'callback' => '_search_api_add_aggregation_field_ajax',
            'wrapper' => 'search-api-alter-add-aggregation-field-settings',
          ),
        ),
      );
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['add_field'] = array(
      '#type' => 'submit',
      '#value' => t('Add new field'),
      '#submit' => array('_search_api_add_aggregation_field_submit'),
      '#limit_validation_errors' => array(),
      '#ajax' => array(
        'callback' => '_search_api_add_aggregation_field_ajax',
        'wrapper' => 'search-api-alter-add-aggregation-field-settings',
      ),
    );
    */
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
    /*
    unset($form_state['values']['actions']);
    if (empty($form_state['values']['fields'])) {
      return;
    }
    foreach ($form_state['values']['fields'] as $name => $field) {
      $fields = $form_state['values']['fields'][$name]['fields'] = array_values(array_filter($field['fields']));
      unset($form_state['values']['fields'][$name]['actions']);
      if ($field['name'] && !$fields) {
        form_error($form['fields'][$name]['fields'], t('You have to select at least one field to aggregate. If you want to remove an aggregated field, please delete its name.'));
      }
    }
    */
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    if (!$items) {
      return;
    }
    if (isset($this->configuration['fields'])) {
      $types = $this->getTypes('type');
      foreach ($items as $item) {
        $wrapper = $this->index->entityWrapper($item);
        foreach ($this->configuration['fields'] as $name => $field) {
          if ($field['name']) {
            $required_fields = array();
            foreach ($field['fields'] as $f) {
              if (!isset($required_fields[$f])) {
                $required_fields[$f]['type'] = $types[$field['type']];
              }
            }
            search_api_extract_fields($wrapper, $required_fields);
            $values = array();
            foreach ($required_fields as $f) {
              if (isset($f['value'])) {
                $values[] = $f['value'];
              }
            }
            $values = $this->flattenArray($values);

            $this->reductionType = $field['type'];
            $item->$name = array_reduce($values, array($this, 'reduce'), NULL);
            if ($field['type'] == 'count' && !$item->$name) {
              $item->$name = 0;
            }
          }
        }
      }
    }
  }

  /**
   * Helper method for reducing an array to a single value.
   */
  public function reduce($a, $b) {
    switch ($this->reductionType) {
      case 'fulltext':
        return isset($a) ? $a . "\n\n" . $b : $b;
      case 'sum':
        return $a + $b;
      case 'count':
        return $a + 1;
      case 'max':
        return isset($a) ? max($a, $b) : $b;
      case 'min':
        return isset($a) ? min($a, $b) : $b;
      case 'first':
        return isset($a) ? $a : $b;
    }
  }

  /**
   * Helper method for flattening a multi-dimensional array.
   */
  protected function flattenArray(array $data) {
    $ret = array();
    foreach ($data as $item) {
      if (!isset($item)) {
        continue;
      }
      if (is_scalar($item)) {
        $ret[] = $item;
      }
      else {
        $ret = array_merge($ret, $this->flattenArray($item));
      }
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    $types = $this->getTypes('type');
    if (isset($this->configuration['fields'])) {
      foreach ($this->configuration['fields'] as $name => $field) {
        $definition = array(
          'label' => $field['name'],
          'description' => empty($field['description']) ? '' : $field['description'],
          'type' => $types[$field['type']],
        );
        $properties[$name] = new DataDefinition($definition);
      }
    }
  }

  /**
   * Helper method for creating a field description.
   */
  protected function fieldDescription(array $field, array $index_fields) {
    $fields = array();
    foreach ($field['fields'] as $f) {
      $fields[] = isset($index_fields[$f]) ? $index_fields[$f]['name'] : $f;
    }
    $type = $this->getTypes();
    $type = $type[$field['type']];
    return t('A @type aggregation of the following fields: @fields.', array('@type' => $type, '@fields' => implode(', ', $fields)));
  }

  /**
   * Helper method for getting information about available aggregation types.
   *
   * @param string $info
   *   (optional) One of "name", "type" or "description", to indicate what
   *   values should be returned for the types. Defaults to "name".
   *
   * @return array
   *   An array of the identifiers of the available types mapped to, depending
   *   on $info, their names, their data types or their descriptions.
   */
  protected function getTypes($info = 'name') {
    switch ($info) {
      case 'name':
        return array(
          'fulltext' => t('Fulltext'),
          'sum' => t('Sum'),
          'count' => t('Count'),
          'max' => t('Maximum'),
          'min' => t('Minimum'),
          'first' => t('First'),
        );
      case 'type':
        return array(
          'fulltext' => 'string',
          'sum' => 'integer',
          'count' => 'integer',
          'max' => 'integer',
          'min' => 'integer',
          'first' => 'string',
        );
      case 'description':
        return array(
          'fulltext' => t('The Fulltext aggregation concatenates the text data of all contained fields.'),
          'sum' => t('The Sum aggregation adds the values of all contained fields numerically.'),
          'count' => t('The Count aggregation takes the total number of contained field values as the aggregated field value.'),
          'max' => t('The Maximum aggregation computes the numerically largest contained field value.'),
          'min' => t('The Minimum aggregation computes the numerically smallest contained field value.'),
          'first' => t('The First aggregation will simply keep the first encountered field value. This is helpful foremost when you know that a list field will only have a single value.'),
        );
    }
    return array();
  }

  /**
   * Submit helper callback for buttons in the callback's configuration form.
   */
  public function formButtonSubmit(array $form, array &$form_state) {
    $button_name = $form_state['triggering_element']['#name'];
    if ($button_name == 'op') {
      for ($i = 1; isset($this->configuration['fields']['search_api_aggregation_' . $i]); ++$i) {
      }
      $this->configuration['fields']['search_api_aggregation_' . $i] = array(
        'name' => '',
        'type' => 'fulltext',
        'fields' => array(),
      );
    }
    else {
      $field = substr($button_name, 34);
      unset($this->configuration['fields'][$field]);
    }
    $form_state['rebuild'] = TRUE;
    $this->changes = TRUE;
  }

}

/**
 * Submit function for buttons in the callback's configuration form.
 */
function _search_api_add_aggregation_field_submit(array $form, array &$form_state) {
  $form_state['callbacks']['search_api_alter_add_aggregation']->formButtonSubmit($form, $form_state);
}

/**
 * AJAX submit function for buttons in the callback's configuration form.
 */
function _search_api_add_aggregation_field_ajax(array $form, array &$form_state) {
  return $form['callbacks']['settings']['search_api_alter_add_aggregation']['fields'];
}
