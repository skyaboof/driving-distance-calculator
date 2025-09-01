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

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'driving_distance_calculator',
  ];

  /**
   * Tests JSON route returns expected structure.
   */
  public function testJsonRoute(): void {
    $request = Request::create('/driving-distance-calculator/calculate', 'GET', [
      'distance' => 10,
      'minutes' => 20,
      'weight' => 5,
      'fragile' => 1,
    ]);
    $response = \Drupal::service('http_kernel')->handle($request);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

    $data = json_decode($response->getContent(), TRUE);
    $this->assertIsArray($data);
    $this->assertSame('success', $data['status']);
    $this->assertArrayHasKey('total_price', $data);
    $this->assertArrayHasKey('surcharge', $data);
    $this->assertArrayHasKey('conditions', $data);
  }

}
