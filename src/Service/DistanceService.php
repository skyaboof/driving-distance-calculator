<?php

declare(strict_types=1);

namespace Drupal\driving_distance_calculator\Service;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service to calculate driving distance using an external API.
 */
class DistanceService {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Calculate driving distance between two sets of coordinates.
   *
   * Coordinates expected as [lon, lat].
   *
   * Returns an associative array:
   * - distance: (float|null) meters
   * - duration: (float|null) seconds
   * - raw: (mixed|null) raw API response
   * - error: (string|null) error message on failure
   *
   * @param array|string $from
   * @param array|string $to
   *
   * @return array
   */
  public function calculateDistance($from, $to): array {
    $config = $this->configFactory->get('driving_distance_calculator.settings');
    $api_key = $config->get('api_key') ?? '';
    $provider = $config->get('provider') ?? 'openrouteservice';

    if (empty($api_key)) {
      $this->logger->error('Driving Distance Calculator: API key is empty in configuration.');
      return ['distance' => null, 'duration' => null, 'raw' => null, 'error' => 'API key not configured'];
    }

    try {
      if ($provider === 'openrouteservice') {
        $url = 'https://api.openrouteservice.org/v2/directions/driving-car';
        $body = [
          'coordinates' => [
            (array) $from,
            (array) $to,
          ],
        ];

        $response = $this->httpClient->request('POST', $url, [
          'headers' => [
            'Authorization' => $api_key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
          ],
          'json' => $body,
          'timeout' => 10,
        ]);

        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (!empty($data['features'][0]['properties']['segments'][0])) {
          $segment = $data['features'][0]['properties']['segments'][0];
          return [
            'distance' => $segment['distance'],
            'duration' => $segment['duration'],
            'raw' => $data,
            'error' => null,
          ];
        }

        return ['distance' => null, 'duration' => null, 'raw' => $data, 'error' => 'Unexpected response structure'];
      }

      // Additional providers can be implemented here (google, mapbox, ...).
      return ['distance' => null, 'duration' => null, 'raw' => null, 'error' => 'Provider not implemented'];
    }
    catch (GuzzleException $e) {
      $this->logger->error('Driving Distance API request failed: @message', ['@message' => $e->getMessage()]);
      return ['distance' => null, 'duration' => null, 'raw' => null, 'error' => $e->getMessage()];
    }
  }

}
