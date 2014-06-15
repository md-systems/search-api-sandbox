<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\SearchApi\Processor\Stopwords.
 */

namespace Drupal\search_api\Plugin\SearchApi\Processor;

use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Utility\Utility;

/**
 * @SearchApiProcessor(
 *   id = "stopwords",
 *   label = @Translation("Stopwords"),
 *   description = @Translation("Allows you to define stopwords which will be ignored in searches. <strong>Caution:</strong> Only use after both 'Ignore case' and 'Tokenizer' have run.")
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    unset($this->stopwords);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['stopwords'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Stopwords'),
      '#description' => $this->t('Enter a list of stopwords, each on a separate line, that will be removed from content before it is indexed and from search terms before searching. <a href="@url">More info about stopwords.</a>.', array('@url' => 'https://en.wikipedia.org/wiki/Stop_words')),
      '#default_value' => implode("\n", $this->configuration['stopwords']),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    // Convert our text input to an array.
    $form_state['values']['stopwords'] = explode("\n", $form_state['values']['stopwords']);
    $this->setConfiguration($form_state['values']);
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
  public function postprocessSearchResults(ResultSetInterface $results) {
    foreach ($this->ignored as $ignored_search_key) {
      $results->addIgnoredSearchKey($ignored_search_key);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function testType($type) {
    return Utility::isTextType($type, array('text', 'tokenized_text'));
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    $stopwords = $this->getStopWords();
    if (empty($stopwords) || !is_string($value)) {
      return;
    }
    $value = trim($value);
    if (isset($stopwords[$value])) {
      $this->ignored[$value] = $value;
      $value = '';
    }
  }

  /**
   * Gets all the stopwords.
   *
   * @return array
   *   An array whose keys and values are the stopwords set for this processor.
   */
  protected function getStopWords() {
    if (!isset($this->stopwords)) {
      $this->stopwords = array_combine($this->configuration['stopwords'], $this->configuration['stopwords']);
    }
    return $this->stopwords;
  }

}
