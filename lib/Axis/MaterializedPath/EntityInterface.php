<?php
/**
 * Date: 07.12.13
 * Time: 23:57
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

namespace Axis\MaterializedPath;


interface EntityInterface
{
  /**
   * @return string
   */
  public function getTreeId();

  /**
   * @return string
   */
  public function getTreeType();
} 