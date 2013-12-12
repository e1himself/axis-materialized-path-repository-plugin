<?php
/**
 * Date: 07.12.13
 * Time: 23:37
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

namespace Axis\MaterializedPath;

use Axis\MaterializedPath\Exception\PathAlreadyExistsException;
use Axis\MaterializedPath\Exception\PathHasChildrenException;
use Axis\MaterializedPath\Exception\PathNotFoundException;
use Axis\MaterializedPath\Internal\PathUtilsTrait;
use Axis\MaterializedPath\Model\Entry;
use Axis\MaterializedPath\Model\EntryPeer;
use Axis\MaterializedPath\Model\EntryQuery;

class TreeManager
{
  use PathUtilsTrait;

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @param string $entityType
   */
  public function __construct($entityType)
  {
    $this->entityType = $entityType;
  }

  /**
   * @param string $path
   * @param EntityInterface $entity
   * @param bool $overwrite
   * @throws Exception\PathNotFoundException
   * @throws Exception\PathAlreadyExistsException
   */
  public function put($path, $entity, $overwrite = false)
  {
    $path = $this->_normalize($path);

    $e = EntryPeer::retrieveByPath($path);
    if ($e && !$overwrite)
    {
      throw new PathAlreadyExistsException($path);
    } elseif (!$e)
    {
      if ($path != '/' && !EntryPeer::doesPathExist($parent = $this->_getParentPath($path)))
      {
        throw new PathNotFoundException($parent);
      }

      $e = new Entry();
      $e->setPath($path);
      $e->setSlug(basename($path));
    }

    $e->setEntityId($entity->getTreeId());
    $e->setEntityType($this->entityType);
    $e->save();
  }

  /**
   * @param string $path
   * @throws PathNotFoundException
   * @return EntityInterface
   */
  public function get($path)
  {
    if ($e = EntryPeer::retrieveByPath($path))
    {
      $peer = constant($this->entityType.'::PEER');
      return $peer::retrieveByPk($e->getEntityId());
    }
    else
    {
      throw new PathNotFoundException("Path not found: $path");
    }
  }

  /**
   * @param $path
   * @param bool $includeSubTree
   * @throws PathHasChildrenException
   * @return array|mixed|\PropelObjectCollection
   */
  public function remove($path, $includeSubTree = false)
  {
    $path = $this->_normalize($path);

    $count = $this->countChildren($path);
    if ($count > 0 && $includeSubTree)
    {
      return EntryQuery::create()
        ->filterByPath($path.'%', \Criteria::LIKE)
        ->delete();
    }
    elseif ($count > 0)
    {
      throw new PathHasChildrenException('Cannot remove node: there are children nodes');
    }
    else
    {
      $con = \Propel::getConnection();
      $con->beginTransaction();
      {
        $removed = EntryQuery::create()
          ->filterByPath($path.'_', \Criteria::LIKE)
          ->delete();
        if ($removed > 1)
        {
          $con->rollBack();
          throw new PathHasChildrenException('Aborting. There were children nodes');
        }
      }
      $con->commit();
      return $removed;
    }
  }

  /**
   * @return array
   */
  public function getAll()
  {
    return $this->_getBranch();
  }

  /**
   * @param string $path
   * @return array
   */
  public function getBranch($path)
  {
    return $this->_getBranch($this->_normalize($path));
  }

  /**
   * @param string $path
   * @return array
   */
  public function getChildren($path)
  {
    return $this->_getBranch($this->_normalize($path).'_%');
  }

  /**
   * @param string $path
   * @return int
   */
  public function countChildren($path)
  {
    return $this->_countBranch($this->_normalize($path).'_%');
  }

  /**
   * @param string $path Path SQL wildcard
   * @return array Map [path => entity]
   */
  public function _getBranch($path = null)
  {
    /** @var EntryQuery $q */
    $q = EntryQuery::create();
    $q->orderByPath();
    $q->orderByOrderNumber();

    if ($path)
    {
      $q->filterByPath($path, \Criteria::LIKE);
    }

    $nodes = $q->find()->getArrayCopy();

    // map [id => object]
    $map = array_hash_by(call_each('getEntityId'), $nodes);
    // map [id => path]
    $map = array_map(call_each('getPath'), $map);

    $res = [];

    if (count($map))
    {
      $peer = constant($this->entityType.'::PEER');
      /** @var EntityInterface[] $objects */
      $objects = $peer::retrieveByPKs(array_keys($map));

      foreach ($objects as $object)
      {
        $res[$map[$object->getTreeId()]] = $object;
      }
    }

    return $res;
  }

  protected function _countBranch($path = null)
  {
    /** @var EntryQuery $q */
    $q = EntryQuery::create();

    if ($path)
    {
      $q->filterByPath($path, \Criteria::LIKE);
    }

    return $q->count();
  }
} 