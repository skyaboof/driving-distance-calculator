<?php

namespace Drupal\driving_distance_calculator\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service to fetch distance & duration from Google Maps API.
 */
class GoogleDistanceService {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * Fetch distance between two addresses.
   */
  public function getDistance(string $origin, string $destination): array {
    $config = $this->configFactory->get('driving_distance_calculator.settings');
    $api_key = $config->get('google_api_key');

    if (empty($api_key)) {
      return ['status' => 'error', 'message' => 'Google API key missing'];
    }

    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    $params = [
      'origins' => $origin,
      'destinations' => $destination,
      'mode' => 'driving',
      'units' => 'metric',
      'key' => $api_key,
    ];

    try {
      $response = $this->httpClient->request('GET', $url, ['query' => $params]);
      $data = json_decode($response->getBody()->getContents(), TRUE);

      if ($data['status'] !== 'OK') {
        return ['status' => 'error', 'message' => $data['status']];
      }

      $element = $data['rows'][0]['elements'][0];
      if ($element['status'] !== 'OK') {
        return ['status' => 'error', 'message' => $element['status']];
      }

      return [
        'status' => 'success',
        'distance_km' => $element['distance']['value'] / 1000.0,
        'duration_min' => $element['duration']['value'] / 60.0,
      ];
    }
    catch (\Exception $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

}
