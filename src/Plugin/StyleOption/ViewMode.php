<?php

declare(strict_types=1);

namespace Drupal\kumquat_core\Plugin\StyleOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\style_options\Plugin\StyleOptionPluginBase;
use Drupal\style_options\StyleOptionStyleTrait;

/**
 * Define the class attribute option plugin from a visual representation.
 *
 * @StyleOption(
 *   id = "view_mode",
 *   label = @Translation("View mode")
 * )
 */
class ViewMode extends StyleOptionPluginBase {

  use StyleOptionStyleTrait;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state): array {

    $options = $this->getConfiguration()['options'];
    array_walk($options, function (&$option) {
        $option = $option['label'];
    });
    $form['view_mode'] = [
      '#type' => 'radios',
      '#title' => $this->getLabel(),
      '#default_value' => $this->getValue('view_mode') ?? $this->getDefaultValue(),
      '#wrapper_attributes' => [
        'class' => [
          'wrapper-view-mode',
          $this->getConfiguration()['view_mode'] ?? '',
        ],
      ],
      '#description' => $this->getConfiguration('description'),
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $build) {
    $value = $this->getValue('view_mode') ?? 'default';
    $option_definition = $this->getConfiguration()['options'];
    $target_entity_type = $this->getConfiguration()['target_entity_type'];
    $paragraph_field_name = $this->getConfiguration()['paragraph_field_name'];
    if (is_array($value)) {
      $view_mode = implode(' ',
        array_map(function ($index) use ($option_definition) {
          return $option_definition[$index]['view_mode'] ?? 'default';
        }, $value)
      );
    }
    else {
      $view_mode = $this->getConfiguration()['options'][$value]['view_mode'] ?? 'default';
    }
    if (!empty($view_mode) && !empty($target_entity_type) && !empty($paragraph_field_name)) {
      $viewer = \Drupal::entityTypeManager()->getViewBuilder($target_entity_type);
      $entity = $build['#paragraph']->$paragraph_field_name->entity;
      $build[$paragraph_field_name][0] = $viewer->view($entity, $view_mode);
    }
    return $build;
  }

}
