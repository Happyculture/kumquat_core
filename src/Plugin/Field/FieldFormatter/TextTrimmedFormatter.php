<?php

namespace Drupal\kumquat_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter as TextTrimmedFormatterAliasCore;

/**
 * Override of the 'text_trimmed' formatter.
 */
class TextTrimmedFormatter extends TextTrimmedFormatterAliasCore {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'ellipsis' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['ellipsis'] = [
      '#title' => $this->t('Ellipsis'),
      '#type' => 'textfield',
      '#description' => $this->t('Define the ellipsis to be added if the string has been cut. Leave empty to add nothing.'),
      '#default_value' => $this->getSetting('ellipsis'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if (!empty($this->getSetting('ellipsis'))) {
      $summary[] = $this->t('Ellipsis: @ellipsis', ['@ellipsis' => $this->getSetting('ellipsis')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as &$element) {
      $element['#text_summary_ellipsis'] = $this->getSetting('ellipsis');

      foreach ($element['#pre_render'] as &$callback) {
        if ($this instanceof $callback[0]) {
          $callback[0] = static::class;
        }
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderSummary(array $element) {
    $original = $element['#markup'];
    $element = parent::preRenderSummary($element);
    if ($element['#markup'] !== $original) {
      $element['#markup'] .= $element['#text_summary_ellipsis'];
    }
    return $element;
  }

}
