<?php

namespace Drupal\kumquat_core\Plugin\views\area;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\search_api\Display\DisplayPluginManager;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display selected facets.
 *
 * @ViewsArea("kumquat_facets")
 */
class Facets extends AreaPluginBase {

  /**
   * The facets manager service.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * The facets_facet entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * The search_api display plugin manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManager
   */
  protected $sapiDisplayManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->facetManager = $container->get('facets.manager');
    $instance->facetStorage = $container->get('entity_type.manager')->getStorage('facets_facet');
    $instance->sapiDisplayManager = $container->get('plugin.manager.search_api.display');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['facets_source_id'] = ['default' => ''];
    $options['facets'] = ['default' => []];
    $options['display_title'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $source_id = \Drupal::service('kumquat_core.facets_helper')->getSourceIdFromView($this->view->id(), $this->view->current_display);
    $this->options['facets_source_id'] = $source_id;
    if (empty($source_id)) {
      foreach ($this->sapiDisplayManager->getDefinitions() as $display_id => $definition) {
        if ($definition['view_id'] === $this->view->id() && $definition['view_display'] === $this->view->current_display) {
          $source_id = $definition['provider'] . ':' . str_replace(':', '__', $display_id);
          break;
        }
      }
    }
    // We should have an appropriate source_id now.
    if (empty($source_id)) {
      return;
    }

    $form['facets_source_id'] = [
      '#type' => 'hidden',
      '#value' => $source_id,
    ];

    $form['facets'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Facets'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
    ];

    // Get the facets having this display as source.
    /** @var \Drupal\facets\Entity\Facet[] $facets */
    $facets = $this->facetStorage->loadbyProperties(['facet_source_id' => $source_id]);
    foreach ($facets as $facet_id => $facet) {
      $form['facets'][$facet_id] = [
        '#weight' => !empty($this->options['facets'][$facet_id]['weight']) ? $this->options['facets'][$facet_id]['weight'] : 0,
        '#attributes' => ['class' => ['draggable']],
      ];
      $form['facets'][$facet_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $facet->label(),
        '#default_value' => !empty($this->options['facets'][$facet_id]['enabled']),
      ];
      $form['facets'][$facet_id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $facet->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $form['facets'][$facet_id]['#weight'],
        '#attributes' => ['class' => ['weight']],
      ];
    }
    uasort($form['facets'], [
      '\Drupal\Component\Utility\SortArray',
      'sortByWeightProperty',
    ]);

    $form['display_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => $this->options['display_title'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if ($empty && empty($this->options['empty'])) {
      return [];
    }

    // Filter and sort enabled facets.
    $facets = $this->options['facets'];
    $facets = array_filter($facets, function ($item) {
      return !empty($item['enabled']);
    });
    uasort($facets, [
      '\Drupal\Component\Utility\SortArray',
      'sortByWeightProperty',
    ]);

    $build = [];
    foreach ($this->facetStorage->loadMultiple(array_keys($facets)) as $facet_id => $facet) {
      /** @var \Drupal\facets\Entity\Facet $facet */
      $build[$facet_id] = $this->buildFacet($facet);

      // Add the title if the facet is not empty.
      if (empty($this->facetManager->returnProcessedFacet($facet)->getResults())) {
        $empty_behavior = $facet->getEmptyBehavior();
        if (empty($empty_behavior) || $empty_behavior['behavior'] === 'none') {
          continue;
        }
      }

      $build[$facet_id]['title'] = [
        '#weight' => -100,
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => ['class' => 'off-canvas-aside__subtitle'],
        '#value' => $facet->label(),
        '#access' => !empty($this->options['display_title']),
      ];
    }

    // Add the main title and the wrapper if the facets are not empty.
    if (!empty($build)) {
      $build['#type'] = 'container';
      $build['#theme_wrappers'] = ['container__views_filters__' . $this->view->id() . '__' . $this->view->current_display . '__' . $this->options['id']];
      $build['#attributes'] = ['class' => ['views-filters']];
    }

    return $build;
  }

  /**
   * Build the render array of a facet.
   *
   * Stolen from \Drupal\facets\Plugin\Block\FacetBlock::build().
   *
   * @param \Drupal\facets\Entity\Facet $facet
   *   The facet to build.
   *
   * @return array
   *   The render array.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  protected function buildFacet(Facet $facet) {
    // Let the facet_manager build the facets.
    $build = $this->facetManager->build($facet);

    if (!empty($build)) {
      // Add extra elements from facet source, for example, ajax scripts.
      // @see Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay
      /** @var \Drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source */
      $facet_source = $facet->getFacetSource();
      $build += $facet_source->buildFacet();

      // Add contextual links only when we have results.
      $build['#contextual_links']['facets_facet'] = [
        'route_parameters' => ['facets_facet' => $facet->id()],
      ];

      if (!empty($build[0]['#attributes']['class']) && in_array('facet-active', $build[0]['#attributes']['class'], TRUE)) {
        $build['#attributes']['class'][] = 'facet-active';
      }
      else {
        $build['#attributes']['class'][] = 'facet-inactive';
      }

      // Add classes needed for ajax.
      if (!empty($build['#use_ajax'])) {
        $build['#attributes']['class'][] = 'block-facets-ajax';
        // The configuration block id isn't always set in the configuration.
        if (isset($this->configuration['block_id'])) {
          $build['#attributes']['class'][] = 'js-facet-block-id-' . $this->configuration['block_id'];
        }
        else {
          $build['#attributes']['class'][] = 'js-facet-block-id-' . $this->pluginId;
        }
      }
    }

    return $build;
  }

}
