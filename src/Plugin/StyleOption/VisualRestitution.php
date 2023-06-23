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
 *   id = "visual_restitution",
 *   label = @Translation("Visual restitution")
 * )
 */
class VisualRestitution extends StyleOptionPluginBase {

  use StyleOptionStyleTrait;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state): array {

    $options = $this->getConfiguration()['options'];
    array_walk($options, function (&$option, $machine_name) {
      if (!empty($option['image'])) {
        $plugin_id = $this->getPluginDefinition()['provider'];
        $module_path = \Drupal::service('extension.list.module')->getPath($plugin_id);
        if (!empty($module_path)) {
          $base_dir = $module_path . '/images/visual_restitution';
          $img_path = '/' . $base_dir . '/' . $option['image'];
          $option = $option['label'] . '<br /><img src="' . $img_path . '" alt="perdu" />';
        }
      }
      else {
        $option = $option['label'];
      }
    });
    $form['visual_restitution'] = [
      '#type' => 'radios',
      '#title' => $this->getLabel(),
      '#default_value' => $this->getValue('visual_restitution') ?? $this->getDefaultValue(),
      '#wrapper_attributes' => [
        'class' => [
          'wrapper-visual-restitution',
          $this->getConfiguration()['visual_restitution'] ?? '',
        ],
      ],
      '#description' => $this->getConfiguration('description'),
      '#options' => $options,
      '#attached' => [
        'library' => [
          'kumquat_core/style_options.visual_restitution',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $build) {
    $value = $this->getValue('visual_restitution') ?? NULL;
    $class = $value ?? NULL;
    if (!empty($class)) {
      $build['#attributes']['class'][] = $class;
    }
    return $build;
  }

}
