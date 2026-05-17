<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2026
 * @package MAdmin
 * @subpackage Cache
 */


namespace Aimeos\MAdmin\Cache\Item;


/**
 * MAdmin cache item Interface.
 *
 * @package MAdmin
 * @subpackage Cache
 */
interface Iface extends \Aimeos\MShop\Common\Item\Iface
{
	/**
	 * Returns the value associated to the key.
	 *
	 * @return string Returns the value of the item
	 */
	public function getValue() : string;

	/**
	 * Sets the new value of the item.
	 *
	 * @param string $value Value of the item
	 * @return static Cache item for chaining method calls
	 */
	public function setValue( string $value ) : static;

	/**
	 * Returns the expiration time of the item.
	 *
	 * @return string|null Expiration time of the item or null for no expiration
	 */
	public function getTimeExpire() : ?string;

	/**
	 * Sets the new expiration time of the item.
	 *
	 * @param string|null $timestamp Expiration time of the item or null for no expiration
	 * @return static Cache item for chaining method calls
	 */
	public function setTimeExpire( ?string $timestamp ) : static;

	/**
	 * Returns the tags associated to the item.
	 *
	 * @return array Tags associated to the item
	 */
	public function getTags() : array;

	/**
	 * Sets the new tags associated to the item.
	 *
	 * @param array $tags Tags associated to the item
	 * @return static Cache item for chaining method calls
	 */
	public function setTags( array $tags ) : static;
}
