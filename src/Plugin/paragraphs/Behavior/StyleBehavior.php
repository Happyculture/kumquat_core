<?php

namespace Drupal\kumquat_core\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Behavior to allow editors to choose the styles to apply to their paragraphs.
 *
 * @ParagraphsBehavior(
 *   id = "kumquat_style",
 *   label = @Translation("Style"),
 *   description = @Translation("Allow editors to choose the style(s) to apply to their paragraphs."),
 * )
 */
class StyleBehavior extends ParagraphsBehaviorBase {

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The theme.manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->themeManager = $container->get('theme.manager');
    return $instance;
  }

  /**
   * List all available styles to be selected in the widget.
   *
   * @param string $paragraph_type
   *   The paragraph type on which the style might be applied.
   *
   * @return array
   *   Array of styles names keyed by ID.
   */
  protected function getAvailableStyles(string $paragraph_type) {
    $styles = [];

    $hooks = [
      'kumquat_style',
      $paragraph_type . '_kumquat_style',
    ];
    foreach ($hooks as $hook) {
      $this->moduleHandler->invokeAllWith(
        $hook,
        function (callable $hook) use (&$styles, $paragraph_type) {
          $styles += $hook($paragraph_type);
        }
      );
    }
    $this->moduleHandler->alter($hooks, $styles, $paragraph_type);
    $this->themeManager->alter($hooks, $styles, $paragraph_type);

    if (empty($styles)) {
      return ['default' => $this->t('Default')];
    }

    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => $this->getAvailableStyles($paragraph->bundle()),
      '#default_value' => $paragraph->getBehaviorSetting('kumquat_style', 'style'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $summary = parent::settingsSummary($paragraph);
    if ($style = $paragraph->getBehaviorSetting('kumquat_style', 'style')) {
      $summary[] = [
        'label' => $this->t('Style'),
        'value' => $this->getAvailableStyles($paragraph->bundle())[$style],
      ];
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    return [];
  }

}
