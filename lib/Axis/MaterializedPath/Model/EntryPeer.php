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
   * @param null $key
   */
  public static function addInstanceToPool($obj, $key = null)
  {
    if (\Propel::isInstancePoolingEnabled()) {
      $path = $obj->getPath();
      EntryPeer::$instances['$path:'.$path] = $obj;
    }
  }

  /**
   * @param $path
   * @return Entry
   */
  public static function retrieveByPath($path)
  {
    $path = static::_normalize($path);

    if (\Propel::isInstancePoolingEnabled() && $obj = static::getInstanceFromPool('$path:'.$path))
    {
      return $obj;
    }

    $obj = EntryQuery::create()
      ->filterByPath($path)
      ->findOne();

    return $obj;
  }

  /**
   * @param string $path
   * @return bool
   */
  public static function doesPathExist($path)
  {
    $path = static::_normalize($path);

    if (\Propel::isInstancePoolingEnabled() && $obj = static::getInstanceFromPool('$path:'.$path))
    {
      return true;
    }

    $count = EntryQuery::create()
      ->filterByPath($path)
      ->count();

    return $count > 0;
  }
}
