<?php

/**
 * Settings-related utility methods.
 * @link https://www.kimai.org/v1/api.html
 */
class CRM_Exthours_Kimai_Utils {

  /**
   * Kimai Authenticate Setup Access to get API Key
   *
   * @param string $username Kimai username
   * @param string $password Kimai password
   * @return array for kimai api key request result.
   */
  public static function kimaiAuthAPIKey($username, $password) {
    // Kimai authenticate method array
    $kimaiAuth = array(
      "method" => "authenticate",
      "params" => array(
        $username,
        $password,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST');

    return $request['result'];
  }

  /**
   * Get Kimai Setup Prime
   *
   * @return array
   */
  public static function kimaiSetupPrime() {
    $apiKey = Civi::settings()->get('exthours_kimai_api_key');

    // Kimai database will create the custom table
    // and additional column for kimai_timesheet table
    $kimaiAuth = array(
      "method" => "primeUpdates",
      "params" => array(
        $apiKey,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request for the primeUpdates
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST', 'core/civicrm.php');

    if ($request['result']['success']) {
      Civi::settings()->set('exthours_kimai_setup_primed', TRUE);
    }

    return $request['result'];
  }

  /**
   * Get Kimai Timesheet
   *
   * @return array of kimai timesheet data.
   */
  public static function getKimaiTimesheet() {
    $apiKey = Civi::settings()->get('exthours_kimai_api_key');

    // Kimai Get Timesheet Method
    $kimaiAuth = array(
      "method" => "getTimesheet",
      "params" => array(
        "apiKey" => $apiKey,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST');

    return $request['result'];
  }

  /**
   * Get Kimai Projects
   *
   * @return array of kimai projects data.
   */
  public static function getKimaiProjects() {
    $apiKey = Civi::settings()->get('exthours_kimai_api_key');

    // Kimai Get Timesheet Method
    $kimaiAuth = array(
      "method" => "getProjects",
      "params" => array(
        "apiKey" => $apiKey,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST');

    return $request['result'];
  }

  /**
   * Get Kimai Project Name
   * @param Int $projectId kimai project id
   *
   * @return kimai project name.
   */
  public static function getKimaiProjectName($projectId) {
    $projects = self::getKimaiProjects();
    $projectName = '';

    foreach ($projects['items'] as $project) {
      if ($project['projectID'] == $projectId) {
        $projectName = $project['name'];
      }
    }

    return $projectName;
  }

  /**
   * Get Organization Name
   * @param Int $organizationId kimai project id
   *
   * @return organization name.
   */
  public static function getOrganizationName($organizationId) {
    $organization = \Civi\Api4\Contact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $organizationId)
      ->addWhere('contact_type', '=', 'Organization')
      ->execute()
      ->first();

    return $organization['display_name'];
  }

  /**
   * Get kimai getUpdates api data
   */
  public static function getKimaiUpdatesData($projectId) {
    $apiKey = Civi::settings()->get('exthours_kimai_api_key');

    // Kimai Get Timesheet Method
    $kimaiAuth = array(
      "method" => "getUpdates",
      "params" => array(
        $apiKey,
        $projectId,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST', 'core/civicrm.php');

    return $request['result']['items']['queued_data'];
  }

  /**
   * Confirm queued timesheet in kimai using API
   * @param $queuedId
   * @return array
   */
  public static function confirmQueueMessage($queuedId) {
    $apiKey = Civi::settings()->get('exthours_kimai_api_key');

    // Kimai Confirm Queue Message
    $kimaiAuth = array(
      "method" => "confirmQueueMessage",
      "params" => array(
        $apiKey,
        $queuedId,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST', 'core/civicrm.php');

    return $request['result']['items'];
  }

  /**
   * Update activity using the kimai queued data
   * @param $queuedId
   * @param $action (delete and update)
   * @param $data (queued_timesheet_data)
   */
  public static function getKimaiUpdate($queuedId, $action, $data) {
    $entryActivity = \Civi\Api4\EntryActivity::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('external_id', '=', $data['timeEntryID'])
      ->execute()
      ->first();

    $message = [];

    if ($action === 'update') {
      // Get contact id in exthours_project_contact for the source_contact_id in activity
      $projectContacts = \Civi\Api4\ProjectContact::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('external_id', '=', $data['projectID'])
        ->execute()
        ->first();

      // If projectID doesn't exist in exthours_project_contact, log error message and don't save the activity
      if (!$projectContacts) {
        $errorMessage = "Exthours: could not find organization contact for kimai queue item {$queuedId}, timesheet entry id {$data['timeEntryID']}";
        CRM_Core_Error::debug_log_message($errorMessage);
        return $errorMessage;
      }

      $duration = round($data['duration'] / 60);

      $customValues = [
        'work_category' => $data['activityID'],
        'tracking_number' => $data['trackingNumber'],
      ];

      if ($entryActivity) {
        // update data in activity using activity_id in exthours_entry_activity
        $results = \Civi\Api4\Activity::update()
          ->setCheckPermissions(FALSE)
          ->addWhere('id', '=', $entryActivity['activity_id'])
          ->addValue('activity_type_id:name', 'exthours_servicehours')
          ->addValue('activity_date_time', date("Y-m-d H:i:s", $data['start']))
          ->addValue('duration', $duration)
          ->addValue('details', $data['comment'])
          ->addValue('source_contact_id', $projectContacts['contact_id'])
          ->addValue('target_contact_id', $projectContacts['contact_id'])
          ->execute();

        self::saveActivityCustomFields($entryActivity['activity_id'], $customValues, 'update');
      }
      else {
        // Add new activity
        $createActivity = \Civi\Api4\Activity::create()
          ->setCheckPermissions(FALSE)
          ->addValue('activity_type_id:name', 'exthours_servicehours')
          ->addValue('activity_date_time', date("Y-m-d H:i:s", $data['start']))
          ->addValue('duration', $duration)
          ->addValue('details', $data['comment'])
          ->addValue('source_contact_id', $projectContacts['contact_id'])
          ->addValue('target_contact_id', $projectContacts['contact_id'])
          ->execute()
          ->first();

        // Add new exthours_entry_activity
        $createEntryActivity = \Civi\Api4\EntryActivity::create()
          ->setCheckPermissions(FALSE)
          ->addValue('external_id', $data['timeEntryID'])
          ->addValue('activity_id', $createActivity['id'])
          ->execute();

        self::saveActivityCustomFields($createActivity['id'], $customValues);
      }
    }
    elseif ($action === 'delete') {
      // delete timesheet in activity
      $deleteActivity = \Civi\Api4\Activity::delete()
        ->setCheckPermissions(FALSE)
        ->addWhere('id', '=', $entryActivity['activity_id'])
        ->execute();

      // delete exthours_entry_activity
      $deleteEntryActivity = \Civi\Api4\EntryActivity::delete()
        ->setCheckPermissions(FALSE)
        ->addWhere('external_id', '=', $entryActivity['external_id'])
        ->execute();
    }

    $message = self::confirmQueueMessage($queuedId);

    return $message;
  }

  /**
   * Get all registered activity in kimai using API
   * @param $queuedId
   * @return array of kimai activities
   */
  public static function getKimaiActivities() {
    $apiKey = Civi::settings()->get('exthours_kimai_api_key');

    // Kimai Get Activities API
    $kimaiAuth = array(
      "method" => "getActivities",
      "params" => array(
        $apiKey,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST', 'core/civicrm.php');

    return $request['result']['items'];
  }

  /**
   * Save kimai activity ID in the work category custom field
   * @param $entityId
   * @param $workCategoryId
   * @param $action (existing activity for update)
   */
  public static function saveActivityCustomFields($entityId, $customFields, $action = NULL) {
    // Get column name of workcategory custom field
    $workCategory = \Civi\Api4\CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('option_group_id:name', '=', 'exthours_workcategory')
      ->execute()
      ->first();

    // Get column name of tracking number custom field
    $trackingNumber = \Civi\Api4\CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('label', '=', 'Tracking Number')
      ->execute()
      ->first();

    // Get column name of is invoiced custom field
    $isInvoiced = \Civi\Api4\CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('label', '=', 'Is Invoiced?')
      ->execute()
      ->first();

    // Insert/Update work category
    $createWorkCategory = civicrm_api3('CustomValue', 'create', [
      'sequential' => 1,
      'entity_id' => $entityId,
      'entity_type' => 'Activity',
      "custom_{$workCategory['id']}" => $customFields['work_category'],
    ]);

    // Init trackingNumberValue and IsInvoicedValue
    $trackingNumberVal = NULL;
    $isInvoicedVal = 0;

    // If customFields['tracking_number'] is not empty, update...
    // trackingNumberValue and IsInvoicedValue value
    if (!empty($customFields['tracking_number'])) {
      $trackingNumberVal = $customFields['tracking_number'];
      $isInvoicedVal = 1;
    }

    // Insert/Update tracking number
    $createTrackingNumber = civicrm_api3('CustomValue', 'create', [
      'sequential' => 1,
      'entity_id' => $entityId,
      'entity_type' => 'Activity',
      "custom_{$trackingNumber['id']}" => $trackingNumberVal,
    ]);

    // Insert/Update isInvoiced
    $createIsInvoiced = civicrm_api3('CustomValue', 'create', [
      'sequential' => 1,
      'entity_id' => $entityId,
      'entity_type' => 'Activity',
      "custom_{$isInvoiced['id']}" => $isInvoicedVal,
    ]);
  }

}
