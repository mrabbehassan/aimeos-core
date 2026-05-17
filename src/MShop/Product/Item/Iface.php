<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2026
 * @package MShop
 * @subpackage Product
 */


namespace Aimeos\MShop\Product\Item;


/**
 * Generic interface for product items created and saved by product managers.
 *
 * @package MShop
 * @subpackage Product
 */
interface Iface
	extends \Aimeos\MShop\Common\Item\Iface, \Aimeos\MShop\Common\Item\Config\Iface,
		\Aimeos\MShop\Common\Item\ListsRef\Iface, \Aimeos\MShop\Common\Item\PropertyRef\Iface,
		\Aimeos\MShop\Common\Item\Rating\Iface, \Aimeos\MShop\Common\Item\Status\Iface,
		\Aimeos\MShop\Common\Item\Time\Iface, \Aimeos\MShop\Common\Item\TypeRef\Iface
{
	/**
	 * Returns the parent product items referencing the product
	 *
	 * @return \Aimeos\Map Associative list of items implementing \Aimeos\MShop\Product\Item\Iface
	 */
	public function getParentItems() : \Aimeos\Map;

	/**
	 * Returns the supplier items referencing the product
	 *
	 * @return \Aimeos\MShop\Locale\Item\Site\Iface|null Site item or null if not available
	 */
	public function getSiteItem() : ?\Aimeos\MShop\Locale\Item\Site\Iface;

	/**
	 * Adds a new stock item or overwrite an existing one
	 *
	 * @param \Aimeos\MShop\Stock\Item\Iface $item New or existing stock item
	 * @return static Self object for method chaining
	 */
	public function addStockItem( \Aimeos\MShop\Stock\Item\Iface $item ) : static;

	/**
	 * Adds new stock items or overwrite existing ones
	 *
	 * @param iterable $items New or existing stock items
	 * @return static Self object for method chaining
	 */
	public function addStockItems( iterable $items ) : static;

	/**
	 * Removes an existing stock item
	 *
	 * @param \Aimeos\MShop\Stock\Item\Iface $item Existing stock item
	 * @return static Self object for method chaining
	 */
	public function deleteStockItem( \Aimeos\MShop\Stock\Item\Iface $item ) : static;

	/**
	 * Removes a list of existing stock items
	 *
	 * @param \Aimeos\Map|\Aimeos\MShop\Stock\Item\Iface[] $items Existing stock items
	 * @return static Self object for method chaining
	 */
	public function deleteStockItems( iterable $items ) : static;

	/**
	 * Returns the deleted stock items
	 *
	 * @return \Aimeos\Map Stock items implementing \Aimeos\MShop\Stock\Item\Iface
	 */
	public function getStockItemsDeleted() : \Aimeos\Map;

	/**
	 * Returns the stock items associated to the product
	 *
	 * @param string|null $type Type of the stock item
	 * @return \Aimeos\Map Associative list of items implementing \Aimeos\MShop\Stock\Item\Iface
	 */
	public function getStockItems( $type = null ) : \Aimeos\Map;

	/**
	 * Adds a new stock item or overwrite an existing one
	 *
	 * @param \Aimeos\Map|\Aimeos\MShop\Stock\Item\Iface[] $items New list of stock items
	 * @return static Self object for method chaining
	 */
	public function setStockItems( iterable $items ) : static;

	/**
	 * Returns the code of the product item.
	 *
	 * @return string Code of the product
	 */
	public function getCode() : string;

	/**
	 * Sets a new code of the product item.
	 *
	 * @param string $code New code of the product item
	 * @return static Product item for chaining method calls
	 */
	public function setCode( string $code ) : static;

	/**
	 * Returns the data set name assigned to the product item.
	 *
	 * @return string Data set name
	 */
	public function getDataset() : string;

	/**
	 * Sets a new data set name assignd to the product item.
	 *
	 * @param string $name New data set name
	 * @return static Product item for chaining method calls
	 */
	public function setDataset( ?string $name ) : static;

	/**
	 * Returns the label of the product item.
	 *
	 * @return string Label of the product item
	 */
	public function getLabel() : string;

	/**
	 * Sets a new label of the product.
	 *
	 * @param string $label New label of the product item
	 * @return static Product item for chaining method calls
	 */
	public function setLabel( string $label ) : static;

	/**
	 * Returns the URL segment for the product item.
	 *
	 * @return string URL segment of the product item
	 */
	public function getUrl() : string;

	/**
	 * Sets a new URL segment for the product.
	 *
	 * @param string|null $url New URL segment of the product item
	 * @return static Product item for chaining method calls
	 */
	public function setUrl( ?string $url ) : static;

	/**
	 * Returns the quantity scale of the product item.
	 *
	 * @return float Quantity scale
	 */
	public function getScale() : float;

	/**
	 * Sets a new quantity scale of the product item.
	 *
	 * @param float $value New quantity scale
	 * @return static Product item for chaining method calls
	 */
	public function setScale( float $value ) : static;

	/**
	 * Returns the URL target specific for that product
	 *
	 * @return string URL target specific for that product
	 */
	public function getTarget() : string;

	/**
	 * Sets a new label of the product item.
	 *
	 * @param string $value New URL target specific for that product
	 * @return static Product item for chaining method calls
	 */
	public function setTarget( ?string $value ) : static;

	/**
	 * Returns the flag if stock is available for that product.
	 *
	 * @return int "1" if product is in stock, "0" if product is out of stock
	 */
	public function inStock() : int;

	/**
	 * Sets the flag if stock is available for that product.
	 *
	 * @param int $value "1" if product is in stock, "0" if product is out of stock
	 * @return static Product item for chaining method calls
	 */
	public function setInStock( int $value ) : static;

	/**
	 * Returns the boost factor for that product.
	 *
	 * @return float Boost factor
	 */
	public function boost() : float;

	/**
	 * Sets the boost factor for that product.
	 *
	 * @param float $value Boost factor
	 * @return static Product item for chaining method calls
	 */
	public function setBoost( float $value ) : static;
}
