<?php

use CRM_Exthours_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Exthours_Form_Setup extends CRM_Core_Form {

  public function preProcess() {
    CRM_Utils_System::setTitle(E::ts('External Hours: Kimai API Key setup'));
  }

  public function buildQuickForm() {
    $this->add('text',
      'kimai_username',
      E::ts('Username'),
      '',
      TRUE
    );

    $this->add('password',
      'kimai_pass',
      E::ts('Password'),
      '',
      TRUE
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Setup API Key'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ),
    ));

    parent::buildQuickForm();
  }

  /**
   * Override parent::validate().
   */
  public function validate() {
    $error = parent::validate();
    $values = $this->exportValues();

    $request = CRM_Exthours_Kimai_Utils::kimaiAuthAPIKey($values['kimai_username'], $values['kimai_pass']);
    if (!$request['success']) {
      $this->setElementError('kimai_username', E::ts('Unknown user or no permissions.'));
      $this->setElementError('kimai_pass', E::ts('Unknown password or no permissions.'));
      CRM_Core_Session::setStatus(E::ts('There is an error with the username and password since it fails to connect in Kimai API.'), E::ts('External Hours: Kimai API Key setup'), "error");
    }
    else {
      $this->set('kimaiRequest', $request);
    }

    return (0 == count($this->_errors));
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $values = $this->exportValues();
    $request = $this->get('kimaiRequest');
    Civi::settings()->set('exthours_kimai_api_key', $request['items'][0]['apiKey']);
    CRM_Exthours_Kimai_Utils::kimaiSetupPrime();

    // Get option value details of exthours_servicehours activity type
    // (Activity types are stored as option values for option group id=2)
    $serviceHoursOptionValue = \Civi\Api4\OptionValue::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('option_group_id', '=', 2)
      ->addWhere('name', '=', 'exthours_servicehours')
      ->execute()
      ->first();

    // Get option group details of exthours_workcategory
    $workCategoryOptionGroup = \Civi\Api4\OptionGroup::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', 'exthours_workcategory')
      ->execute()
      ->first();

    // Create custom group for the Service Hours Details
    // Extend as Activity with column value as exthours_servicehours value

    $serviceHoursDetailsCustomGroup = $this->_createIfNotExistsAndGetServiceHoursDetailsCustomGroup($serviceHoursOptionValue['value']);

    // Create -- if not exists -- custom fields for the Service Hours Details custom group
    $this->_createIfNotExistServiceHoursDetailsCustomFields($serviceHoursDetailsCustomGroup['id'], $workCategoryOptionGroup['id']);

    // Save all kimai activities in option group id exthours_workcategory
    $kimaiActivities = CRM_Exthours_Kimai_Utils::getKimaiActivities();
    foreach ($kimaiActivities as $activity) {
      $existingOptionValue = \Civi\Api4\OptionValue::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('option_group_id:name', '=', 'exthours_workcategory')
        ->addWhere('value', '=', $activity['activityID'])
        ->execute()
        ->first();
      if (empty($existingOptionValue)) {
        $results = \Civi\Api4\OptionValue::create()
          ->setCheckPermissions(FALSE)
          ->addValue('option_group_id:name', 'exthours_workcategory')
          ->addValue('label', $activity['name'])
          ->addValue('value', $activity['activityID'])
          ->execute();
      }
    }

    CRM_Core_Session::setStatus(E::ts('Kimai API Key has successfully setup.'), E::ts('External Hours: Kimai API Key setup'), "success");
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/exthours/settings', 'reset=1'));
    parent::postProcess();
  }

  /**
   * Get the Service Hours Details custom group object via api; if it doesn't
   * exist, create it first.
   *
   * @param $extendColumnValue extend column value of the service hours details custom group
   *
   * @return Object CiviCRM api4 custom group object.
   */
  private function _createIfNotExistsAndGetServiceHoursDetailsCustomGroup($extendColumnValue) {
    $serviceHoursDetailsCustomGroup = \Civi\Api4\CustomGroup::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', 'Service_Hours_Details')
      ->addWhere('extends', '=', 'Activity')
      ->execute()
      ->first();
    if (empty($serviceHoursDetailsCustomGroup)) {
      // Couldn't find one, so create it.
      $serviceHoursDetailsCustomGroup = \Civi\Api4\CustomGroup::create()
        ->setCheckPermissions(FALSE)
        ->addValue('name', 'Service_Hours_Details')
        ->addValue('title', 'Service Hours Details')
        ->addValue('extends', 'Activity')
        ->addValue('collapse_display', FALSE)
        ->addValue('style:name', 'Inline')
        ->addValue('extends_entity_column_value', [
            $extendColumnValue,
          ])
        ->execute()
        ->first();
    }
    return $serviceHoursDetailsCustomGroup;
  }

  /**
   * Create all appropriate custom fields in the Service Hours Details custom group;
   * but only create each field if it doesn't exist already.
   *
   * @param Int $customGroupId
   * @param Int $workCategoryoptionGroupId
   */
  private function _createIfNotExistServiceHoursDetailsCustomFields($customGroupId, $workCategoryoptionGroupId) {
    $workCategoryCustomField = \Civi\Api4\CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', 'Work_Category')
      ->addWhere('custom_group_id', '=', $customGroupId)
      ->execute()
      ->first();
    if (empty($workCategoryCustomField)) {
      $workCategoryCustomField = \Civi\Api4\CustomField::create()
        ->setCheckPermissions(FALSE)
        ->addValue('custom_group_id', $customGroupId)
        ->addValue('name', 'Work_Category')
        ->addValue('label', 'Work Category')
        ->addValue('data_type', 'Int')
        ->addValue('html_type', 'Select')
        ->addValue('option_group_id', $workCategoryoptionGroupId)
        ->addValue('is_view', TRUE)
        ->addValue('is_searchable', TRUE)
        ->execute();
    }

    $trackingNumberCustomeField = \Civi\Api4\CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', 'Tracking_Number')
      ->addWhere('custom_group_id', '=', $customGroupId)
      ->execute()
      ->first();
    if (empty($trackingNumberCustomeField)) {
      $trackingNumberCustomeField = \Civi\Api4\CustomField::create()
        ->setCheckPermissions(FALSE)
        ->addValue('custom_group_id', $customGroupId)
        ->addValue('name', 'Tracking_Number')
        ->addValue('label', 'Tracking Number')
        ->addValue('data_type', 'String')
        ->addValue('html_type', 'Text')
        ->addValue('is_view', TRUE)
        ->addValue('is_searchable', TRUE)
        ->execute();
    }

    $isInvoicedCustomeField = \Civi\Api4\CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', 'Is_Invoiced')
      ->addWhere('custom_group_id', '=', $customGroupId)
      ->execute()
      ->first();
    if (empty($isInvoicedCustomeField)) {
      $isInvoicedCustomeField = \Civi\Api4\CustomField::create()
        ->setCheckPermissions(FALSE)
        ->addValue('custom_group_id', $customGroupId)
        ->addValue('name', 'Is_Invoiced')
        ->addValue('label', 'Is Invoiced?')
        ->addValue('data_type', 'Boolean')
        ->addValue('html_type', 'Radio')
        ->addValue('is_view', TRUE)
        ->addValue('is_searchable', TRUE)
        ->execute();
    }
  }
}
