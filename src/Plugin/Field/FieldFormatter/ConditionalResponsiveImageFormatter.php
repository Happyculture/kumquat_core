<?php

namespace Drupal\kumquat_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'conditional_responsive_image' formatter.
 *
 * Allow to choose the responsive formatter to use based on conditions.
 *
 * @FieldFormatter(
 *   id = "conditional_responsive_image",
 *   label = @Translation("Conditional responsive image"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class ConditionalResponsiveImageFormatter extends ConditionalResponsiveImageFormatterProxy {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'conditions' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['responsive_image_style']['#title'] = $this->t('Responsive image style fallback');
    $elements['responsive_image_style']['#description'] = [
      'usage' => ['#markup' => $this->t('Image style used if no condition succeeds.') . '<br />'],
      'parent' => $elements['responsive_image_style']['#description'],
    ];

    $elements['conditions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Conditions'),
      '#options' => $this->getConditionsOptions(),
      '#default_value' => $this->getSetting('conditions'),
      '#weight' => -10,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    foreach ($summary as $index => $message) {
      if (str_starts_with($message->getUntranslatedString(), 'Responsive image style:')) {
        $summary[$index] = $this->t('Responsive image style fallback: @responsive_image_style', $message->getArguments());
        break;
      }
    }

    $enabled_conditions = array_filter($this->getSetting('conditions'));
    if (!empty($enabled_conditions)) {
      array_walk($enabled_conditions, function (&$condition) {
        $condition = $this->getConditionsOptions()[$condition] ?? $condition;
      });
      $enabled_conditions = implode(', ', $enabled_conditions);
    }
    else {
      $enabled_conditions = $this->t('None');
    }
    $conditions_summary = $this->t('Conditions: @list', [
      '@list' => $enabled_conditions,
    ]);
    array_unshift($summary, $conditions_summary);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entityStorage = \Drupal::entityTypeManager()
      ->getStorage('entity_view_mode');
    if ($entityStorage->load($items->getEntity()->getEntityTypeId() . '.' . $this->viewMode)) {
      $this->setSetting('responsive_image_style', $this->viewMode);
    }
    return parent::viewElements($items, $langcode);
  }

  /**
   * Get the condition options.
   *
   * @return array
   *   The available options.
   */
  protected function getConditionsOptions(): array {
    return [
      'match_view_mode' => $this->t('Match view mode ID'),
    ];
  }

}
