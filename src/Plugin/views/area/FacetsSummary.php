<?php

namespace Drupal\kumquat_core\Plugin\views\area;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display active facets.
 *
 * @ViewsArea("kumquat_facets_summary")
 */
class FacetsSummary extends Facets {

  /**
   * The facets helper service.
   *
   * @var \Drupal\kumquat_core\Service\FacetsHelper
   */
  protected $facetsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->facetsHelper = $container->get('kumquat_core.facets_helper');
    return $instance;
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

    // Fill facets with their results.
    $this->facetManager->updateResults($this->options['facets_source_id']);
    // Convert facets settings to facet objects from the facet manager service
    // because those have been updated with the results.
    foreach ($this->facetManager->getFacetsByFacetSourceId($this->options['facets_source_id']) as $facet) {
      if (!empty($facets[$facet->id()])) {
        $facets[$facet->id()] = $facet;
      }
    }
    /** @var \Drupal\facets\Entity\Facet[] $facets */

    $links = [];
    foreach ($facets as $facet) {
      // Do not build the facet in summary if facet has no active item.
      if (!$facet->getActiveItems()) {
        continue;
      }

      $links = array_merge($links, $this->facetsHelper->generateDefaultFacetLinks($facet));
    }
    // @Todo is the fulltext url key configurable in the handler options?
    // Add a link to remove the fulltext search if necessary.
    $links = array_merge($links, $this->facetsHelper->generateFulltextLinks('content'));

    // Add the main title and the wrapper if the summary is not empty.
    $build = [];
    if (!empty($links)) {
      $build['#type'] = 'container';
      $build['#theme_wrappers'] = ['container__views_facets_summary__' . $this->view->id() . '__' . $this->view->current_display . '__' . $this->options['id']];
      $build['#attributes'] = ['class' => ['filters__summary']];
      $build['title'] = [
        '#weight' => -100,
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Active filters'),
        '#attributes' => ['class' => 'visually-hidden'],
        '#access' => !empty($this->options['display_title']),
      ];
      $build['links'] = [
        '#theme' => 'links__facets_summary',
        '#links' => $links,
      ];
    }

    return $build;
  }

}
