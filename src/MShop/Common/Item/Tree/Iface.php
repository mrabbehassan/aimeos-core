<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 * @package MShop
 * @subpackage Common
 */


namespace Aimeos\MShop\Common\Item\Tree;


/**
 * Common interface for tree items
 *
 * @package MShop
 * @subpackage Common
 */
interface Iface extends \Aimeos\MShop\Common\Item\Status\Iface
{
	/**
	 * Returns the code of the item.
	 *
	 * @return string Code of the item
	 */
	public function getCode() : string;

	/**
	 * Sets the code of the item.
	 *
	 * @param string $name New code of the item
	 * @return static Item for chaining method calls
	 */
	public function setCode( string $name ) : static;

	/**
	 * Returns the name of the item.
	 *
	 * @return string Name of the item
	 */
	public function getLabel() : string;

	/**
	 * Sets the new name of the item.
	 *
	 * @param string $name New name of the item
	 * @return static Item for chaining method calls
	 */
	public function setLabel( string $name ) : static;

	/**
	 * Returns the level of the item in the tree
	 *
	 * @return int Level of the item starting with "0" for the root node
	 */
	public function getLevel() : int;

	/**
	 * Returns the ID of the parent category
	 *
	 * @return string|null Unique ID of the parent category
	 */
	public function getParentId() : ?string;

	/**
	 * Adds a child node to this node.
	 *
	 * @param \Aimeos\MShop\Common\Item\Tree\Iface $item Child node to add
	 * @return static Catalog item for chaining method calls
	 */
	public function addChild( \Aimeos\MShop\Common\Item\Tree\Iface $item ) : static;

	/**
	 * Removes a child node from this node.
	 *
	 * @param \Aimeos\MShop\Common\Item\Tree\Iface $item Child node to remove
	 * @return static Tree item for chaining method calls
	 */
	public function deleteChild( \Aimeos\MShop\Common\Item\Tree\Iface $item ) : static;

	/**
	 * Returns a child of this node identified by its index.
	 *
	 * @param int $index Index of child node
	 * @return static Selected node
	 */
	public function getChild( int $index ) : static;

	/**
	 * Returns all children of this node.
	 *
	 * @return \Aimeos\Map Numerically indexed list of items implementing \Aimeos\MShop\Common\Item\Tree\Iface
	 */
	public function getChildren() : \Aimeos\Map;

	/**
	 * Returns the deleted children.
	 *
	 * @return \Aimeos\Map List of removed children implementing \Aimeos\MShop\Common\Item\Tree\Iface
	 */
	public function getChildrenDeleted() : \Aimeos\Map;

	/**
	 * Tests if a node has children.
	 *
	 * @return bool True if node has children, false if not
	 */
	public function hasChildren() : bool;

	/**
	 * Returns the node and its children as list
	 *
	 * @return \Aimeos\Map List of IDs as keys and items implementing \Aimeos\MShop\Common\Item\Tree\Iface
	 */
	public function toList() : \Aimeos\Map;
}
