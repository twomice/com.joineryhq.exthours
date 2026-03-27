<?php

use CRM_Exthours_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Exthours_Form_Project extends CRM_Core_Form {

  /**
   * System ID for Project Contact being edited.
   * @var int
   */
  private $_id;

  /**
   * Pre-process
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );
  }

  public function buildQuickForm() {
    $projectOptions = [];

    // Fetch Kimai Projects
    $kimaiProjects = CRM_Exthours_Kimai_Utils::getKimaiProjects();
    foreach ($kimaiProjects['items'] as $kimaiProject) {
      $isUsed = \Civi\Api4\ProjectContact::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('external_id', '=', $kimaiProject['projectID'])
        ->execute()
        ->count();
      if (!$isUsed) {
        $projectOptions[$kimaiProject['projectID']] = $kimaiProject['name'];
      }
    }
    $projectOptions = CRM_Utils_Array::asort($projectOptions);
    $this->add('select', 'kimai_project_id', E::ts('Kimai Project'), $projectOptions, true, ['placeholder' => E::ts('Select'), 'class' => 'crm-select2 huge']);

    // Fetch Contact Organization API using addEntityRef
    $entityRefParams = [
      'create' => TRUE,
      'api' => [
        'params' => ['contact_type' => 'Organization'],
      ],
      'placeholder' => 'Select Organization',
    ];
    $this->addEntityRef('civicrm_organization_id', E::ts('CiviCRM Organization'), $entityRefParams, TRUE);

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => ts('Save and New'),
        'subName' => 'new',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ),
    ));

    parent::buildQuickForm();
  }

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_id) {
      $projectContact = \Civi\Api4\ProjectContact::get()
        ->addWhere('id', '=', $this->_id)
        ->execute()
        ->first();
      $defaults['kimai_project_id'] = $projectContact['external_id'];
      $defaults['civicrm_organization_id'] = $projectContact['contact_id'];
    }

    return $defaults;
  }

  /**
   * Override parent::validate().
   */
  public function validate() {
    $error = parent::validate();
    $values = $this->exportValues();

    $getProjectId = \Civi\Api4\ProjectContact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('external_id', '=', $values['kimai_project_id']);
    $getOrganizationId = \Civi\Api4\ProjectContact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('contact_id', '=', $values['civicrm_organization_id']);

    // If edit/update, exclude current ID
    if ($this->_id) {
      $getProjectId->addWhere('id', '!=', $this->_id);
      $getOrganizationId->addWhere('id', '!=', $this->_id);
    }

    $checkProjectId = $getProjectId->execute()->first();
    $checkOrganizationId = $getOrganizationId->execute()->first();

    if ($checkProjectId) {
      $this->setElementError('kimai_project_id', E::ts('This Organization is already integrated'));
    }

    if ($checkOrganizationId) {
      $this->setElementError('civicrm_organization_id', E::ts('This Organization is already integrated'));
    }

    return (0 == count($this->_errors));
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $values = $this->exportValues();

    if ($this->_id) {
      $results = \Civi\Api4\ProjectContact::update()
        ->setCheckPermissions(FALSE)
        ->addWhere('id', '=', $this->_id)
        ->addValue('external_id', $values['kimai_project_id'])
        ->addValue('contact_id', $values['civicrm_organization_id'])
        ->execute();

      CRM_Core_Session::setStatus(E::ts('Project has successfully edited!'), E::ts('Kimai Integration: Project'), 'success');
    }
    else {
      $results = \Civi\Api4\ProjectContact::create()
        ->setCheckPermissions(FALSE)
        ->addValue('external_id', $values['kimai_project_id'])
        ->addValue('contact_id', $values['civicrm_organization_id'])
        ->execute();

      CRM_Core_Session::setStatus(E::ts('A new project has been integrated!'), E::ts('Kimai Integration: Project'), 'success');
    }

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      $session->setStatus(ts('You can add another project.'), '', 'info');
      $session->replaceUserContext(
        CRM_Utils_System::url(
          'civicrm/admin/exthours/project',
          'reset=1&action=add'
        )
      );
    }

    parent::postProcess();
  }

}
