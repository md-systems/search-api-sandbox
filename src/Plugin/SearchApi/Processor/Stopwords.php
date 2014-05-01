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
      'stopwords' => "but\ndid\nthe this that those\netc",
      'file' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['help'] = array(
      '#markup' => '<p>' . $this->t('Provide a stopwords file or enter the words in this form. If you do both, both will be used. Read about <a href="!stopwords">stopwords</a>.', array('!stopwords' => 'https://en.wikipedia.org/wiki/Stop_words')) . '</p>');
    $form['file'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Stopwords file'),
      '#description' => $this->t('This must be a stream-type description like <code>public://stopwords/stopwords.txt</code> or <code>http://example.com/stopwords.txt</code> or <code>private://stopwords.txt</code>.'),
    );
    $form['stopwords'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Stopwords'),
      '#description' => $this->t('Enter a space and/or linebreak separated list of stopwords that will be removed from content before it is indexed and from search terms before searching.'),
      '#default_value' => $this->configuration['stopwords'],
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
  public function process(&$value) {
    $stopwords = $this->getStopWords();
    if (empty($stopwords) || !is_string($value)) {
      return;
    }
    $words = preg_split('/\s+/', $value);
    foreach ($words as $sub_key => $sub_value) {
      if (isset($stopwords[$sub_value])) {
        unset($words[$sub_key]);
        $this->ignored[] = $sub_value;
      }
    }
    $value = implode(' ', $words);
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
      $form_words = preg_split('/\s+/', $this->configuration['stopwords']);
    }
    $this->stopwords = array_flip(array_merge($file_words, $form_words));
    return $this->stopwords;
  }
}
