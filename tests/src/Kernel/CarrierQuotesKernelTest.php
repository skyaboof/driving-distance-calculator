<?php

namespace Drupal\Tests\driving_distance_calculator\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel test for JSON price calculation route.
 *
 * @group driving_distance_calculator
 */
class PriceCalculatorJsonTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'driving_distance_calculator'];

  public function testJsonRouteAndHeaders(): void {
    $request = Request::create('/driving-distance-calculator/calculate', 'GET', [
      'distance' => 10,
      'minutes' => 20,
      'weight' => 5,
      'fragile' => 1,
    ]);
    $response = \Drupal::service('http_kernel')->handle($request);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type', ''));

    $cacheControl = (string) $response->headers->get('Cache-Control', '');
    $this->assertStringContainsString('no-store', $cacheControl);

    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame('success', $data['status']);
    $this->assertArrayHasKey('total_price', $data);
    $this->assertArrayHasKey('surcharge', $data);
    $this->assertArrayHasKey('conditions', $data);
  }

  public function testNegativeDistanceError(): void {
    $request = Request::create('/driving-distance-calculator/calculate', 'GET', [
      'distance' => -1,
      'minutes' => 10,
    ]);
    $response = \Drupal::service('http_kernel')->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame('error', $data['status']);
    $this->assertStringContainsString('distance', implode(' ', $data['errors']));
  }

}
