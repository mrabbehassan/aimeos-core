<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024-2026
 * @package MShop
 * @subpackage Common
 */


namespace Aimeos\MShop\Common\Item\TypeRef;


/**
 * Common trait for items containing type items
 *
 * @package MShop
 * @subpackage Common
 */
trait Traits
{
	/**
	 * Returns the item property for the given name
	 *
	 * @param string $name Name of the property
	 * @param mixed $default Default value if property is unknown
	 * @return mixed|null Property value or default value if property is unknown
	 */
	abstract public function get( string $name, $default = null );

	/**
	 * Sets the new item property for the given name
	 *
	 * @param string $name Name of the property
	 * @param mixed $value New property value
	 * @return static Item for method chaining
	 */
	abstract public function set( string $name, $value ) : static;

	/**
	 * Returns the prefix for the item properties
	 *
	 * @return string Prefix for the item properties
	 */
	abstract protected function prefix() : string;


	/**
	 * Returns the type item of the item if available.
	 *
	 * @return \Aimeos\MShop\Type\Item\Iface|null Type item or NULL if not available
	 */
	public function getTypeItem() : ?\Aimeos\MShop\Type\Item\Iface
	{
		// @phpstan-ignore return.type
		return $this->get( '.type' );
	}


	/**
	 * Returns the type code of the item.
	 *
	 * @return string Type code of the item
	 */
	public function getType() : string
	{
		return (string) $this->get( $this->prefix() . 'type', '' );
	}


	/**
	 * Sets the new type of the item.
	 *
	 * @param string $type Type of the item
	 * @return static Attribute item for chaining method calls
	 */
	public function setType( string $type ) : static
	{
		return $this->set( $this->prefix() . 'type', \Aimeos\Utils::code( $type ) );
	}
}
