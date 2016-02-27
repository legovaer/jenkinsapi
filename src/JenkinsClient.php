<?php
/**
 * Created by PhpStorm.
 * User: legovaer
 * Date: 24/02/16
 * Time: 15:06
 */

namespace Drupal\jenkins;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;


class JenkinsClient {

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('jenkins.settings');
  }

  /**
   * Perform a request to Jenkins server and return the response.
   *
   * @param string $path
   *   API path with leading slash, for example '/api/json'.
   * @param object $response
   *   HTTP request response object - see \Drupal::httpClient()->get()
   * @param $query
   *   Data to be sent as query string.
   * @param string $method
   *   HTTP method, either 'GET' (default) or 'POST'.
   * @param array $data
   *   Post data.
   * @param array $headers
   *   HTTP headers.
   *
   * @see \Drupal::httpClient()->get()
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function request($uri, array $options = []) {
  //public function request($path, $query = array(), $method = 'GET', $data = NULL, $headers = array()) {
    $options['max_redirects'] = 0;

    // Force request to start immediately.
    if (!isset($options['query']['delay'])) {
      $options['query']['delay'] = '0sec';
    }

    if (!isset($options['method'])) {
      $options['method'] = 'GET';
    }

    // Default to JSON unless otherwise specified.
    $default_headers = [
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
    ];
    array_merge($options['headers'], $default_headers);

    // Do HTTP request and get response object.
    $url = $this->config->get('base_url') . $uri . '?' . UrlHelper::buildQuery($options['query']);
    return \Drupal::httpClient()->get($url, $options);
  }
}