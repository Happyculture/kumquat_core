<?php

namespace Drupal\kumquat_core\Helpers;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\ui_patterns\UiPatterns;

/**
 * A helper including some trusted callbacks to pre_render patterns.
 */
class PatternsHelper implements TrustedCallbackInterface {

  /**
   * Clean patterns fields content from empty drupal fields.
   *
   * If they have the "optional" setting.
   *
   * @param array $element
   *   A render array element.
   *
   * @return array
   *   A render array element.
   */
  public static function handleOptionalFields(array $element) {
    $pattern_definition = UiPatterns::getPatternDefinition($element['#id']);
    foreach ($pattern_definition->getFields() as $field_name => $field) {
      if (!empty($field->toArray()['optional']) && !empty($element['#context'])) {

        if (isset($element['#' . $field_name]) && is_array($element['#' . $field_name]) && ($entity = $element['#context']->getProperty('entity'))) {
          $children = Element::children($element['#' . $field_name]);
          if (!empty($children)) {
            foreach ($children as $entity_field_name) {
              if ($entity->hasField($entity_field_name) && $entity->{$entity_field_name}->isEmpty()) {
                unset($element['#' . $field_name][$entity_field_name]);
              }
            }
          }
          elseif (!empty($element['#' . $field_name]['#sources'])) {
            foreach (array_keys($element['#' . $field_name]['#sources']) as $entity_field_name) {
              if ($entity->hasField($entity_field_name) && $entity->{$entity_field_name}->isEmpty()) {
                unset($element['#' . $field_name][$entity_field_name]);
              }
            }
          }
          elseif (empty($element['#' . $field_name]['#theme']) && empty($element['#' . $field_name]['#type'])) {
            unset($element['#' . $field_name]);
          }
        }
      }
    }

    return $element;
  }

  /**
   * Force patterns fields content to be usable in twig loops.
   *
   * If they have the "multiple" setting.
   *
   * @param array $element
   *   A render array element.
   *
   * @return array
   *   A render array element.
   */
  public static function handleMultipleFields(array $element) {
    $pattern_definition = UiPatterns::getPatternDefinition($element['#id']);
    foreach ($pattern_definition->getFields() as $field_name => $field) {
      if (!empty($field->toArray()['multiple'])) {
        if (!array_key_exists('#' . $field_name, $element)) {
          continue;
        }

        // Handle use case of multiple fields that get a single scalar value
        // instead of an array.
        if (is_scalar($element['#' . $field_name])) {
          $element['#' . $field_name] = [['#markup' => $element['#' . $field_name]]];
        }

        // Multiple fields in the same area in the UI creates a #sources key. We
        // recreate this key even for single sources to reduce the use cases to
        // manage.
        if (empty($element['#' . $field_name]['#sources'])) {
          $element['#' . $field_name]['#sources'] = $element['#' . $field_name];
        }

        // Get sources to work on.
        $element_sources = $element['#' . $field_name]['#sources'];

        // Drupal expects sources to be render arrays. Ensure we didn't give it
        // scalar values directly in the templates to prevent issues.
        foreach ($element_sources as $key => $value) {
          if (is_scalar($value)) {
            $element_sources[$key] = ['#markup' => $value];
          }
        }

        $values = [];

        $sourceKeys = Element::children($element_sources, TRUE);

        // For each element in the sources we need to extract final values.
        foreach ($sourceKeys as $sourceKey) {
          $source = $element_sources[$sourceKey];

          // The element is a multiple field.
          if (!empty($source['#is_multiple'])) {
            $deltas = Element::children($source, TRUE);

            // Extract common source field properties.
            $source_properties = array_diff_key($source, array_combine($deltas, $deltas));

            // Explode the field values in one field per value and keep
            // common properties in case they contain something important.
            foreach ($deltas as $delta) {
              $values[] = ($source_properties + [0 => $source[$delta]]);
            }
          }

          // The element is a view.
          elseif (!empty($source['#view']) && !empty($source['#rows'])) {
            foreach ($source['#rows'] as $delta => $row) {
              $values[] = $row;
            }
          }

          // The element is empty.
          elseif (array_key_exists('#cache', $source) && !array_key_exists('#type', $source) && !array_key_exists('#theme', $source)) {
            // Do nothing.
          }

          // Fallback for other cases (custom render array).
          else {
            $values[] = $source;
          }
        }

        $element['#' . $field_name] = $values;
      }
    }

    return $element;
  }

  /**
   * Compute boolean fields values so they can be used in twig conditions.
   *
   * @param array $element
   *   A render array element.
   *
   * @return array
   *   A render array element.
   */
  public static function handleBooleanFields(array $element) {
    $pattern_definition = UiPatterns::getPatternDefinition($element['#id']);
    foreach ($pattern_definition->getFields() as $field_name => $field) {
      if (!empty($field->getType() == 'boolean') && !empty($element['#' . $field_name])) {
        $value = TRUE;
        if (empty($element['#' . $field_name]['#sources'])) {
          $element['#' . $field_name]['#sources'] = $element['#' . $field_name];
        }
        foreach ($element['#' . $field_name]['#sources'] as $source) {
          if (array_key_exists('#items', $source)) {
            $value &= !empty($source['#items']->value);
          }
          else {
            $value &= !empty($source);
          }
        }
        $element['#' . $field_name] = $value;
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'handleOptionalFields',
      'handleMultipleFields',
      'handleBooleanFields',
    ];
  }

}
