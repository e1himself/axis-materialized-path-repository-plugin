<?php

namespace Axis\MaterializedPath\Model;

use Axis\MaterializedPath\Internal\PathUtilsTrait;
use Axis\MaterializedPath\Model\om\BaseEntry;
use PropelPDO;


/**
 * @package    propel.generator.plugins.AxisMaterializedPathRepositoryPlugin.lib.Axis.MaterializedPath.Model
 */
class Entry extends BaseEntry
{
  public function setEntityId($v)
  {
    if ($v == null)
    {
      throw new \InvalidArgumentException('Entity ID cannot be null');
    }
    return parent::setEntityId($v);
  }

  /**
   * @param string $v
   * @return Entry
   * @throws \InvalidArgumentException
   */
  public function setPath($v)
  {
    if ($v && strpos($v, '//') !== FALSE)
    {
      throw new \InvalidArgumentException("Path cannot contain two // in a row ($v)");
    }
    return parent::setPath($v);
  }

  /**
   * @param string $v
   * @return Entry
   * @throws \InvalidArgumentException
   */
  public function setSlug($v)
  {
    if ($v && (strpos('/', $v) !== FALSE || strpos('*', $v) !== FALSE || strpos('%', $v) !== FALSE))
    {
      throw new \InvalidArgumentException('Disallowed symbol in path. You cannot use /, * and % in path.');
    }
    return parent::setSlug($v);
  }

  public function preSave(PropelPDO $con = null)
  {
    $this->setLevel(substr_count($this->getPath(),'/'));
    return parent::preSave($con);
  }
}
