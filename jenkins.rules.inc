<?php
/**
 * @file
 *  Rules module hook implementations.
 */

/**
 * Implements hook_rules_action_info().
 */
function jenkins_rules_action_info() {
  $actions = array();

  $defaults = array(
    'group' => t('Jenkins'),
    'access callback' => 'jenkins_rules_integration_access',
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

  $actions['jenkins_request'] = $defaults + array(
    'label' => t('Make an arbitrary request'),
    'base' => 'jenkins_rules_request',
    'parameter' => array(
      'path' => array(
        'type' => 'text',
        'label' => t('Path'),
      ),
      'query' => array(
        'type' => 'text',
        'label' => t('Query XML'),
        'description' => t('Eg. ') . htmlspecialchars('<query><name><![CDATA[[user:name]]]></name><uid><![CDATA[[user:uid]]]></uid></query>'),
      ),
      'method' => array(
        'type' => 'text',
        'label' => t('Method'),
        'description' => t('Either GET or POST'),
      ),
      'data' => array(
        'type' => 'text',
        'label' => t('Data'),
      ),
      'headers' => array(
        'type' => 'text',
        'label' => t('Header XML'),
        'description' => t('Eg. ') . htmlspecialchars('<headers><Content-Type><![CDATA[[application/json]]]></Content-Type></headers>'),
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
 * Performs an arbitrary request.
 */
function jenkins_rules_request($path, $query_xml, $method, $data, $headers_xml) {
  $query = _jenkins_xml_to_array($query_xml);
  $headers = _jenkins_xml_to_array($headers_xml);

  jenkins_request($path, $query, $method, $data, $headers);
}

/**
 * Helper function which takes a single level XML document string and converts it to a one dimensional array.
 *
 * @param string $xml_str
 * @return array
 */
function _jenkins_xml_to_array($xml_str) {
  $array = array();
  $xml_str = trim($xml_str);
  if (!empty($xml_str)) {
    $xml = new SimpleXMLElement($xml_str);
    foreach ($xml as $name => $element) {
      $array[$name] = (string) $element;
    }
  }
  return $array;
}
