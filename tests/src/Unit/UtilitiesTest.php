<?php

namespace Drupal\Tests\commerce_amws\Unit;

use Drupal\commerce_amws\Utilities;

use Drupal\Tests\UnitTestCase;

/**
 * Class UtilitiesTest.
 *
 * Tests the Utilities functions.
 *
 * @coversDefaultClass \Drupal\commerce_amws\Utilities
 * @group commerce_amws
 * @package Drupal\Tests\commerce_amws\Unit
 */
class UtilitiesTest extends UnitTestCase {

  /**
   * @covers ::arrayMergeRecursive
   */
  public function testArrayMergeRecursive() {
    // Test a straight-forward array merge.
    $array1 = [
      'a' => 'red',
      'b' => 'green',
    ];
    $array2 = [
      'c' => 'blue',
      'd' => 'yellow',
    ];


    $expected_array = [
      'a' => 'red',
      'b' => 'green',
      'c' => 'blue',
      'd' => 'yellow',
    ];
    $this->assertEquals($expected_array, Utilities::arrayMergeRecursive($array1, $array2));

    // Test one where there is a duplicate for the same key.
    $array2 = [
      'c' => 'blue',
      'b' => 'yellow',
    ];


    $expected_array = [
      'a' => 'red',
      'b' => 'yellow',
      'c' => 'blue',
    ];
    $this->assertEquals($expected_array, Utilities::arrayMergeRecursive($array1, $array2));
  }

  /**
   * @covers ::amwsPriceToDrupalPrice
   */
  public function testAmwsPriceToDrupalPrice() {
    // Confirm test will fail with a float value.
    $amws_price = [
      'Amount' => 150.99,
      'CurrencyCode' => 'USD',
    ];
    try {
      $message = 'The provided value "150.99" must be a string, not a float.';
      Utilities::amwsPriceToDrupalPrice($amws_price);

      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->assertTrue(TRUE, $message);
    }

    // Confirm test will pass.
    $amws_price = [
      'Amount' => '150.99',
      'CurrencyCode' => 'USD',
    ];
    $expected_result = '150.99 USD';
    $this->assertEquals($expected_result, Utilities::amwsPriceToDrupalPrice($amws_price));
  }

}
