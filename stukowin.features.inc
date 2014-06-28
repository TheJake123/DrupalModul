<?php

/**
 * @file stukowin.features.inc
 * TODO: Enter file description here.
 */

/**
 * Implements hook_features_api().
 */
function stukowin_features_api() {
  return array(
    'mycomponent' => array(
      'default_hook' => 'mycomponent_defaults',
      'default_file' => FEATURES_DEFAULTS_INCLUDED,
      'feature_source' => TRUE,
      'file' => drupal_get_path('module', 'mycomponent') . '/mycomponent.features.inc',
    ),
  );
}
