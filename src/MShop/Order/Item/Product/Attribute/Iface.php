<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2026
 * @package MShop
 * @subpackage Order
 */


namespace Aimeos\MShop\Order\Item\Product\Attribute;


/**
 * Interface for objects storing the selected product attributes.
 *
 * @package MShop
 * @subpackage Order
 */
interface Iface
	extends \Aimeos\MShop\Common\Item\Iface, \Aimeos\MShop\Common\Item\Parentid\Iface, \Aimeos\MShop\Common\Item\TypeRef\Iface
{
	/**
	 * Sets the site ID of the item.
	 *
	 * @param string $value Unique site ID of the item
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function setSiteId( string $value ) : static;

	/**
	 * Returns the original attribute ID of the ordered product attribute.
	 *
	 * @return string Attribute ID of the ordered product attribute
	 */
	public function getAttributeId() : string;

	/**
	 * Sets the original attribute ID of the ordered product attribute.
	 *
	 * @param string|null $id Attribute ID of the ordered product attribute
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function setAttributeId( ?string $id ) : static;

	/**
	 * Returns the code of the product attibute.
	 *
	 * @return string Code of the attribute
	 */
	public function getCode() : string;

	/**
	 * Sets the code of the product attribute.
	 *
	 * @param string $code Code of the attribute
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function setCode( string $code ) : static;

	/**
	 * Returns the localized name of the product attribute.
	 *
	 * @return string Localized name of the product attribute
	 */
	public function getName() : string;

	/**
	 * Sets the localized name of the product attribute.
	 *
	 * @param string|null $name Localized name of the product attribute
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function setName( ?string $name ) : static;

	/**
	 * Returns the value of the product attribute.
	 *
	 * @return string|array Value of the product attribute
	 */
	public function getValue();

	/**
	 * Sets the value of the product attribute.
	 *
	 * @param string|array $value Value of the product attribute
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function setValue( $value ) : static;

	/**
	 * Returns the quantity of the product attribute.
	 *
	 * @return float Quantity of the product attribute
	 */
	public function getQuantity() : float;

	/**
	 * Sets the quantity of the product attribute.
	 *
	 * @param float $value Quantity of the product attribute
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function setQuantity( float $value ) : static;

	/**
	 * Returns the price of the product attribute.
	 *
	 * @return string|null Price of the product attribute
	 */
	public function getPrice() : ?string;

	/**
	 * Sets the price of the product attribute.
	 *
	 * @param string|null $value Price of the product attribute
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function setPrice( ?string $value ) : static;

	/**
	 * Copys all data from a given attribute item.
	 *
	 * @param \Aimeos\MShop\Attribute\Item\Iface $item Attribute item to copy from
	 * @return static Order base product attribute item for chaining method calls
	 */
	public function copyFrom( \Aimeos\MShop\Attribute\Item\Iface $item ) : static;
}
