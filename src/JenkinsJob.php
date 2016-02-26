<?php

/**
 * @file
 * Contains \Drupal\jenkins\JenkinsJob.
 */

namespace Drupal\jenkins;

use Drupal\Component\Utility\Unicode;

/**
 * Defines a JenkinsJob service.
 *
 * @method bool enable($name)
 * @method bool get($name)
 * @method bool disable($name)
 * @method bool delete($name)
 * @method bool copy($name, $new_name)
 * @method bool create($name, array $options)
 */
class JenkinsJob {

  /**
   * @var \Drupal\jenkins\JenkinsClient
   */
  protected $client;

  /**
   * Constructs a JenkinsJob service.
   *
   * @param \Drupal\jenkins\JenkinsClient $client
   *   The JenkinsClient that will connect to Jenkins.
   */
  public function __construct(JenkinsClient $client) {
    $this->client = $client;
  }

  /**
   * Class overloader.
   *
   * @param string $method
   *   The method that needs to be called on this class.
   * @param string|array $args
   *   Can be either a string or an array.
   *
   * @see JenkinsJob::request()
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   */
  public function __call($method, $args) {
    if (count($args) < 1) {
      throw new \InvalidArgumentException('Magic request methods require a name');
    }

    $name = $args[0];
    $value = isset($args[1]) ? $args[1] : NULL;
    return $this->request($method, $name, $value);
  }

  /**
   * Triggers a build for a jenkins job.
   *
   * @param string $name
   *   The name of the job.
   * @param array $params
   *   An array containing additional parameters if the job requires this.
   *
   * @return int
   *   The job number.
   */
  public function build($name, $params = NULL) {
    $this->assertValidName($name);

    $data = [];
    if (is_array($params) && count($params)) {
      $data = ['parameter' => []];
      foreach ($params as $name => $value) {
        $data['parameter'][] = ['name' => urlencode($name), 'value' => urlencode($value)];
      }
    }

    $options = [
      'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
      'data' => 'json=' . json_encode($data),
    ];
    $name = rawurlencode($name);
    $response = $this->client->request("/job/{$name}/build", $options);
    return $this->queueId($response);
  }

  /**
   * Perform a request on the JenkinsClient.
   *
   * @param $name
   *   The name of the Jenkins job.
   * @param $uri
   *   The URI used for accessing the API.
   * @param array $options
   *   An array containing additional parameters.
   *
   * @throws InvalidArgumentException
   *   When the given $name is invalid
   *
   * @return bool
   *   True if the operation was successful, false if not.
   */
  protected function _execute($name, $uri, $options = []) {
    $this->assertValidName($name);

    $uri = str_replace("{NAME}", rawurlencode($name), $uri);
    return $this->client->request($uri, $options)->getStatusCode() == 200;
  }

  /**
   * Magic method handler.
   *
   * @param $method
   *   The method that needs to be performed.
   * @param $name
   *   The name of the Jenkins job.
   * @param string|array $value
   *   (optional) a value that needs to be passed if applicable.
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   Most of the time a boolean.
   */
  public function request($method, $name, $value) {
    switch ($method) {
      case "enable":
        return $this->_execute($name, "/job/{NAME}/enable");

      case "get":
        return $this->_execute($name, "/job/{NAME}/api/json", ['method' => 'GET']);

      case "disable":
        return $this->_execute($name, "/job/{NAME}/disable");

      case "delete":
        return $this->_execute($name, "/job/{NAME}/doDelete");

      case "copy":
        $this->assertValidName($name);
        $this->assertValidName($value);
        $options = [
          'query' => ['name' => $name, 'mode' => 'copy', 'from' => $value],
          'method' => 'POST',
        ];
        return $this->client->request('/createItem', $options);

      case "create":
        $options = [
          'query' => ['name' => rawurlencode($name)],
          'headers' => ['Content-Type' => 'text/xml'],
          'method' => 'POST',
          'data' => $value,
        ];
        return $this->_execute($name, '/createItem', $options);

      case "update":
        $options = [
          'headers' => ['Content-Type' => 'text/xml'],
          'data' => [$value],
          'method' => 'POST',
        ];
        return $this->_execute($name, "/job/{NAME}/config.xml", $options);
    }
  }

  /**
   * Get a list of jobs.
   *
   * @param $depth
   *   Integer that tells how much data to get from Jenkins.
   * @param $tree
   *   Array describing what data to return. It should be on the form.
   *
   * @todo fix this method
   */
  /*public function getMultiple($depth = 0, $tree = NULL, &$response = NULL) {
    // @todo: honor $tree argument.
    $query = array(
      'depth' => $depth,
    );

    $name = rawurlencode($name);

    if (jenkins_request('/api/json', $response, $query)) {
      return json_decode($response->data);
    }

    return FALSE;
  }*/

  /**
   * Extracts the jenkins build queue id from the HTTP response object.
   *
   * @todo throw exception if queue id isn't available.
   *
   * @param object $response
   *   The response object returned from drupal_http_request().
   *
   * @return int
   *   The jenkins queue id or 0 on error.
   */
  public function queueId($response) {
    if (!isset($response->headers['location'])) {
      return 0;
    }
    else {
      $location = $response->headers['location'];
      $matches = [];
      if (!preg_match('#/queue/item/(\d+)/$#', $location, $matches)) {
        return 0;
      }
      else {
        return !is_numeric($matches[1]) ? 0 : $matches[1];
      }
    }
  }

  /**
   * Get a single build.
   *
   * @param string $name
   *   Job name.
   * @param int $number
   *    build number
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function getBuild($name, $number) {
    $number = (int) $number;
    return $this->client->request("/job/{$name}/{$number}/api/json");
  }

  /**
   * Retrieves full Jenkins build log for a job.
   *
   * @param string $name
   *   The name of the Jenkins job.
   * @param int $number
   *   The job build number.
   *
   * @return object
   *   The http_request() response object.
   */
  public function getBuildLog($name, $number) {
    $name = rawurlencode($name);
    $number = rawurlencode($number);
    return $this->client->request("/job/{$name}/{$number}/consoleText");
  }

  /**
   * Returns the path to a Jenkins build log based on the queue id.
   *
   * @todo throw an exceptions on error or pending.
   *
   * @param int $queue_id
   *   The queue id to use for the lookup.
   *
   * @return array
   *   Information about the current state of the item in the queue.
   */
  public function queueStatus($queue_id) {
    $state = [
      'error' => FALSE,
      'building' => FALSE,
      'message' => '',
      'build_id' => 0,
    ];

    $response = $this->client->request("/queue/item/{$queue_id}/api/json");

    if (200 != $response->getStatusCode()) {
      // Assume 404.
      $state['error'] = TRUE;
      $state['message'] = t('Build completed.');
      return $state;
    }
    else {
      $json = json_decode($response->getBody());
      if (isset($json->executable->number)) {
        // We are building.
        $state['building'] = TRUE;
        $state['build_id'] = (int) $json->executable->number;
        return $state;
      }
      else {
        // This explains why it is still waiting.
        if (isset($json->why)) {
          $state['message'] = $json->why;
          return $state;
        }
        else {
          $state['error'] = TRUE;
          $state['message'] = t('Unknown error.');
          return $state;
        }
      }
    }
  }

  /**
   * Validates a jenkins job name.
   *
   * Based on Hudson.java.checkGoodName() and java's native Character.isISOControl().
   *
   * @param String $name
   *   The name of the job to validate.
   */
  public function assertValidName($name) {
    if (preg_match('~(\\?\\*/\\\\%!@#\\$\\^&\|<>\\[\\]:;)+~', $name)) {
      throw new \InvalidArgumentException("$name is an invalid job name.");
    }

    // Define range of non printable characters.
    $non_print_high = 31;

    // Value PHP assigns if invalid or extended ascii character (? == 63).
    $ascii_garbage = 63;

    $len = Unicode::strlen($name);
    for ($i = 0; $len > $i; ++$i) {
      // Unicode char to ord logic lifted from http://stackoverflow.com/questions/1365583/how-to-get-the-character-from-unicode-value-in-php
      $char = Unicode::substr($name, $i, 1);
      $unpacked = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
      $ord = $unpacked[1];

      if ($ord <= $non_print_high || $ord == $ascii_garbage) {
        throw new \InvalidArgumentException("$name is an invalid job name.");
      }
    }
  }

}