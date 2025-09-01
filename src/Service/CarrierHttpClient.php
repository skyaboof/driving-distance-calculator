<?php

namespace Drupal\driving_distance_calculator\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Psr\Log\LoggerInterface;

class CarrierHttpClient {

  public function __construct(
    protected ClientInterface $http,
    protected CacheBackendInterface $cache,
    protected LoggerInterface $logger
  ) {}

  /**
   * Cached GET/POST helper with basic retry.
   *
   * @param string $cid Cache id key
   * @param callable $requestFn function(): \Psr\Http\Message\ResponseInterface
   * @param int $ttl TTL seconds
   */
  public function cachedRequest(string $cid, callable $requestFn, int $ttl = 600) {
    if ($cache = $this->cache->get($cid)) return $cache->data;

    $attempts = 0;
    $last = NULL;
    while ($attempts < 2) {
      try {
        $resp = $requestFn();
        $data = json_decode((string) $resp->getBody(), TRUE);
        $this->cache->set($cid, $data, time() + $ttl);
        return $data;
      } catch (\Throwable $e) {
        $last = $e;
        $attempts++;
        usleep(150000);
      }
    }
    $this->logger->error('Carrier request failed: @msg', ['@msg' => $last?->getMessage()]);
    return NULL;
  }

}
