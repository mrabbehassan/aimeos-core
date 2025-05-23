<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2025
 * @package MShop
 * @subpackage Plugin
 */


namespace Aimeos\MShop\Plugin\Provider\Order;


/**
 * Sets the tax rate of products and services depending on the country
 *
 * Shops selling into several countries with different tax rates can use this
 * plugin to set a different tax rate in all price items for that countries.
 *
 * The following option is available:
 * - country-taxrates: JSON object of ISO country code as key and tax rate as value
 *
 * To trace the execution and interaction of the plugins, set the log level to DEBUG:
 *	madmin/log/manager/loglevel = 7
 *
 * @package MShop
 * @subpackage Plugin
 */
class Taxrates
	extends \Aimeos\MShop\Plugin\Provider\Factory\Base
	implements \Aimeos\MShop\Plugin\Provider\Iface, \Aimeos\MShop\Plugin\Provider\Factory\Iface
{
	private array $beConfig = array(
		'country-taxrates' => array(
			'code' => 'country-taxrates',
			'internalcode' => 'country-taxrates',
			'label' => 'Tax rate for each two letter ISO country code',
			'type' => 'map',
			'internaltype' => 'array',
			'default' => [],
			'required' => false,
		),
		'state-taxrates' => array(
			'code' => 'state-taxrates',
			'internalcode' => 'state-taxrates',
			'label' => 'Tax rate for each two letter state code',
			'type' => 'map',
			'internaltype' => 'array',
			'default' => [],
			'required' => false,
		),
		'services' => array(
			'code' => 'services',
			'internalcode' => 'services',
			'label' => 'Apply to services as well',
			'type' => 'bool',
			'internaltype' => 'bool',
			'default' => true,
			'required' => false,
		),
	);


	/**
	 * Checks the backend configuration attributes for validity.
	 *
	 * @param array $attributes Attributes added by the shop owner in the administraton interface
	 * @return array An array with the attribute keys as key and an error message as values for all attributes that are
	 * 	known by the provider but aren't valid
	 */
	public function checkConfigBE( array $attributes ) : array
	{
		$errors = parent::checkConfigBE( $attributes );

		return array_merge( $errors, $this->checkConfig( $this->beConfig, $attributes ) );
	}


	/**
	 * Returns the configuration attribute definitions of the provider to generate a list of available fields and
	 * rules for the value of each field in the administration interface.
	 *
	 * @return array List of attribute definitions implementing \Aimeos\Base\Critera\Attribute\Iface
	 */
	public function getConfigBE() : array
	{
		return $this->getConfigItems( $this->beConfig );
	}


	/**
	 * Subscribes itself to a publisher
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface $p Object implementing publisher interface
	 * @return \Aimeos\MShop\Plugin\Provider\Iface Plugin object for method chaining
	 */
	public function register( \Aimeos\MShop\Order\Item\Iface $p ) : \Aimeos\MShop\Plugin\Provider\Iface
	{
		$plugin = $this->object();

		$p->attach( $plugin, 'addAddress.after' );
		$p->attach( $plugin, 'setAddresses.after' );
		$p->attach( $plugin, 'addProduct.after' );
		$p->attach( $plugin, 'addService.after' );
		$p->attach( $plugin, 'setServices.after' );

		return $this;
	}


	/**
	 * Receives a notification from a publisher object
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface $order Shop basket instance implementing publisher interface
	 * @param string $action Name of the action to listen for
	 * @param mixed $value Object or value changed in publisher
	 * @return mixed Modified value parameter
	 * @throws \Aimeos\MShop\Plugin\Provider\Exception if checks fail
	 */
	public function update( \Aimeos\MShop\Order\Item\Iface $order, string $action, $value = null )
	{
		$addrpay = $order->getAddress( 'payment' );
		$addrship = $order->getAddress( 'delivery' );
		$taxrates = $this->getConfigValue( 'country-taxrates', [] );
		$staterates = $this->getConfigValue( 'state-taxrates', [] );

		if( ( $address = reset( $addrship ) ) === false
			&& ( $address = reset( $addrpay ) ) === false
			|| !isset( $taxrates[$address->getCountryId()] )
			&& !isset( $staterates[$address->getState()] )
		) {
			return $value;
		}

		$taxrate = $staterates[$address->getState()] ?? $taxrates[$address->getCountryId()];

		if( $value instanceof \Aimeos\MShop\Order\Item\Product\Iface )
		{
			$value->getPrice()->setTaxrate( $taxrate );
			return $value;
		}

		foreach( $order->getProducts() as $orderProduct )
		{
			foreach( $orderProduct->getProducts() as $subProduct ) {
				$subProduct->getPrice()->setTaxrate( $taxrate );
			}

			$orderProduct->getPrice()->setTaxrate( $taxrate );
		}

		if( $this->getConfigValue( 'services', true ) )
		{
			foreach( $order->getServices() as $orderServiceGroup )
			{
				foreach( $orderServiceGroup as $orderService ) {
					$orderService->getPrice()->setTaxrate( $taxrate );
				}
			}
		}

		return $value;
	}
}
