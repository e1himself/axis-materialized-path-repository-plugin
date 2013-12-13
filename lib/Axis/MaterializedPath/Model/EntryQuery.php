<?php

namespace Axis\MaterializedPath\Model;

use Axis\MaterializedPath\EntityInterface;
use Axis\MaterializedPath\Internal\PathUtilsTrait;
use Axis\MaterializedPath\Model\om\BaseEntryQuery;
use Criteria;

/**
 * @package    propel.generator.plugins.AxisMaterializedPathRepositoryPlugin.lib.Axis.MaterializedPath.Model
 */
class EntryQuery extends BaseEntryQuery
{
  use PathUtilsTrait;

  /** @var string */
  protected $entityType;

  /**
   * @param string $entityType
   */
  public function setEntityType($entityType)
  {
    $this->filterByEntityType($entityType);
    $this->entityType = $entityType;
  }

  /**
   * @return string
   */
  public function getEntityType()
  {
    return $this->entityType;
  }

  /**
   * Returns a new EntryQuery object.
   *
   * @param string $entityType
   * @param     string $modelAlias The alias of a model in the query
   * @param   EntryQuery|Criteria $criteria Optional Criteria to build the query from
   *
   * @return EntryQuery
   */
  public static function create($entityType = null, $modelAlias = null, $criteria = null)
  {
    if ($criteria instanceof EntryQuery) {
      if ($entityType)
        $criteria->setEntityType($entityType); // added this
      return $criteria;
    }
    $query = new EntryQuery(null, null, $modelAlias);
    if ($entityType)
      $query->setEntityType($entityType); // and this

    if ($criteria instanceof Criteria) {
      $query->mergeWith($criteria);
    }

    return $query;
  }

  /**
   * @return $this
   */
  public function treeOrder()
  {
    $this->orderByLevel();
    $this->orderByOrderNumber();
    $this->orderByPath();
    return $this;
  }

  /**
   * @param string $path
   * @return $this
   */
  public function descendantsOf($path)
  {
    $p = $this->_normalize($path);
    $this->filterByPath($p.'_%', \Criteria::LIKE);
    $this->filterByLevel($this->_calcLevel($p), \Criteria::GREATER_THAN);
    return $this;
  }

  /**
   * @param string $path
   * @return $this
   */
  public function branchOf($path)
  {
    $p = $this->_normalize($path);
    $this->filterByPath($p.'%', \Criteria::LIKE);
    $this->filterByLevel($this->_calcLevel($p), \Criteria::GREATER_EQUAL);
    return $this;
  }

  /**
   * @param string $path
   * @return $this
   */
  public function childrenOf($path)
  {
    $p = $this->_normalize($path);
    $this->filterByPath($p.'%', \Criteria::LIKE);
    $this->filterByLevel($this->_calcLevel($p) + 1);
    return $this;
  }

  /**
   * @param string $path
   * @param bool $excludeSelf
   * @return $this
   */
  public function siblingsOf($path, $excludeSelf = false)
  {
    $p = $this->_normalize($path);

    if ($p == '/')
    {
      if ($excludeSelf)
      {
        $this->where('1 = 0'); // exclude all
      }
      else
      {
        $this->filterByPath($path);
      }
    }
    else
    {
      $parent = $this->_getParentPath($p);

      $this->filterByPath($parent.'_%', \Criteria::LIKE);
      $this->filterByLevel($this->_calcLevel($p));

      if ($excludeSelf)
      {
        $this->where('path != ?', $p);
      }
    }

    return $this;
  }

  /**
   * @param string $path
   * @return $this
   */
  public function ancestorsOf($path)
  {
    $p = $this->_normalize($path);
    $this->where('? LIKE CONCAT(path,"%")', $path); // $path LIKE "{entry.path}_%"
    $this->filterByLevel($this->_calcLevel($p), \Criteria::LESS_THAN);
    return $this;
  }

  /**
   * @param string $path
   * @return $this
   */
  public function parentOf($path)
  {
    $p = $this->_normalize($path);
    $this->where('? LIKE CONCAT(path,"%")', $path); // $path LIKE "{entry.path}_%"
    $this->filterByLevel($this->_calcLevel($p) - 1);
    return $this;
  }

  /**
   * @throws \LogicException
   * @return array
   */
  public function retrieveTree()
  {
    if (empty($this->entityType))
    {
      throw new \LogicException('Please specify EntityType');
    }

    $nodes = $this->find()->getArrayCopy('EntityId');
    // map [id => path]
    $map = array_map( function($x) { /** @var Entry $x */ return $x->getPath(); }, $nodes);

    $res = [];

    if (count($map))
    {
      $peer = constant($this->entityType.'::PEER');
      $objects = [];
      foreach ($peer::retrieveByPKs(array_keys($map)) as $o)
      {
        /** @var EntityInterface $o */
        $objects[$o->getTreeId()] = $o;
      }

      foreach ($map as $id => $path)
      {
        $res[$path] = isset($objects[$id]) ? $objects[$id] : null;
      }
    }

    return $res;
  }
}
