<?php

namespace Drupal\kumquat_core\Plugin\ExtraField;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;

/**
 * Base class for view based extra fields.
 */
abstract class ExtraFieldDisplayViewBase extends ExtraFieldDisplayFormattedBase {

  const VIEW_NAME = '';
  const VIEW_DISPLAY = 'default';

  /**
   * The view.
   *
   * @var \Drupal\views\Entity\View
   */
  protected View $view;

  /**
   * The executable instance of the view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected ViewExecutable $exec;

  /**
   * Return the machine name of the view to render.
   *
   * @return string
   *   view machine name.
   */
  protected function getViewName() {
    return static::VIEW_NAME;
  }

  /**
   * Return the machine name of the display of the view to render.
   *
   * @return string
   *   display machine name.
   */
  protected function getViewDisplay() {
    return static::VIEW_DISPLAY;
  }

  /**
   * Get arguments to pass to the view.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The current entity.
   *
   * @return array
   *   The arguments array.
   */
  protected function getViewArguments(ContentEntityInterface $entity) : array {
    return [$entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->view = View::load($this->getViewName());
    $this->exec = $this->view->getExecutable();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    // Set the current display and the argument.
    $this->exec->setDisplay($this->getViewDisplay());
    $this->exec->setArguments($this->getViewArguments($entity));

    // Force total_rows to be calculated.
    $this->exec->get_total_rows = TRUE;

    // Execute the view so the results are calculated.
    $this->exec->execute();

    // Set the isEmpty flag to prevent field label to be displayed if the view
    // is empty.
    $this->isEmpty = empty($this->exec->total_rows);

    // Return the render array.
    $build = $this->exec->buildRenderable();
    return $this->isEmpty ? ['#cache' => $build['#cache']] : $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->exec->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelDisplay() {
    $label = $this->getLabel();
    return !empty($label) ? 'above' : 'hidden';
  }

}
