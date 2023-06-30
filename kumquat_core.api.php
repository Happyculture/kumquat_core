<?php

/**
 * @file
 * Hooks provided by the Kumquat Core module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define the available styles for all paragraph types.
 *
 * @param string $paragraph_type
 *   The paragraph type.
 *
 * @return array
 *   An associative array with style machine name as key and style label as
 *   value.
 */
function hook_kumquat_style(string $paragraph_type) {
  if ($paragraph_type !== 'rich_text') {
    return [];
  }
  return [
    'default' => t('Default'),
    'hl' => t('Highlighted'),
    'str' => t('Strong'),
  ];
}

/**
 * Define the available styles for a specific paragraph type.
 *
 * @param string $paragraph_type
 *   The paragraph type.
 *
 * @return array
 *   An associative array with style machine name as key and style label as
 *   value.
 */
function hook_PARAGRAPH_TYPE_kumquat_style(string $paragraph_type) {
  return [
    'default' => t('Default'),
    'hl' => t('Highlighted'),
    'str' => t('Strong'),
  ];
}

/**
 * Alter the available styles for all paragraph types.
 *
 * This hook is called on both modules and active themes.
 *
 * @param array $styles
 *   An associative array with style machine name as key and style label as
 *   value.
 * @param string $paragraph_type
 *   The paragraph type.
 */
function hook_kumquat_style_alter(array &$styles, string $paragraph_type) {
  if ($paragraph_type === 'rich_text') {
    $styles['custom'] = t('Custom');
  }
}

/**
 * Alter the available styles for a specific paragraph types.
 *
 * This hook is called on both modules and active themes.
 *
 * @param array $styles
 *   An associative array with style machine name as key and style label as
 *   value.
 * @param string $paragraph_type
 *   The paragraph type.
 */
function hook_PARAGRAPH_TYPE_kumquat_style_alter(array &$styles, string $paragraph_type) {
  $styles['custom'] = t('Custom');
}

/**
 * @} End of "addtogroup hooks".
 */
