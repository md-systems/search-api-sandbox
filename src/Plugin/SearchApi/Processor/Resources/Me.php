<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\SearchApi\Processor\Resources\Me.
 */

namespace Drupal\search_api\Plugin\SearchApi\Processor\Resources;

class Me implements unicodeListInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0488}\x{0489}\x{20DD}\x{20DE}\x{20DF}\x{20E0}\x{20E2}' .
      '\x{20E3}\x{20E4}\x{A670}\x{A671}\x{A672}';
  }
}
