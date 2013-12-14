AxisMaterializedPathRepository Plugin
=====================================

Symfony1 plugin to implement materialized path repository for storing hierarchical data

Requirements
------------

1. Symfony 1.4
2. Propel ORM Plugin 1.6
3. PHP 5.4+

Installation
------------

1. Add requirement to your `composer.json`:

~~~ json
"require": {
  "e1stuff/axis-materialized-path-repository-plugin": "0.1.*"
}
~~~

2. Build model by running `php symfony propel:build-model`
3. Update your database schema (using propel migration or manually)
4. Enable plugin in `ProjectConfigiration`

Usage
-----

Assume you have entity in your schema that need to be stored hierarchically:

~~~ yaml

propel:
  document:
    id: ~
    title: ~
    created_at: ~
    author_id: { type: int }

~~~

You do not need to modify your schema to organize your objects into tree.

Implement `getTreeId()` and `getTreeType()` methods in your entity:

~~~ php

class Document 
{
  public function getTreeId()
  {
    return (string)$this->getPrimaryKey();
  }
  
  public function getTreeType()
  {
    return __CLASS__;
  }
}

~~~

Tree control:

~~~ php

// create a tree manager
$tree = new TreeManager('Document'); // Document - your entity name

$root = new Document();
$root->setTitle('Homepage');
$root->save(); // note that entity should have ID before putting into 
$tree->put('/', $root);

$contacts = new Document();
$contacts->setTitle('Contact us');
$contacts->save();
$tree->put('/contact-us/', $contacts);

$about = new Document();
$about->setTitle('About us');
$about->save();
$tree->put('/about-us/');

// ...

$doc = $tree->get('/contact-us/');
echo $doc->getTitle(); // Contact us

$children = $tree->getChildren('/'); // [$contacts, $about]


$tree->move('/about/', '/our-company/', Position::before($contacts));
echo $tree->get('/our-company/')->getTitle(); // About us

$children = $tree->getChildren('/'); // [$about, $contacts]

~~~

API
---

See public methods of TreeManager class.

- `put($path, $entity)` - put an entity link into tree
- `move($path, $newPath, $position = null)` - move entity link to another empty location
- `reorder($path, $position)` - reorder entity link without changing hierarchy
- `get($path)` - get entity associated with path
- `remove($path)` - remove path
- `getAll()` - return all tree entities
- `getBranch($path)` - returns all entities of a subtree (including subroot) (map \[path => entity\])
- `getSiblings($path)` - return all entities within same parent (map \[path => entity\])
- `countSiblings($path)` - return number of siblings
- `getChildren($path)` - return all direct children (map \[path => entity\])
- `countChildren($path)` - return number of direct children
- `getAncestors($path)` - return all ancestors (map \[path => entity\])
- `getLevel($path)` - return level of path
- `getDescendants($path)` - return all descendants (map \[path => entity\])
- `countDescendants($path)` - return number of all descendants
