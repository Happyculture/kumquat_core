<?php

namespace Drupal\kumquat_core\Helpers;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a static block which content is based on a template.
 */
abstract class StaticBlockBase extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => $this->getThemeName(),
    ];
  }

  /**
   * Gets the theme name on which the block is based.
   *
   * @return string
   *   The theme name as defined in a hook_theme() implementation.
   */
  abstract public function getThemeName();

}
