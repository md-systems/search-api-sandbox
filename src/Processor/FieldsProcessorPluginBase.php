<?php

/**
 * @file
 * Contains Drupal\search_api\Processor\FieldsProcessorPluginBase.
 */

namespace Drupal\search_api\Processor;

use Drupal\Core\Render\Element;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Query\FilterInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Base class for processors that work on individual fields.
 *
 * A form element to select the fields to run on is provided, as well as easily
 * overridable methods to provide the actual functionality. Subclasses can
 * override any of these methods (or the interface methods themselves, of
 * course) to provide their specific functionality:
 * - processField()
 * - processFieldValue()
 * - processKeys()
 * - processKey()
 * - processFilters()
 * - processFilterValue()
 * - process()
 *
 * The following methods can be used for specific logic regarding the fields to
 * run on:
 * - testField()
 * - testType()
 */
abstract class FieldsProcessorPluginBase extends ProcessorPluginBase {

  /**
   * Overrides \Drupal\search_api\Plugin\ConfigurablePluginBase::buildConfigurationForm().
   *
   * Adds a "fields" checkboxes form element for selecting which fields the
   * processor should run on.
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $fields = $this->index->getFields();
    $field_options = array();
    $default_fields = array();
    if (isset($this->configuration['fields'])) {
      $default_fields = array_filter($this->configuration['fields']);
    }
    foreach ($fields as $name => $field) {
      if ($this->testType($field->getType())) {
        $field_options[$name] = $field->getPrefixedLabel();
        if (!isset($this->configuration['fields']) && $this->testField($name, $field)) {
          $default_fields[$name] = $name;
        }
      }
    }

    $form['fields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Enable this processor on the following fields'),
      '#options' => $field_options,
      '#default_value' => $default_fields,
    );

    return $form;
  }

  /**
   * Overrides \Drupal\search_api\Plugin\ConfigurablePluginBase::validateConfigurationForm().
   *
   * Validates the "fields" form element, filtering out the unset checkboxes.
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $fields = array_filter($form_state['values']['fields']);
    if ($fields) {
      $fields = array_fill_keys($fields, TRUE);
    }
    $form_state['values']['fields'] = $fields;
  }

  /**
   * Overrides \Drupal\search_api\Plugin\ConfigurablePluginBase::preprocessIndexItems().
   *
   * Calls processField() for all fields for which testField() returns TRUE.
   *
   * @param array $items
   *   An array of items to be preprocessed for indexing, formatted as specified
   *   by \Drupal\search_api\Backend\BackendSpecificInterface::indexItems().
   */
  public function preprocessIndexItems(array &$items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      /** @var \Drupal\search_api\Item\FieldInterface $field */
      foreach ($item as $name => $field) {
        if ($this->testField($name, $field)) {
          $this->processField($field);
        }
      }
    }
  }

  /**
   * Overrides \Drupal\search_api\Plugin\ConfigurablePluginBase::preprocessSearchQuery().
   *
   * Calls processKeys() for the keys and processFilters() for the filters.
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $keys = &$query->getKeys();
    if (isset($keys)) {
      $this->processKeys($keys);
    }
    $filter = $query->getFilter();
    $filters = &$filter->getFilters();
    $this->processFilters($filters);
  }

  /**
   * Overrides \Drupal\search_api\Processor\ProcessorPluginBase::postprocessSearchResults().
   *
   * Does nothing by default.
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    return;
  }

  /**
   * Processes a single field's value.
   *
   * Calls process() either for the whole text, or each token, depending on the
   * type. Also takes care of extracting list values and of fusing returned
   * tokens back into a one-dimensional array.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field to process.
   */
  protected function processField(FieldInterface $field) {
    $values = $field->getValues();
    $type = $field->getType();

    foreach ($values as $i => &$value) {
      if ($type == 'tokenized_text') {
        foreach ($value as &$tokenized_value) {
          $this->processFieldValue($tokenized_value['value'], $type);
        }
      }
      else {
        $this->processFieldValue($value, $type);
      }

      if ($type == 'tokenized_text') {
        $value = $this->normalizeTokens($value);
      }
      else {
        if (is_array($value)) {
          $value = $this->implodeTokens($value);
        }
        if (!$value && !is_numeric($value)) {
          unset($values[$i]);
        }
      }
    }

    // We're setting the type and values here as it could have changed.
    $field->setType($type);
    $field->setValues($values);
  }

  /**
   * Normalizes an internal array of tokens, which might be nested.
   *
   * @param array $tokens
   *   An array of tokens, possibly nested.
   * @param int $score
   *   The score to use as a multiplier for all of the tokens contained in this
   *   array of tokens.
   *
   * @return array
   *   A normalized tokens array, without any nested tokens arrays.
   */
  protected function normalizeTokens(array $tokens, $score = 1) {
    $ret = array();
    foreach ($tokens as $token) {
      if (empty($token['value']) && !is_numeric($token['value'])) {
        // Filter out empty tokens.
        continue;
      }
      if (!isset($token['score'])) {
        $token['score'] = $score;
      }
      else {
        $token['score'] *= $score;
      }
      if (is_array($token['value'])) {
        foreach ($this->normalizeTokens($token['value'], $token['score']) as $t) {
          $ret[] = $t;
        }
      }
      else {
        $ret[] = $token;
      }
    }
    return $ret;
  }

  /**
   * Implodes an array of tokens into a single string.
   *
   * @param array $tokens
   *   The tokens array to implode.
   *
   * @return string
   *   The text data from the tokens concatenated into a single string.
   */
  protected function implodeTokens(array $tokens) {
    $ret = array();
    foreach ($tokens as $token) {
      if (empty($token['value']) && !is_numeric($token['value'])) {
        // Filter out empty tokens.
        continue;
      }
      if (is_array($token['value'])) {
        $ret[] = $this->implodeTokens($token['value']);
      }
      else {
        $ret[] = $token['value'];
      }
    }
    return implode(' ', $ret);
  }

  /**
   * Preprocesses the search keywords.
   *
   * Calls processKey() for individual strings.
   *
   * @param array|string $keys
   *   Either a parsed keys array, or a single keywords string.
   */
  protected function processKeys(&$keys) {
    if (is_array($keys)) {
      foreach ($keys as $key => &$v) {
        if (Element::child($key)) {
          $this->processKeys($v);
          if (!$v && !is_numeric($v)) {
            unset($keys[$key]);
          }
        }
      }
    }
    else {
      $this->processKey($keys);
    }
  }

  /**
   * Preprocesses the query filters.
   *
   * @param array $filters
   *   An array of filters, passed by reference. The contents follow the format
   *   of \Drupal\search_api\Query\FilterInterface::getFilters().
   */
  protected function processFilters(array &$filters) {
    $fields = $this->index->getFields();
    foreach ($filters as $key => &$f) {
      if (is_array($f)) {
        if (isset($fields[$f[0]]) && $this->testField($f[0], $fields[$f[0]])) {
          // We want to allow processors also to easily remove complete filters.
          // However, we can't use empty() or the like, as that would sort out
          // filters for 0 or NULL. So we specifically check only for the empty
          // string, and we also make sure the filter value was actually changed
          // by storing whether it was empty before.
          $empty_string = $f[1] === '';
          $this->processFilterValue($f[1]);

          if ($f[1] === '' && !$empty_string) {
            unset($filters[$key]);
          }
        }
      }
      elseif ($f instanceof FilterInterface) {
        $child_filters = & $f->getFilters();
        $this->processFilters($child_filters);
      }
    }
  }

  /**
   * Tests whether a certain field should be processed.
   *
   * @param string $name
   *   The field's machine name.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field's information.
   *
   * @return bool
   *   TRUE if the field should be processed, FALSE otherwise.
   */
  protected function testField($name, FieldInterface $field) {
    if (!isset($this->configuration['fields'])) {
      return $this->testType($field->getType());
    }
    return !empty($this->configuration['fields'][$name]);
  }

  /**
   * Determines whether a field of a certain type should be preprocessed.
   *
   * The default implementation returns TRUE for "text", "tokens" and "string".
   *
   * @param string $type
   *   The type of the field (either when preprocessing the field at index time,
   *   or a filter on the field at query time).
   *
   * @return bool
   *   TRUE if fields of that type should be processed, FALSE otherwise.
   */
  protected function testType($type) {
    return Utility::isTextType($type, array('text', 'tokenized_text', 'string'));
  }

  /**
   * Processes a single text element in a field.
   *
   * The default implementation just calls process().
   *
   * @param string $value
   *   The string value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Can either be left a string, or
   *   changed into an array of tokens. A token is an associative array
   *   containing:
   *   - value: Either the text inside the token, or a nested array of tokens.
   *     The score of nested tokens will be multiplied by their parent's score.
   *   - score: The relative importance of the token, as a float, with 1 being
   *     the default.
   * @param string $type
   *   The field type as a reference. Can be manipulated
   *   directly, nothing has to be returned. Can contain any of the supported
   *   item types such as string, text. The processFieldValue is responsible
   *   of changing the type. Common example is changing text to tokenized_text.
   */
  protected function processFieldValue(&$value, &$type) {
    $this->process($value);
  }

  /**
   * Processes a single search keyword.
   *
   * The default implementation just calls process().
   *
   * @param string $value
   *   The string value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Can either be left a string, or be
   *   changed into a nested keys array, as defined by
   *   \Drupal\search_api\Query\QueryInterface::getKeys().
   */
  protected function processKey(&$value) {
    $this->process($value);
  }

  /**
   * Processes a single filter value.
   *
   * Called for processing a single filter value. The default implementation
   * just calls process().
   *
   * @param string $value
   *   The string value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Has to remain a string.
   */
  protected function processFilterValue(&$value) {
    $this->process($value);
  }

  /**
   * Processes a single string value.
   *
   * This method is ultimately called for all text by the standard
   * implementation, and does nothing by default.
   *
   * @param string $value
   *   The string value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Since this can be called for all
   *   value types, $value has to remain a string.
   */
  protected function process(&$value) {}

}
