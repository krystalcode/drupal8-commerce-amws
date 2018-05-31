<?php

namespace Drupal\commerce_amws;

/**
 * Wrapper class for utility functions.
 */
class Utilities {

  /**
   * Merges two or more arrays recursively.
   *
   * Similarly to `array_merge`, it merges the elements of two or more arrays
   * together so that the values of one are appended to the end of the previous
   * one.
   *
   * If the input arrays have the same string keys, then the later value
   * for that key will overwrite the previous one.
   *
   * If, however, the arrays contain numeric keys, the later value will not
   * overwrite the original value, but will be appended. Values in the input
   * array with numeric keys will be renumbered with incrementing keys starting
   * from zero in the result array.
   *
   * If the input arrays contain array values, they will be merged recursively
   * with the rules described.
   *
   * @param array $array1
   *   The first array to merge.
   * @param array $array2
   *   The second array to merge.
   *
   * @return array
   *   The resulting array.
   */
  public static function arrayMergeRecursive(array $array1, array $array2) {
    // Get all input arrays. We still use arguments in the function declaration
    // so that we get type casting and minimum number of arguments without
    // writing additional code.
    $arrays = func_get_args();

    // No  need to loop over the first array, just copy it.
    $merged = array_shift($arrays);

    while ($arrays) {
      $array = array_shift($arrays);
      if (!is_array($array)) {
        throw new \InvalidArgumentException(
          sprintf(
            'All arguments must be arrays for merging them recursively, "%s" given.',
            json_encode($array)
          )
        );
      }

      // Loop over all array items. If we have a string key and array values in
      // both arrays, merge the values recursively. Otherwise, append the value
      // if the key is integer or replace the value if the key is string.
      foreach ($array as $key => $value) {
        if (is_string($key)) {
          if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = self::arrayMergeRecursive($merged[$key], $value);
            continue;
          }

          $merged[$key] = $value;
        }
        else {
          $merged[] = $value;
        }
      }
    }

    return $merged;
  }

}
