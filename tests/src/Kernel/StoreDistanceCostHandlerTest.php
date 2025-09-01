<?php

namespace Drupal\Tests\driving_distance_calculator\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;

/**
 * Tests the StoreDistanceCostWebformHandler with mocked Distance Matrix API.
 *
 * @group driving_distance_calculator
 */
class StoreDistanceCostHandlerTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'webform',
    'driving_distance_calculator',
  ];

  protected function setUp(): void {
    parent::setUp();
    \Drupal::configFactory()->getEditable('driving_distance_calculator.settings')
      ->set('google_api_key', 'test-key')
      ->save();
  }

  protected function createTestWebform(): Webform {
    $webform = Webform::create([
      'id' => 'test_distance_cost',
      'title' => 'Test Distance Cost',
      'elements' => [
        'origin_address' => ['#type' => 'textfield'],
        'destination_address' => ['#type' => 'textfield'],
        'minutes' => ['#type' => 'number'],
        'weight' => ['#type' => 'number'],
        'fragile' => ['#type' => 'checkbox'],
        'calculated_cost' => ['#type' => 'number'],
        'calculated_distance' => ['#type' => 'number'],
      ],
      'handlers' => [
        'store_distance_cost' => ['id' => 'store_distance_cost'],
      ],
    ]);
    $webform->save();
    return $webform;
  }

  public function testServerSideLookupSuccess(): void {
    $mockResponse = new Response(200, [], json_encode([
      'rows' => [[ 'elements' => [[ 'status' => 'OK', 'distance' => ['value' => 5000] ]] ]],
    ]));
    $mockClient = $this->createMock(ClientInterface::class);
    $mockClient->method('get')->willReturn($mockResponse);
    $this->container->set('http_client', $mockClient);

    $this->createTestWebform();

    $submission = WebformSubmission::create([
      'webform_id' => 'test_distance_cost',
      'data' => [
        'origin_address' => 'Address A',
        'destination_address' => 'Address B',
        'minutes' => 10,
        'weight' => 2,
        'fragile' => 1,
      ],
    ]);
    $submission->save();

    $submission = WebformSubmission::load($submission->id());
    $data = $submission->getData();

    $this->assertSame(5.0, $data['calculated_distance']);
    $this->assertGreaterThan(0, $data['calculated_cost']);
  }

  public function testServerSideLookupFailureValidation(): void {
    $mockResponse = new Response(200, [], json_encode([
      'rows' => [[ 'elements' => [[ 'status' => 'NOT_FOUND' ]] ]],
    ]));
    $mockClient = $this->createMock(ClientInterface::class);
    $mockClient->method('get')->willReturn($mockResponse);
    $this->container->set('http_client', $mockClient);

    $this->createTestWebform();

    $submission = WebformSubmission::create([
      'webform_id' => 'test_distance_cost',
      'data' => [
        'origin_address' => 'Bad Origin',
        'destination_address' => 'Bad Destination',
        'minutes' => 10,
      ],
    ]);

    $violations = $submission->validate();
    $this->assertNotEmpty($violations, 'Expected validation errors when API lookup fails.');
  }

}
