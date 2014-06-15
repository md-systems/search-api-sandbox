<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\SearchApi\Processor\Resources\Pi.
 */

namespace Drupal\search_api\Plugin\SearchApi\Processor\Resources;

class Pi implements unicodeListInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{00AB}\x{2018}\x{201B}\x{201C}\x{201F}\x{2039}\x{2E02}' .
      '\x{2E04}\x{2E09}\x{2E0C}\x{2E1C}\x{2E20}';
  }
}
