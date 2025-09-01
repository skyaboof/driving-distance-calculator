<?php

namespace Drupal\Tests\driving_distance_calculator\Unit;

use Drupal\driving_distance_calculator\Service\PriceCalculator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\driving_distance_calculator\Service\PriceCalculator
 * @group driving_distance_calculator
 */
class PriceCalculatorTest extends UnitTestCase {

  /**
   * @var \Drupal\driving_distance_calculator\Service\PriceCalculator
   */
  protected $calculator;

  protected function setUp(): void {
    parent::setUp();
    $this->calculator = new PriceCalculator();
  }

  public function testFlatModeCalculation() {
    $result = $this->calculator->calculate(10, 15, [
      'pricing_mode' => 'flat',
      'base_price' => 20,
      'fragile' => 1,
      'fragile_surcharge' => 5,
    ]);
    $this->assertEquals('success', $result['status']);
    $this->assertEquals(25, $result['total_price']);
  }

  public function testPerKmModeCalculation() {
    $result = $this->calculator->calculate(10, 0, [
      'pricing_mode' => 'per_km',
      'base_price' => 5,
      'rate_per_km' => 2,
    ]);
    $this->assertEquals(25, $result['total_price']); // 5 + (10*2)
  }

  public function testTieredModeCalculation() {
    $result = $this->calculator->calculate(50, 0, [
      'pricing_mode' => 'tiered',
      'tiers' => [
        ['min' => 0, 'max' => 20, 'rate' => 1],
        ['min' => 21, 'max' => 100, 'rate' => 0.5],
      ],
    ]);
    $this->assertEquals(25, $result['total_price']); // 20*1 + 30*0.5
  }

}
