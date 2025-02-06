<?php

namespace Drupal\ccu_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link_title_only' formatter.
 *
 * @FieldFormatter(
 *   id = "link_title_only",
 *   label = @Translation("Link title only"),
 *   field_types = {
 *     "link",
 *   },
 * )
 */
class LinkTitleOnlyFormatter extends FormatterBase {

  /**
   * The token utility service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->token = $container->get('token');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();

    $elements = [];
    foreach ($items as $delta => $item) {
      if (!empty($item->title)) {
        $elements[$delta] = [
          '#markup' => $this->token->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]),
        ];
      }
    }
    return $elements;
  }

}
