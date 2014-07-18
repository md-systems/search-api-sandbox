<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\SearchApi\Processor\Transliteration.
 */

namespace Drupal\search_api\Plugin\SearchApi\Processor;

use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchApiProcessor(
 *   id = "transliteration",
 *   label = @Translation("Transliteration"),
 *   description = @Translation("Makes searches insensitive to accents and other non-ASCII characters.")
 * )
 */
class Transliteration extends FieldsProcessorPluginBase {

  /**
   * The transliteration service to use.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliterator = NULL;

  /**
  * @var string
  */
  protected $langcode = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\search_api\Plugin\SearchApi\Processor\Transliteration $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Component\Transliteration\TransliterationInterface $transliterator */
    $transliterator = $container->get('transliteration');
    $processor->setTransliterator($transliterator);
    /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
    $language_manager = $container->get('language_manager');
    $processor->setLangcode($language_manager->getDefaultLanguage()->getId());

    return $processor;
  }

  /**
   * Retrieves the transliterator.
   *
   * @return \Drupal\Component\Transliteration\TransliterationInterface
   *   The transliterator.
   */
  public function getTransliterator() {
    return $this->transliterator ?: \Drupal::service('transliteration');
  }

  /**
   * Sets the transliterator.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliterator
   *   The new transliterator.
   *
   * @return self
   *   The invoked object.
   */
  public function setTransliterator($transliterator) {
    $this->transliterator = $transliterator;
    return $this;
  }

  /**
   * Retrieves the langcode.
   *
   * @return string
   *   The langcode.
   */
  public function getLangcode() {
    return $this->langcode ?: \Drupal::languageManager()->getDefaultLanguage()->getId();
  }

  /**
   * Sets the langcode.
   *
   * @param string $langcode
   *   The new langcode.
   *
   * @return self
   *   The invoked object.
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // We don't touch integers, NULL values or the like.
    if (is_string($value)) {
      $value = $this->getTransliterator()->transliterate($value, $this->getLangcode());
    }
  }

}
