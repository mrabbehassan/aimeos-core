<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2026
 * @package MShop
 * @subpackage Subscription
 */


namespace Aimeos\MShop\Subscription\Item;


/**
 * Interface for all order item implementations.
 *
 * @package MShop
 * @subpackage Subscription
 */
interface Iface extends \Aimeos\MShop\Common\Item\Iface
{
	/**
	 * Renewing payment failed
	 */
	const REASON_PAYMENT = -1;

	/**
	 * Subscription ended normally
	 */
	const REASON_END = 0;

	/**
	 * Subscription cancelled by customer
	 */
	const REASON_CANCEL = 1;


	/**
	 * Returns the associated order item
	 *
	 * @return \Aimeos\MShop\Order\Item\Iface|null Order item
	 */
	public function getOrderItem() : ?\Aimeos\MShop\Order\Item\Iface;

	/**
	 * Returns the ID of the order
	 *
	 * @return string|null ID of the order
	 */
	public function getOrderId() : ?string;

	/**
	 * Sets the ID of the order item which the customer bought
	 *
	 * @param string $id ID of the order
	 * @return static Subscription item for chaining method calls
	 */
	public function setOrderId( string $id ) : static;

	/**
	 * Returns the ID of the ordered product
	 *
	 * @return string|null ID of the ordered product
	 */
	public function getOrderProductId() : ?string;

	/**
	 * Sets the ID of the ordered product item which the customer subscribed for
	 *
	 * @param string $id ID of the ordered product
	 * @return static Subscription item for chaining method calls
	 */
	public function setOrderProductId( string $id ) : static;

	/**
	 * Returns the date of the next subscription renewal
	 *
	 * @return string|null ISO date in "YYYY-MM-DD HH:mm:ss" format
	 */
	public function getDateNext() : ?string;

	/**
	 * Sets the date of the next subscription renewal
	 *
	 * @param string $date ISO date in "YYYY-MM-DD HH:mm:ss" format
	 * @return static Subscription item for chaining method calls
	 */
	public function setDateNext( string $date ) : static;

	/**
	 * Returns the date when the subscription renewal ends
	 *
	 * @return string|null ISO date in "YYYY-MM-DD HH:mm:ss" format
	 */
	public function getDateEnd() : ?string;

	/**
	 * Sets the delivery date of the invoice.
	 *
	 * @param string|null $date ISO date in "YYYY-MM-DD HH:mm:ss" format
	 * @return static Subscription item for chaining method calls
	 */
	public function setDateEnd( ?string $date ) : static;

	/**
	 * Returns the time interval to pass between the subscription renewals
	 *
	 * @return string PHP time interval, e.g. "P1M2W"
	 */
	public function getInterval() : string;

	/**
	 * Sets the time interval to pass between the subscription renewals
	 *
	 * @param string $value PHP time interval, e.g. "P1M2W"
	 * @return static Subscription item for chaining method calls
	 */
	public function setInterval( string $value ) : static;

	/**
	 * Returns the current renewal period of the subscription product
	 *
	 * @return int Current renewal period
	 */
	public function getPeriod() : int;

	/**
	 * Sets the current renewal period of the subscription product
	 *
	 * @param int $value Current renewal period
	 * @return static Subscription item for chaining method calls
	 */
	public function setPeriod( int $value ) : static;

	/**
	 * Returns the product ID of the subscription product
	 *
	 * @return string Product ID
	 */
	public function getProductId() : string;

	/**
	 * Sets the product ID of the subscription product
	 *
	 * @param string $value Product ID
	 * @return static Subscription item for chaining method calls
	 */
	public function setProductId( string $value ) : static;

	/**
	 * Returns the reason for the end of the subscriptions
	 *
	 * @return int|null Reason code or NULL for no reason
	 */
	public function getReason() : ?int;

	/**
	 * Sets the reason for the end of the subscriptions
	 *
	 * @param int|null $status Reason code or NULL for no reason
	 * @return static Subscription item for chaining method calls
	 */
	public function setReason( ?int $status ) : static;

	/**
	 * Returns the status of the subscriptions
	 *
	 * @return int Subscription status, i.e. "1" for enabled, "0" for disabled
	 */
	public function getStatus() : int;

	/**
	 * Sets the status of the subscriptions
	 *
	 * @param int $status Subscription status, i.e. "1" for enabled, "0" for disabled
	 * @return static Subscription item for chaining method calls
	 */
	public function setStatus( int $status ) : static;
}
