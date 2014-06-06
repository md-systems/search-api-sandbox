<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\SearchApi\Processor\Stopwords.
 */

namespace Drupal\search_api\Plugin\SearchApi\Processor;

use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;

/**
 * @SearchApiProcessor(
 *   id = "search_api_stopwords_processor",
 *   label = @Translation("Stop words processor"),
 *   description = @Translation("Words to be filtered out before indexing")
 * )
 */
class Stopwords extends FieldsProcessorPluginBase {

  /**
   * Holds all words ignored for the last query.
   *
   * @var array
   */
  protected $ignored = array();

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'stopwords' => array(
        "a",
        "an",
        "and",
        "are",
        "as",
        "at",
        "be",
        "but",
        "by",
        "for",
        "if",
        "in",
        "into",
        "is",
        "it",
        "no",
        "not",
        "of",
        "on",
        "or",
        "s",
        "such",
        "t",
        "that",
        "the",
        "their",
        "then",
        "there",
        "these",
        "they",
        "this",
        "to",
        "was",
        "will",
        "with",
      ),
      'file' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = array();

    $form['help'] = array(
      '#markup' => '<p>' . $this->t('Provide a stopwords file or enter the words in this form. If you do both, both will be used. Read about <a href="!stopwords">stopwords</a>.', array('!stopwords' => 'https://en.wikipedia.org/wiki/Stop_words')) . '</p>'
    );

    // Only include full text fields. Important as only those can be tokenized.
    $fields = $this->index->getFields();
    $field_options = array();
    $default_fields = array();
    if (isset($this->configuration['fields'])) {
      $default_fields = array_keys($this->configuration['fields']);
      $default_fields = array_combine($default_fields, $default_fields);
    }

    foreach ($fields as $name => $field) {
      if ($field['type'] == 'text') {
        if ($this->testType($field['type'])) {
          $field_options[$name] = $field['name_prefix'] . $field['name'];
          if (!isset($this->configuration['fields']) && $this->testField($name, $field)) {
            $default_fields[$name] = $name;
          }
        }
      }
    }

    $form['fields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Enable this processor on the following fields'),
      '#options' => $field_options,
      '#default_value' => $default_fields,
    );

    $form['file'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Stopwords file'),
      '#description' => $this->t('This must be a stream-type description like <code>public://stopwords/stopwords.txt</code> or <code>http://example.com/stopwords.txt</code> or <code>private://stopwords.txt</code>.'),
    );
    $form['stopwords'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Stopwords'),
      '#description' => $this->t('Enter linebreak separated list of stopwords that will be removed from content before it is indexed and from search terms before searching.'),
      '#default_value' => implode(PHP_EOL, $this->configuration['stopwords']),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $uri = $form_state['values']['file'];
    if (!empty($uri) && !@file_get_contents($uri)) {
      $el = $form['file'];
      \Drupal::formBuilder()->setError($el, $form_state, $this->t('Stopwords file') . ': ' . $this->t('The file %uri is not readable or does not exist.', array('%uri' => $uri)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Convert our text input to an array
    $this->configuration['stopwords'] = explode(PHP_EOL, $form_state['values']['stopwords']);
  }

  /**
   * {@inheritdoc}
   */
  public function process(&$value) {
    $stopwords = $this->getStopWords();
    if (empty($stopwords) || !is_string($value)) {
      return;
    }
    $stopwords_preg_replace = implode('|', $stopwords);
    $value = preg_replace('@('. $stopwords_preg_replace .')@siU', '', $value);
    // Remove extra spaces.
    $value = preg_replace('/\s+/s', ' ', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $this->ignored = array();
    parent::preprocessSearchQuery($query);
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(array &$response, QueryInterface $query) {
    if ($this->ignored) {
      if (isset($response['ignored'])) {
        $response['ignored'] = array_merge($response['ignored'], $this->ignored);
      }
      else {
        $response['ignored'] = $this->ignored;
      }
    }
  }

  /**
   * Gets all the stopwords.
   *
   * @return
   *   An array whose keys are the stopwords set in either the file or the text
   *   field.
   */
  protected function getStopWords() {
    if (isset($this->stopwords)) {
      return $this->stopwords;
    }
    $file_words = $form_words = array();
    if (!empty($this->configuration['file']) && $stopwords_file = file_get_contents($this->configuration['file'])) {
      $file_words = preg_split('/\s+/', $stopwords_file);
    }
    if (!empty($this->configuration['stopwords'])) {
      $form_words = $this->configuration['stopwords'];
    }
    $this->stopwords = array_merge($file_words, $form_words);
    return $this->stopwords;
  }
}
