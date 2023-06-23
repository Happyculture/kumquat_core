<?php

namespace Drupal\kumquat_core\Plugin\Field\FieldFormatter;

use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Drupal\svg_image_responsive\Plugin\Field\FieldFormatter\SvgResponsiveImageFormatter;

if (class_exists('Drupal\svg_image_responsive\Plugin\Field\FieldFormatter\SvgResponsiveImageFormatter')) {
  /**
   * Conditional class extended by ConditionalResponsiveImageFormatter.
   */
  class ConditionalResponsiveImageFormatterProxy extends SvgResponsiveImageFormatter {}
}
else {
  /**
   * Conditional class extended by ConditionalResponsiveImageFormatter.
   */
  class ConditionalResponsiveImageFormatterProxy extends ResponsiveImageFormatter {}
}
