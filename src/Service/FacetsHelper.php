<?php

namespace Drupal\kumquat_core\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Utility\FacetsUrlGenerator;
use Drupal\search_api\Plugin\search_api\display\ViewsDisplayBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helper service to manipulate facets.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FacetsHelper {
  use StringTranslationTrait;

  /**
   * The facet plugin manager service.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * The facet_source plugin manager service.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourceManager;

  /**
   * The facets URL generator utility service.
   *
   * @var \Drupal\facets\Utility\FacetsUrlGenerator
   */
  protected $facetsUrlGenerator;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Class constructor.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The facet plugin manager service.
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_manager
   *   The facet_source plugin manager service.
   * @param \Drupal\facets\Utility\FacetsUrlGenerator $facets_url_generator
   *   The facets URL generator utility service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(DefaultFacetManager $facet_manager, FacetSourcePluginManager $facet_source_manager, FacetsUrlGenerator $facets_url_generator, RequestStack $request_stack) {
    $this->facetManager = $facet_manager;
    $this->facetSourceManager = $facet_source_manager;
    $this->facetsUrlGenerator = $facets_url_generator;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * Generate the links to remove the facet.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet we want the links for.
   *
   * @return array
   *   The array of links.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  public function generateDefaultFacetLinks(FacetInterface $facet): array {
    $links = [];

    // Build the facet so the results are fully filled.
    $this->facetManager->build($facet);

    foreach ($facet->getResults() as $result) {
      if ($result->isActive()) {
        $links[] = [
          'url' => $result->getUrl(),
          'title' => $result->getDisplayValue(),
          'attributes' => [
            'title' => $this->t('Remove the "@label" filter', ['@label' => $result->getDisplayValue()]),
          ],
        ];
      }
    }

    return $links;
  }

  /**
   * Generates the link to remove the fulltext attribute from URL.
   *
   * @param string $fulltext_url_key
   *   URL argument to extract the fulltext search content from.
   * @param string $facet_filter_url_key (optional)
   *   URL argument to extract the facets from.
   *   Defaults to 'f'.
   *
   * @return array
   */
  public function generateFulltextLinks(string $fulltext_url_key, string $facet_filter_url_key = 'f') : array {
    $links = [];
    $fulltext_content = $this->currentRequest->query->get($fulltext_url_key);
    if (!empty($fulltext_content)) {
      $url = Url::createFromRequest($this->currentRequest);
      $url->setRouteParameter($facet_filter_url_key, $this->currentRequest->query->get($facet_filter_url_key));
      $links[] = [
        'url' => $url,
        'title' => $fulltext_content,
        'attributes' => [
          'title' => $this->t('Remove the "@label" text filter', ['@label' => $fulltext_content]),
        ],
      ];
    }
    return $links;
  }

  /**
   * Get the facet_source id from a view.
   *
   * @param string $view_id
   *   The view id.
   * @param string $display_id
   *   The display id.
   *
   * @return string|null
   *   The facet_source id or empty if there is none.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSourceIdFromView(string $view_id, string $display_id) {
    foreach (array_keys($this->facetSourceManager->getDefinitions()) as $plugin_id) {
      $instance = $this->facetSourceManager->createInstance($plugin_id);
      $display = $instance->getDisplay();
      if ($display instanceof ViewsDisplayBase) {
        $definition = $display->getPluginDefinition();
        if ($definition['view_id'] === $view_id && $definition['view_display'] === $display_id) {
          return $plugin_id;
        }
      }
    }
  }

}
