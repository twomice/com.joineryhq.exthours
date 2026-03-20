<?php
use CRM_Exthours_ExtensionUtil as E;

return [
  'name' => 'EntryActivity',
  'table' => 'civicrm_exthours_entry_activity',
  'class' => 'CRM_Exthours_DAO_EntryActivity',
  'getInfo' => fn() => [
    'title' => E::ts('Entry Activity'),
    'title_plural' => E::ts('Entry Activities'),
    'description' => E::ts('FIXME'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique EntryActivity ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'external_id' => [
      'title' => E::ts('External ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => E::ts('External ID (ex: kimai timeEntryID)'),
    ],
    'activity_id' => [
      'title' => E::ts('Activity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Activity'),
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
