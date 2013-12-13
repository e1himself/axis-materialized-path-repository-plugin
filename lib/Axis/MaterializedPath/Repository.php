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
   * @param string|EntityInterface $entity
   * @return TreeManager
   */
  public function getTreeManager($entity)
  {
    $name = is_string($entity) ? $entity : $entity->getTreeType();
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
      ->filterByEntityType($entity->getTreeType())
      ->filterByEntityId($entity->getTreeId())
      ->findOne();
  }

  /**
   * @param EntityInterface $entity
   * @return \PropelCollection|Entry[]
   */
  public function findAll($entity)
  {
    return EntryQuery::create()
      ->filterByEntityType($entity->getTreeType())
      ->filterByEntityId($entity->getTreeId())
      ->find();
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

  /**
   * @param EntityInterface $entity
   * @return array
   */
  public function getAllPaths($entity)
  {
    $es = $this->findAll($entity)->getArrayCopy();
    $ret = array_map(function($x) { /** @var $x Entry */ return $x->getPath(); }, $es);
    return $ret;
  }
}