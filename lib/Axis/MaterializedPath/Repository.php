<?php
/**
 * Date: 07.12.13
 * Time: 23:26
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

namespace Axis\MaterializedPath;

class Repository
{
  protected $trees = [];

  /**
   * @param string|EntityInterface $entity
   * @return TreeManager
   */
  public function getTreeManager($entity)
  {
    if ($entity instanceof EntityInterface)
    {
      $name = $entity->getTreeName();
    }
    else
    {
      $name = (string)$entity;
    }

    if (!isset($this->trees[$name]))
    {
      $this->trees[$name] = new TreeManager($name);
    }
    return $this->trees[$name];
  }
}