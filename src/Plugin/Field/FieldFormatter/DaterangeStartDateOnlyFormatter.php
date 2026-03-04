<?php

namespace Drupal\kumquat_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;

/**
 * Plugin implementation of the "Start date only" formatter.
 */
#[FieldFormatter(
  id: 'daterange_start_date_only',
  label: new TranslatableMarkup('Start date only'),
  field_types: [
    'daterange',
  ],
)]
class DaterangeStartDateOnlyFormatter extends DateTimeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    foreach ($items as $item) {
      $item->date = $item->start_date;
    }

    return parent::viewElements($items, $langcode);
  }

}
