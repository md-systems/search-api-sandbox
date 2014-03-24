<?php
/**
 * @file
 * Contains \Drupal\search_api\Annotation\Processor.
 */

namespace Drupal\search_api\Annotation;

/*
 * Include required classes and interfaces.
 */
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Search API processor annotation object.
 *
 * @Annotation
 */
class Processor extends Plugin {

  /**
   * The processor plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the processor plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the processor.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The weight of the processor.
   *
   * @var int|NULL
   */
  public $weight = NULL;

}
