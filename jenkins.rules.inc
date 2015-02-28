<?php
/**
 * @file
 *  Rules module hook implementations.
 */

/**
 * Implements hook_rules_action_info().
 */
function jenkins_rules_action_info() {
  // @ignore style_lowercase_html:function
  $actions = array();

  $defaults = array(
    'group' => t('Jenkins'),
    'access callback' => 'jenkins_rules_integration_access',
  );

  $actions['jenkins_job_build'] = $defaults + array(
    'label' => t('Trigger Build'),
    'base' => 'jenkins_rules_job_build',
    'parameter' => array(
      'name' => array(
        'type' => 'text',
        'label' => t('Job name'),
      ),
      'params' => array(
        'type' => 'text',
        'label' => t('Parameters'),
        'description' => t('The values for parameterized jobs. Each argument should be a separate line with the following format KEY=value. Leave empty for non parameterized jobs.'),
        'optional' => TRUE,
      ),
      'folder' => array(
        'type' => 'text',
        'label' => t('Folder'),
        'description' => t('The name of the folder for the job. Leave empty for jobs not in folders.'),
        'optional' => TRUE,
      ),
      'error_is_fatal' => array(
        'label' => t('Error is fatal'),
        'type' => 'boolean',
        'description' => t('Fatal errors prevent further actions from firing.'),
        'optional' => TRUE,
        'default value' => FALSE,
      ),
    ),
    'provides' => array(
      'jenkins_job_name' => array(
        'type' => 'text',
        'label' => t('Job Name'),
      ),
      'jenkins_queue_id' => array(
        'type' => 'integer',
        'label' => t('Build Number'),
      ),
    ),
  );

  $actions['jenkins_create_job'] = $defaults + array(
    'label' => t('Create a new job'),
    'base' => 'jenkins_rules_create_job',
    'parameter' => array(
      'name' => array(
        'type' => 'text',
        'label' => t('Job name'),
      ),
      'data' => array(
        'type' => 'text',
        'label' => t('Job XML Data'),
      ),
    ),
  );

  $actions['jenkins_copy_job'] = $defaults + array(
    'label' => t('Copy an existing job'),
    'base' => 'jenkins_rules_copy_job',
    'parameter' => array(
      'new_name' => array(
        'type' => 'text',
        'label' => t('New job name'),
      ),
      'existing_name' => array(
        'type' => 'text',
        'label' => t('Existing job name'),
      ),
    ),
  );

  return $actions;
}

/**
 * Access callback for all Jenkins rules actions.
 */
function jenkins_rules_integration_access($permission) {
  return user_access('administer jenkins requests');
}

/**
 * Creates a job.
 */
function jenkins_rules_create_job($name, $data_xml) {
  jenkins_create_job($name, $data_xml);
}

/**
 * Copies a job.
 */
function jenkins_rules_copy_job($new, $existing) {
  jenkins_copy_job($new, $existing);
}

/**
 * Triggers the build of a job.
 */
function jenkins_rules_job_build($name, $params, $folder, $error_is_fatal) {
  $data = NULL;
  $params = trim($params);

  $name = rawurlencode($name);
  if ($folder) {
    $name = rawurlencode($folder) . '/job/' . $name;
  }

  if (strlen($params)) {
    $json = jenkins_parse_args($params);
    if (NULL !== $json) {
      $data = $json;
    }
  }

  $response = NULL;
  $queue_id = jenkins_job_build($name, $data, $response);
  $vars = array(
    'jenkins_queue_id' => (int) $queue_id,
    'jenkins_job_name' => $name,
  );
  return $vars;
}
