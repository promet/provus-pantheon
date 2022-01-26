<?php

/**
 * @file
 * Contains provus.profile.
 */

use Drupal\Core\Session\AccountInterface;
use Drupal\provus\Installer\Form\ExtensionConfigureForm;
use Symfony\Component\Yaml\Parser;


/**
 * Implements hook_install_tasks().
 */
function provus_install_tasks() {
    return [
      'provus_install_extensions' => [
        'display_name' => t('Install Provus Extensions'),
        'display' => TRUE,
        'type' => 'batch',
      ],
      'provus_install_demo_content' => [
        'display_name' => t('Install Demo Content'),
        'display' => TRUE,
        'type' => 'batch',
      ],
    ];
  }

// /**
//  * Implements hook_install_tasks_alter().
//  */
// function provus_install_tasks_alter(array &$tasks, array $install_state) {

//   $task_keys = array_keys($tasks);
//   $insert_before = array_search('provus_install_extensions', $task_keys, TRUE);
//   $tasks = array_slice($tasks, 0, $insert_before - 1, TRUE) +
//     [
//       'provus_extension_configure_form' => [
//         'display_name' => t('Select Provus features to enable'),
//         'type' => 'form',
//         'function' => ExtensionConfigureForm::class,
//       ],
//     ] +
//     array_slice($tasks, $insert_before - 1, NULL, TRUE);
// }


/**
 * Set the path to the logo, favicon and README file based on install directory.
 */
function provus_set_logo() {
    $provus_path = drupal_get_path('profile', 'provus');
  
    Drupal::configFactory()
      ->getEditable('system.theme.global')
      ->set('logo', [
        'path' => $provus_path . '/provus.svg',
        'url' => '',
        'use_default' => FALSE,
      ])
      ->set('favicon', [
        'mimetype' => 'image/vnd.microsoft.icon',
        'path' => $provus_path . '/favicon.ico',
        'url' => '',
        'use_default' => FALSE,
      ])
      ->save(TRUE);
  }
  