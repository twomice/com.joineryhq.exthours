<?php

require_once 'exthours.civix.php';
// phpcs:disable
use CRM_Exthours_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function exthours_civicrm_config(&$config) {
  _exthours_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function exthours_civicrm_install() {
  _exthours_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function exthours_civicrm_enable() {
  _exthours_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function exthours_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_pageRun().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_pageRun
 */
function exthours_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');

  // $meowk = CRM_Exthours_Kimai_Utils::getKimaiUpdatesData();
  // echo "<pre>";
  // print_r($meowk);
  // echo "</pre>";
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function exthours_civicrm_navigationMenu(&$menu) {
  $pages = array(
    'admin_page' => array(
      'label' => E::ts('External Hours'),
      'name' => 'External Hours',
      'url' => 'civicrm/admin/exthours/settings?reset=1',
      'parent' => array('Administer', 'System Settings'),
      'permission' => 'access CiviCRM',
    ),
  );

  foreach ($pages as $page) {
    // Check that our item doesn't already exist.
    $menu_item_properties = array('url' => $page['url']);
    $existing_menu_items = array();
    CRM_Core_BAO_Navigation::retrieve($menu_item_properties, $existing_menu_items);
    if (empty($existing_menu_items)) {
      // Now we're sure it doesn't exist; add it to the menu.
      $menuPath = implode('/', $page['parent']);
      unset($page['parent']);
      _exthours_civix_insert_navigation_menu($menu, $menuPath, $page);
    }
  }
}

/**
 * Log CiviCRM API errors to CiviCRM log.
 */
function _exthours_log_api_error(API_Exception $e, string $entity, string $action, array $params) {
  $logMessage = "CiviCRM API Error '{$entity}.{$action}': " . $e->getMessage() . '; ';
  $logMessage .= "API parameters when this error happened: " . json_encode($params) . '; ';
  $bt = debug_backtrace();
  $errorLocation = "{$bt[1]['file']}::{$bt[1]['line']}";
  $logMessage .= "Error API called from: $errorLocation";
  CRM_Core_Error::debug_log_message($logMessage);
}

/**
 * CiviCRM API wrapper. Wraps with try/catch, redirects errors to log, saves
 * typing.
 */
function _exthours_civicrmapi(string $entity, string $action, array $params, bool $silence_errors = TRUE) {
  try {
    $result = civicrm_api3($entity, $action, $params);
  }
  catch (API_Exception $e) {
    _exthours_log_api_error($e, $entity, $action, $params);
    if (!$silence_errors) {
      throw $e;
    }
  }

  return $result;
}
