AxisMaterializedPathRepository Plugin
=====================================

Symfony1 plugin to implement materialized path repository for storing hierarchycal data

Installation
------------

// TODO: Add `composer.json` package info

Usage
-----

Assume you have entity in your schema that need to be stored hyerarchycally:

~~~ yaml
propel:
  document:
    id: ~
    title: ~
    created_at: ~
    author_id: { type: int }
~~~

You do not need to modify your schema to organize your objects into tree.

Implement `getTreeId()` method in your entity:

~~~ php

class Document 
{
  public function getTreeId()
  {
    return (string)$this->getPrimaryKey();
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
$tree->put('/contact-us', $contacts);

$about = new Document();
$about->setTitle('About us');
$about->save();
$tree->put('/about-us');

// ...

$doc = $tree->get('/contact-us');
echo $doc->getTitle(); // Contact us

$children = $tree->getChildren('/'); // [$contacts, $about]

~~~

Roadmap
-------

1. Improve tree control API:
  # move subtree
  # get parents
  # get siblings
  # get descendants
  # get ancestors

2. Allow siblings ordering (use `order_number` column)
