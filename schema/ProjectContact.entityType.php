<?php
use CRM_Exthours_ExtensionUtil as E;

return [
  'name' => 'ProjectContact',
  'table' => 'civicrm_exthours_project_contact',
  'class' => 'CRM_Exthours_DAO_ProjectContact',
  'getInfo' => fn() => [
    'title' => E::ts('Project Contact'),
    'title_plural' => E::ts('Project Contacts'),
    'description' => E::ts('FIXME'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique ProjectContact ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'external_id' => [
      'title' => E::ts('External ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => E::ts('External ID (ex: kimai projectID)'),
    ],
    'contact_id' => [
      'title' => E::ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
