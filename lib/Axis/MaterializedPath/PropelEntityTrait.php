<?php
/**
 * Date: 11.12.13
 * Time: 2:05
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

namespace Axis\MaterializedPath;


trait PropelEntityTrait
{
  /**
   * @return mixed
   */
  abstract function getPrimaryKey();

  /**
   * @return mixed
   */
  abstract function getPeer();

  /**
   * @return string
   */
  public function getTreeId()
  {
    return (string)$this->getPrimaryKey();
  }

  /**
   * @return string
   */
  public function getTreeKey()
  {
    return $this->getPeer()->getOmClass();
  }
} 