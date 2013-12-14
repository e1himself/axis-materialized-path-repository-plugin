<?php

namespace Axis\MaterializedPath\Model;

use Axis\MaterializedPath\Internal\PathUtilsTrait;
use Axis\MaterializedPath\Model\om\BaseEntryPeer;

/**
 * @package    propel.generator.plugins.AxisMaterializedPathRepositoryPlugin.lib.Axis.MaterializedPath.Model
 */
class EntryPeer extends BaseEntryPeer
{
  use PathUtilsTrait;

  /**
   * @param Entry $obj
   * @param string|null $key
   */
  public static function addInstanceToPool($obj, $key = null)
  {
    if (\Propel::isInstancePoolingEnabled()) {
      $path = $obj->getPath();
      $pathKey = self::_pathKey($obj->getEntityType(), $path);
      EntryPeer::$instances[$pathKey] = $obj;
      parent::addInstanceToPool($obj, $key);
    }
  }

  /**
   * @param string $entityType
   * @param $path
   * @return Entry
   */
  public static function retrieveByPath($entityType, $path)
  {
    $path = static::_normalize($path);
    $pathKey = self::_pathKey($entityType, $path);

    if (\Propel::isInstancePoolingEnabled() && $obj = static::getInstanceFromPool($pathKey))
    {
      return $obj;
    }

    $obj = EntryQuery::create($entityType)
      ->filterByPath($path)
      ->findOne();

    return $obj;
  }

  /**
   * @param string $entityType
   * @param string $path
   * @return bool
   */
  public static function doesPathExist($entityType, $path)
  {
    $path = static::_normalize($path);
    $pathKey = self::_pathKey($entityType, $path);
    if (\Propel::isInstancePoolingEnabled() && $obj = static::getInstanceFromPool($pathKey))
    {
      return true;
    }

    $count = EntryQuery::create($entityType)
      ->filterByPath($path)
      ->count();

    return $count > 0;
  }

  /**
   * @param string $entityType
   * @param string $path
   * @return string
   */
  protected static function _pathKey($entityType, $path)
  {
    return '$'.$entityType.':'.$path;
  }

  /**
   * @param string $path
   * @return null|string Parent path or null for root
   */
  public static function parentPath($path)
  {
    return self::_normalize($path) != '/' ? self::_getParentPath($path) : null;
  }
}
