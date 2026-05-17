<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package MShop
 * @subpackage Common
 */


namespace Aimeos\MShop\Common\Cursor;


/**
 * Default implementation for manager iterators
 *
 * @package MShop
 * @subpackage Common
 */
class Standard implements Iface
{
	private \Aimeos\Base\Criteria\Iface $filter;
	private mixed $value = null;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\Base\Criteria\Iface $filter Criteria object with conditions, sortations, etc.
	 */
	public function __construct( \Aimeos\Base\Criteria\Iface $filter )
	{
		$this->filter = $filter;
	}


	/**
	 * Returns the filter criteria object
	 *
	 * @return \Aimeos\Base\Criteria\Iface Filter criteria object
	 */
	public function filter() : \Aimeos\Base\Criteria\Iface
	{
		return clone $this->filter;
	}


	/**
	 * Sets the new cursor value
	 *
	 * @return \Aimeos\MShop\Common\Cursor\Iface Cursor object
	 */
	public function setValue( mixed $value ) : \Aimeos\MShop\Common\Cursor\Iface
	{
		$this->value = $value;
		return $this;
	}


	/**
	 * Returns the cursor value
	 *
	 * @return mixed Cursor value
	 */
	public function value()
	{
		return $this->value;
	}
}
