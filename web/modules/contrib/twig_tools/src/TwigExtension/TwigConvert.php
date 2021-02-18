<?php

namespace Drupal\twig_tools\TwigExtension;

/**
 * Class TwigConvert.
 */
class TwigConvert extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('boolean', [$this, 'booleanValue']),
      new \Twig_SimpleFilter('integer', [$this, 'integerValue']),
      new \Twig_SimpleFilter('float', [$this, 'floatValue']),
      new \Twig_SimpleFilter('string', [$this, 'stringValue']),
      new \Twig_SimpleFilter('md5', [$this, 'md5Value']),
      new \Twig_SimpleFilter('json_decode', [$this, 'jsonDecode']),
      new \Twig_SimpleFilter('date_from_format', [$this, 'dateFromFormat']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_tools_convert.twig.extension';
  }

  /**
   * Returns the boolean value of a passed variable.
   *
   * @param mixed $value
   *   The variable to get the boolean equivalent value of.
   *
   * @return bool
   *   The boolean value equivalent of the variable.
   */
  public static function booleanValue($value) {
    return boolval($value);
  }

  /**
   * Returns the integer value of a passed variable.
   *
   * @param mixed $value
   *   The variable to get the integer equivalent value of.
   *
   * @return int
   *   The integer value equivalent of the variable.
   */
  public static function integerValue($value) {
    return intval($value);
  }

  /**
   * Returns the float value of a passed variable.
   *
   * @param mixed $value
   *   The variable to get the float equivalent value of.
   *
   * @return float
   *   The float value equivalent of the variable.
   */
  public static function floatValue($value) {
    return floatval($value);
  }

  /**
   * Returns the string value of a passed variable.
   *
   * @param mixed $value
   *   The variable to get the string equivalent value of.
   *
   * @return string
   *   The string value equivalent of the variable.
   */
  public static function stringValue($value) {
    return strval($value);
  }

  /**
   * Returns the md5 hash value of a passed variable.
   *
   * @param mixed $value
   *   The variable to get the md5 hash equivalent value of.
   *
   * @return string
   *   The md5 string hash value of the variable.
   */
  public static function md5Value($value) {
    return md5(strval($value));
  }

  /**
   * Decodes a JSON string into an object or array.
   *
   * @param string $value
   *   The JSON string to decode.
   * @param bool $assoc
   *   If TRUE, will convert JSON to an associative array instead of an object.
   *
   * @return array|object
   *   The object or array equivalent of the JSON string.
   */
  public static function jsonDecode($value, $assoc = FALSE) {
    return json_decode($value, $assoc);
  }

  /**
   * Converts a datetime string between different date formats.
   *
   * @param string $value
   *   A datetime string that matches the $from_format date format.
   * @param string $from_format
   *   A PHP datetime format string.
   * @param string $to_format
   *   A PHP datetime format string.
   * @param string|null $from_timezone
   *   The timezone identifier the datetime should be converted from.
   * @param string|null $to_timezone
   *   The timezone identifier the datetime should be converted to.
   *
   * @return string
   *   The datetime formatted according to the specific data format.
   */
  public static function dateFromFormat($value, $from_format, $to_format, $from_timezone = NULL, $to_timezone = NULL) {
    // Since a Unix timestamp can be 0 or '0', we need additional
    // empty/falsy checks.
    if (empty($value) && $value !== '0' && $value !== 0) {
      return '';
    }

    // Create a datetime object from the specified format.
    $converted_date = $from_timezone
    ? \DateTime::createFromFormat($from_format, $value, new \DateTimeZone($from_timezone))
    : \DateTime::createFromFormat($from_format, $value);

    // Convert datetime to other timezone if specified.
    if (isset($to_timezone)) {
      $converted_date = $converted_date->setTimezone(new \DateTimeZone($to_timezone));
    }

    // Return the datetime formatted in the specified format.
    return $converted_date->format($to_format);
  }

}
