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
   * @param bool|Position|string $overwrite Flag to overwrite existing records (or $position)
   * @param Position|null $position
   * @throws Exception\PathNotFoundException
   * @throws Exception\PathAlreadyExistsException
   */
  public function put($path, $entity, $overwrite = false, $position = null)
  {
    $connection = \Propel::getConnection();
    $connection->beginTransaction();

    $path = $this->_normalize($path);

    if ($position === null && (is_string($overwrite) || $overwrite instanceof Position))
    {
      $position = $overwrite;
      $overwrite = false;
    }

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

    $orderNumber = $this->_handleOrder($path, $position ?: Position::LAST);

    $e->setOrderNumber($orderNumber);
    $e->setEntityId($entity->getTreeId());
    $e->setEntityType($this->entityType);
    $e->save();

    $connection->commit();
  }

  /**
   * @param string $path
   * @param string|Position $position
   * @throws Exception\PathNotFoundException
   * @throws \InvalidArgumentException
   * @return int
   */
  protected function _handleOrder($path, $position)
  {
    if ($position || is_string($position))
    {
      $position = new Position($position);
    }

    switch ($position->getType())
    {
      case 'first':
        $q = EntryQuery::create($this->entityType)
          ->siblingsOf($path)
          ->treeOrder();
        $this->_reorder($q, 2);
        return 1;

      case 'before':
        if ($e = EntryPeer::retrieveByPath($position->getPivot()))
        {
          $orderNumber = $e->getOrderNumber();
          $q = EntryQuery::create($this->entityType)
            ->siblingsOf($path)
            ->filterByOrderNumber($orderNumber, \Criteria::GREATER_EQUAL)
            ->treeOrder();
          $this->_reorder($q, $orderNumber + 1);
          return $orderNumber;
        }
        else
        {
          throw new PathNotFoundException($position->getPivot());
        }
      case 'after':
        if ($e = EntryPeer::retrieveByPath($position->getPivot()))
        {
          $orderNumber = $e->getOrderNumber() + 1;
          $q = EntryQuery::create($this->entityType)
            ->siblingsOf($path)
            ->filterByOrderNumber($orderNumber, \Criteria::GREATER_EQUAL)
            ->treeOrder();
          $this->_reorder($q, $orderNumber + 1);
          return $orderNumber;
        }
        else
        {
          throw new PathNotFoundException($position->getPivot());
        }
      case 'last':
        $max_taken = EntryQuery::create($this->entityType)
          ->siblingsOf($path)
          ->addAsColumn('max_taken', 'MAX(order_number)')
          ->select(['max_taken'])
          ->findOne();
        return $max_taken + 1;

      default:
        throw new \InvalidArgumentException('Unsupported position');
    }
  }

  /**
   * @param EntryQuery $query
   * @param int $startFrom
   * @return int
   */
  protected function _reorder($query, $startFrom)
  {
    $max_taken = $startFrom;

    /** @var Entry[] $siblings */
    $siblings = $query->find()->getArrayCopy();

    foreach ($siblings as $sibling)
    {
      $sibling->setOrderNumber($max_taken);
      $sibling->save();
      $max_taken++;
    }

    return count($siblings);
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

    if ($includeSubTree)
    {
      return EntryQuery::create($this->entityType)
        ->branchOf($path)
        ->delete();
    }

    $count = $this->countChildren($path);
    if ($count > 0)
    {
      throw new PathHasChildrenException('Cannot remove node: there are children nodes');
    }
    else
    {
      $con = \Propel::getConnection();
      $con->beginTransaction();
      {
        $removed = EntryQuery::create($this->entityType)
          ->branchOf($path)
          ->delete();
        if ($removed > 1)
        {
          $con->rollBack();
          throw new PathHasChildrenException('Rolling back. There were children nodes');
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
    return EntryQuery::create($this->entityType)->treeOrder()->retrieveTree();
  }

  /**
   * @param string $path
   * @return array
   */
  public function getBranch($path)
  {
    return EntryQuery::create($this->entityType)->branchOf($path)->treeOrder()->retrieveTree();
  }

  /**
   * @param string $path
   * @param bool $excludeSelf
   * @return array
   */
  public function getSiblings($path, $excludeSelf = false)
  {
    return EntryQuery::create($this->entityType)->siblingsOf($path, $excludeSelf)->treeOrder()->retrieveTree();
  }

  /**
   * @param $path
   * @param bool $excludeSelf
   * @return int
   */
  public function countSiblings($path, $excludeSelf = false)
  {
    $x = $excludeSelf ? -1 : 0;
    return EntryQuery::create($this->entityType)->siblingsOf($path)->count() + $x;
  }

  /**
   * @param string $path
   * @return array
   */
  public function getChildren($path)
  {
    return EntryQuery::create($this->entityType)->childrenOf($path)->treeOrder()->retrieveTree();
  }

  /**
   * @param string $path
   * @return int
   */
  public function countChildren($path)
  {
    return EntryQuery::create($this->entityType)->childrenOf($path)->count();
  }

  /**
   * @param string $path
   * @return array
   */
  public function getAncestors($path)
  {
    return EntryQuery::create($this->entityType)->ancestorsOf($path)->treeOrder()->retrieveTree();
  }

  /**
   * @param string $path
   * @return array
   */
  public function getDescendants($path)
  {
    return EntryQuery::create($this->entityType)->descendantsOf($path)->treeOrder()->retrieveTree();
  }

  /**
   * @param string $path
   * @return int
   */
  public function countDescendants($path)
  {
    return EntryQuery::create($this->entityType)->descendantsOf($path)->count();
  }
} 