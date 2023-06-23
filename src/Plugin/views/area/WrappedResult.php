<?php

namespace Drupal\kumquat_core\Plugin\views\area;

use Drupal\views\Plugin\views\area\Result;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("kumquat_result")
 */
class WrappedResult extends Result {

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $build = parent::render($empty);
    if (empty($build)) {
      return [];
    }

    $build['#theme_wrappers'] = [
      'container__views_results_summary__' . $this->view->id() . '__' . $this->view->current_display . '__' . $this->options['id']  => [
        '#attributes' => ['class' => ['filters__result']],
      ],
    ];
    return $build;
  }

}
