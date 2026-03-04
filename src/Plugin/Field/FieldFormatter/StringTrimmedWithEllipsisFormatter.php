<?php

namespace Drupal\kumquat_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'string_trimmed_with_ellipsis' formatter.
 */
#[FieldFormatter(
  id: 'string_trimmed_with_ellipsis',
  label: new TranslatableMarkup('Trimmed with ellipsis'),
  field_types: [
    'string',
    'string_long',
  ],
)]
class StringTrimmedWithEllipsisFormatter extends TextTrimmedWithEllipsisFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $render_as_summary = function (&$element) {
      // Make sure any default #pre_render callbacks are set on the element,
      // because text_pre_render_summary() must run last.
      $element += \Drupal::service('element_info')->getInfo($element['#type']);
      // Add the #pre_render callback that renders the text into a summary.
      $element['#pre_render'][] = [
        StringTrimmedWithEllipsisFormatter::class,
        'preRenderSummary',
      ];
      // Pass on the trim length to the #pre_render callback via a property.
      $element['#text_summary_trim_length'] = $this->getSetting('trim_length');
      $element['#text_summary_ellipsis'] = $this->getSetting('ellipsis');
    };

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
        '#format' => NULL,
      ];

      $render_as_summary($elements[$delta]);
    }

    return $elements;
  }

}
