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
        "apiKey" => $apiKey,
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
      ->addWhere('id', '=', $organizationId)
      ->addWhere('contact_type', '=', 'Organization')
      ->execute()
      ->first();

    return $organization['display_name'];
  }

  /**
   * Get get kimai getUpdates api data
   *
   * @return organization name.
   */
  public static function getKimaiUpdates() {
    $apiKey = Civi::settings()->get('exthours_kimai_api_key');

    // Kimai Get Timesheet Method
    $kimaiAuth = array(
      "method" => "getUpdates",
      "params" => array(
        "apiKey" => $apiKey,
      ),
      "id" => "1",
      "jsonrpc" => "2.0",
    );

    // API Request
    $request = CRM_Exthours_Kimai_Api::request($kimaiAuth, 'POST', 'core/civicrm.php');

    return $request['result']['items']['queued_data'];
  }

}
