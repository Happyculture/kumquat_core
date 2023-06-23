<?php

namespace Drupal\kumquat_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;

/**
 * Plugin implementation of the "End date only" formatter.
 *
 * @FieldFormatter(
 *   id = "daterange_end_date_only",
 *   label = @Translation("End date only"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DaterangeEndDateOnlyFormatter extends DateTimeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    foreach ($items as $item) {
      $item->date = $item->end_date;
    }

    return parent::viewElements($items, $langcode);
  }

}
