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

  /** @var EntityInterface */
  protected $pivot;

  /**
   * @param string $type
   * @param EntityInterface|null $pivot
   */
  function __construct($type, $pivot = null)
  {
    $this->type = $type;
    $this->pivot = $pivot;
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
   * @param EntityInterface $pivot
   * @return Position
   */
  public static function after($pivot)
  {
    return new self(self::AFTER, $pivot);
  }

  /**
   * @param EntityInterface $pivot
   * @return Position
   */
  public static function before($pivot)
  {
    return new self(self::BEFORE, $pivot);
  }

  /**
   * @return \Axis\MaterializedPath\EntityInterface
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