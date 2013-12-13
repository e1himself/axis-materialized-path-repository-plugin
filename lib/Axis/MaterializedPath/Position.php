<?php
/**
 * Date: 13.12.13
 * Time: 1:10
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

namespace Axis\MaterializedPath;

class Position
{
  const FIRST = 'first';
  const LAST = 'last';
  const AFTER = 'after';
  const BEFORE = 'before';

  /** @var string */
  protected $type;

  /** @var string */
  protected $pivot;

  /**
   * @param string $type
   * @param string|null $pivotPath
   */
  function __construct($type, $pivotPath = null)
  {
    $this->type = $type;
    $this->pivot = $pivotPath;
  }

  /**
   * @return Position
   */
  public static function last()
  {
    return new self(self::LAST);
  }

  /**
   * @return Position
   */
  public static function first()
  {
    return new self(self::FIRST);
  }

  /**
   * @param string $pivotPath
   * @return Position
   */
  public static function after($pivotPath)
  {
    return new self(self::AFTER, $pivotPath);
  }

  /**
   * @param string $pivotPath
   * @return Position
   */
  public static function before($pivotPath)
  {
    return new self(self::BEFORE, $pivotPath);
  }

  /**
   * @return string
   */
  public function getPivot()
  {
    return $this->pivot;
  }

  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}