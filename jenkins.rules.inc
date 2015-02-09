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
      'block' => array(
        'type' => 'boolean',
        'label' => t('Block'),
        'description' => t('Wait until build has completed?  This can cause Drupal requests to time out.'),
        'optional' => TRUE,
        'default value' => FALSE,
      ),
    ),
    'provides' => array(
      'jenkins_job_name' => array(
        'type' => 'text',
        'label' => t('Job Name'),
      ),
      'jenkins_build_id' => array(
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
function jenkins_rules_job_build($name, $params, $block) {

  $data = NULL;
  $params = trim($params);

  // Ensure we have a valid data.
  if (strlen($params)) {
    $json = jenkins_parse_args($params);
    if (NULL !== $json) {
      $data = $json;
    }
  }

  $response = NULL;
  $build_id = jenkins_job_build($name, $data, $response, $block);
  $vars = array(
    'jenkins_build_id' => (int) $build_id,
    'jenkins_job_name' => $name,
  );
  return $vars;
}
