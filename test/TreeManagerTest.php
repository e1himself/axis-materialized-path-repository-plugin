<?php

use Axis\MaterializedPath\Exception\PathHasChildrenException;
use Axis\MaterializedPath\Model\Entry;
use Axis\MaterializedPath\Model\EntryQuery;
use Axis\MaterializedPath\Position;
use Axis\MaterializedPath\TreeManager;
use Axis\MaterializedPath\Model\EntryPeer;

/**
 * Date: 11.12.13
 * Time: 2:50
 * Author: Ivan Voskoboynyk
 * Email: ioann.voskoboynyk@gmail.com
 */

class TreeManagerTest extends PHPUnit_Symfony_Model_TestCase
{
  protected function setUp()
  {
    parent::setUp();
    EntryPeer::doDeleteAll();
  }

  /**
   * @return TreeManager
   */
  protected function createTreeManager()
  {
    return new TreeManager('AxisDocument');
  }

  /**
   * @param TreeManager $tree
   * @param string[] $paths
   * @return AxisDocument[]
   */
  protected function createTree($tree, $paths)
  {
    $docs = [];
    foreach ($paths as $path)
    {
      $docs[$path] = $doc = new AxisDocument();
      $doc->save();
      $tree->put($path, $doc);
    }
    return $docs;
  }

  /**
   * @return EntryQuery
   */
  protected function createQuery()
  {
    return EntryQuery::create('AxisDocument');
  }

  public function testNotFound()
  {
    $tree = $this->createTreeManager();
    $this->setExpectedException('Axis\MaterializedPath\Exception\PathNotFoundException');
    $tree->get('/');
  }

  public function testPut()
  {
    $tree = $this->createTreeManager();
    $root = new AxisDocument();
    $root->save();

    $id = $root->getTreeId();
    $tree->put('/', $root);
    $root = $tree->get('/');

    $this->assertEquals($id, $root->getTreeId());

    $home = new AxisDocument();
    $home->save();
    $tree->put('/home', $home);

    $h1 = $tree->get('/home');
    $this->assertEquals($home->getId(), $h1->getTreeId());

    $h2 = $tree->get('/home/');
    $this->assertEquals($home->getId(), $h2->getTreeId());

    $newHome = new AxisDocument();
    $newHome->save();

    try
    {
      $tree->put('/home', $newHome);
      $this->assertTrue(false, 'Exception thrown');
    }
    catch (Axis\MaterializedPath\Exception\PathAlreadyExistsException $e)
    {
      $this->assertTrue(true, 'Exception thrown');
    }

    $tree->put('/home', $newHome, true);
    $h3 = $tree->get('/home');

    $this->assertEquals($newHome->getTreeId(), $h3->getTreeId());
  }

  public function testRemove()
  {
    $tree = $this->createTreeManager();
    $docs = $this->createTree($tree, ['/', '/home', '/home/io', '/media', '/media/home', '/media/home/io', '/mount']);

    $count = $this->createQuery()->count();
    $this->assertEquals(7, $count);

    try
    {
      $tree->remove('/home');
      $this->assertTrue(false, 'Exception thrown');
    }
    catch (PathHasChildrenException $e)
    {
      $this->assertTrue(true, 'Exception thrown');
    }

    $count = $this->createQuery()->count();
    $this->assertEquals(7, $count);

    $tree->remove('/home', true);

    $count = $this->createQuery()->count();
    $this->assertEquals(5, $count);

    $tree->remove('/media/home', true);

    $count = $this->createQuery()->count();
    $this->assertEquals(3, $count);

    $nodes = $this->createQuery()->find()->getArrayCopy();
    $entries = array_map(function($x) { /** @var $x Entry */ return $x->getPath(); }, $nodes);

//    sort($entries);

    $this->assertEquals(['/', '/media/', '/mount/'], $entries);
  }

  public function testChildren()
  {
    $x = ['/', '/home', '/home/io', '/media', '/media/home', '/media/home/io', '/mount'];
    $tree = $this->createTreeManager();
    $docs = $this->createTree($tree, $x);

    $count = $this->createQuery()->count();
    $this->assertEquals(7, $count);

    $count = $tree->countChildren('/home');
    $this->assertEquals(1, $count);

    //
    $children = $tree->getChildren('/home');
    $this->assertEquals(1, count($children));

    $this->assertEquals(['/home/io/'], array_keys($children));

    //
    $count = $tree->countChildren('/');
    $this->assertEquals(3, $count);

    //
    $children = $tree->getChildren('/');
    $this->assertEquals(3, count($children));

    $paths = array_keys($children);
//    sort($paths);

    $this->assertEquals(['/home/', '/media/', '/mount/'], $paths);

    //
    $descendants = $tree->getDescendants('/');
    $this->assertEquals(6, count($descendants));

    $paths = array_keys($descendants);
    sort($paths);

    $this->assertEquals(['/home/', '/home/io/', '/media/', '/media/home/', '/media/home/io/', '/mount/'], $paths);
  }

  public function testSiblings()
  {
    $x = ['/', '/home', '/home/io', '/media', '/media/home', '/media/home/io', '/mount'];
    $tree = $this->createTreeManager();
    $docs = $this->createTree($tree, $x);

    $siblings = $tree->countSiblings('/');
    $this->assertEquals(1, $siblings);

    $siblings = $tree->countSiblings('/home');
    $this->assertEquals(3, $siblings);

    $siblings = $tree->getSiblings('/home');
    $this->assertEquals(3, count($siblings));

    $paths = array_keys($siblings);
//    sort($paths);

    $this->assertEquals(['/home/', '/media/', '/mount/'], $paths);
  }

  public function testOrder()
  {
    $tree = $this->createTreeManager();

    $root = new AxisDocument();
    $root->save();

    $tree->put('/', $root);

    $home = new AxisDocument();
    $home->save();
    $tree->put('/home', $home);

    $x = new AxisDocument();
    $x->save();
    $tree->put('/x', $x, Position::FIRST);

    $children = $tree->getChildren('/');
    $paths = array_keys($children);

    $this->assertEquals(['/x/', '/home/'], $paths);
  }

  protected function tearDown()
  {
    parent::tearDown();
//    EntryPeer::doDeleteAll();
  }
}