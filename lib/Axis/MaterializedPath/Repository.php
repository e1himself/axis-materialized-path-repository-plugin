<?php
/**
 * Date: 07.12.13
 * Time: 23:26
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

namespace Axis\MaterializedPath;

use Axis\MaterializedPath\Model\Entry;
use Axis\MaterializedPath\Model\EntryQuery;

class Repository
{
  protected $trees = [];

  /**
   * @param string $name
   * @return TreeManager
   */
  public function getTreeManager($name)
  {
    if (!isset($this->trees[$name]))
    {
      $this->trees[$name] = new TreeManager($name);
    }
    return $this->trees[$name];
  }

  /**
   * @param EntityInterface $entity
   * @return Entry
   */
  public function find($entity)
  {
    return EntryQuery::create()
      ->filterByEntityType($entity->getOmType())
      ->filterByEntityId($entity->getTreeId())
      ->findOne();
  }

  /**
   * @param EntityInterface $entity
   * @return null|string
   */
  public function getPath($entity)
  {
    $e = $this->find($entity);
    return $e ? $e->getPath() : null;
  }
}