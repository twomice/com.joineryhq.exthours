<?php

use CRM_Exthours_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Exthours_Form_ProjectDelete extends CRM_Core_Form {

  public function buildQuickForm() {
    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => E::ts('Delete Integration'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ),
    ));

    parent::buildQuickForm();
  }

  public function postProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    $results = \Civi\Api4\ProjectContact::delete()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute();

    CRM_Core_Session::setStatus(E::ts('Integrated Project has been deleted!'), E::ts('Kimai Integration'), 'success');

    parent::postProcess();
  }

}
