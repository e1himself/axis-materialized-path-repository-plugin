<?php
/**
 * Date: 12.12.13
 * Time: 1:17
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

namespace Axis\MaterializedPath\Internal;

trait PathUtilsTrait {
  /**
   * @param string $path Normalized path
   * @return string
   */
  protected static function _getParentPath($path)
  {
    $pos = strrpos($path, '/', -2);
    return $pos ? substr($path, 0, $pos+1) : '/';
  }

  /**
   * @param string $path
   * @param bool $allowWildcards
   * @throws \InvalidArgumentException
   * @return string
   */
  protected static function _normalize($path, $allowWildcards = false)
  {
    if (!$allowWildcards && (strpos($path, '%') !== FALSE || strpos($path, '*') !== FALSE))
    {
      throw new \InvalidArgumentException('Wildcards are not allowed here.');
    }
    $path = trim($path,'/');
    return $path ? "/$path/" : '/';
  }

  /**
   * @param string $path Normalized path
   * @return int
   */
  protected function _calcLevel($path)
  {
    return substr_count($path, '/');
  }
} 