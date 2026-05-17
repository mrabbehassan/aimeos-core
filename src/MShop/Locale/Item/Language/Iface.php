<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2026
 * @package MShop
 * @subpackage Locale
 */


namespace Aimeos\MShop\Locale\Item\Language;


/**
 * Common interface for all language items.
 *
 * @package MShop
 * @subpackage Locale
 */
interface Iface
	extends \Aimeos\MShop\Common\Item\Iface, \Aimeos\MShop\Common\Item\Status\Iface
{
	/**
	 * Returns the two letter ISO language code.
	 *
	 * @return string two letter ISO language code
	 */
	public function getCode() : string;

	/**
	 * Sets the two letter ISO language code.
	 *
	 * @param string $key two letter ISO language code
	 * @return static Locale language item for chaining method calls
	 */
	public function setCode( string $key ) : static;

	/**
	 * Returns the label property of the language.
	 *
	 * @return string Label or symbol of the language
	 */
	public function getLabel() : string;

	/**
	 * Sets the label property of the language.
	 *
	 * @param string $label Label or symbol of the language
	 * @return static Locale language item for chaining method calls
	 */
	public function setLabel( string $label ) : static;
}
