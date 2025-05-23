<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2011
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\MShop\Order\Item;


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $coupons;
	private $products;
	private $services;
	private $addresses;
	private $statusItem;


	protected function setUp() : void
	{
		$context = \TestHelper::context();

		$priceManager = \Aimeos\MShop::create( $context, 'price' );
		$locale = \Aimeos\MShop::create( $context, 'locale' )->create();

		$this->object = new \Aimeos\MShop\Order\Item\Standard( 'order.', ['.price' => $priceManager->create(), '.locale' => $locale] );


		$orderManager = \Aimeos\MShop::create( $context, 'order' );
		$orderStatusManager = $orderManager->getSubManager( 'status' );
		$orderAddressManager = $orderManager->getSubManager( 'address' );
		$orderProductManager = $orderManager->getSubManager( 'product' );
		$orderServiceManager = $orderManager->getSubManager( 'service' );


		$price = $priceManager->create();
		$price->setRebate( '3.01' );
		$price->setValue( '43.12' );
		$price->setCosts( '1.11' );
		$price->setTaxRate( '0.00' );
		$price->setCurrencyId( 'EUR' );

		$prod1 = $orderProductManager->create();
		$prod1->setProductCode( 'prod1' );
		$prod1->setPrice( $price );

		$price = $priceManager->create();
		$price->setRebate( '4.00' );
		$price->setValue( '20.00' );
		$price->setCosts( '2.00' );
		$price->setTaxRate( '0.50' );
		$price->setCurrencyId( 'EUR' );

		$prod2 = $orderProductManager->create();
		$prod2->setProductCode( 'prod2' );
		$prod2->setPrice( $price );

		$price = $priceManager->create();
		$price->setCosts( '2.00' );
		$price->setTaxRate( '0.00' );
		$price->setCurrencyId( 'EUR' );


		$this->products = [$prod1, $prod2];
		$this->coupons = map( ['OPQR' => [$prod1]] );

		$this->addresses = array(
			'payment' => [0 => $orderAddressManager->create()->setType( 'payment' )],
			'delivery' => [0 => $orderAddressManager->create()->setType( 'delivery' )],
		);

		$this->services = array(
			'payment' => [0 => $orderServiceManager->create()->setType( 'payment' )->setCode( 'testpay' )],
			'delivery' => [1 => $orderServiceManager->create()->setType( 'delivery' )->setCode( 'testship' )->setPrice( $price )],
		);

		$this->statusItem = $orderStatusManager->create()->setType( 'test' )->setValue( 'value' );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->products, $this->addresses, $this->services, $this->coupons, $this->statusItem );
	}


	public function testArrayMethods()
	{
		$this->assertFalse( isset( $this->object['test'] ) );
		$this->assertEquals( null, $this->object['test'] );

		$this->object['test'] = 'value';

		$this->assertTrue( isset( $this->object['test'] ) );
		$this->assertEquals( 'value', $this->object['test'] );

		$this->expectException( \LogicException::class );
		unset( $this->object['test'] );
	}


	public function testMagicMethods()
	{
		$this->assertFalse( isset( $this->object->test ) );
		$this->assertEquals( null, $this->object->test );

		$this->object->test = 'value';

		$this->assertTrue( isset( $this->object->test ) );
		$this->assertEquals( 'value', $this->object->test );
	}


	public function testGetSet()
	{
		$this->assertEquals( false, $this->object->get( 'test', false ) );

		$this->object->set( 'test', 'value' );

		$this->assertEquals( 'value', $this->object->get( 'test', false ) );
	}


	public function testIsModified()
	{
		$this->assertFalse( $this->object->isModified() );
	}


	public function testAddProductAppend()
	{
		$this->object->setProducts( $this->products );

		$products = $this->object->getProducts();
		$product = $this->createProduct( 'prodid3' );
		$products[] = $product;

		$result = $this->object->addProduct( $product );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $products, $this->object->getProducts() );
		$this->assertSame( $product, $this->object->getProduct( 2 ) );
	}


	public function testAddProductInsert()
	{
		$this->object->setProducts( $this->products );

		$products = $this->object->getProducts();
		$products[1] = $this->createProduct( 'prodid3' );

		$result = $this->object->addProduct( $products[1], 1 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertSame( $products[1], $this->object->getProduct( 1 ) );
		$this->assertEquals( $products, $this->object->getProducts() );
	}


	public function testAddProductInsertEnd()
	{
		$this->object->setProducts( $this->products );

		$products = $this->object->getProducts();
		$product = $this->createProduct( 'prodid3' );
		$products[] = $product;

		$result = $this->object->addProduct( $product, 2 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $products, $this->object->getProducts() );
		$this->assertSame( $product, $this->object->getProduct( 2 ) );
	}


	public function testAddProductSame()
	{
		$product = $this->createProduct( 'prodid3' )->setQuantity( 5 );

		$this->object->addProduct( $product );
		$this->object->addProduct( $product );

		$this->assertEquals( 10, $this->object->getProduct( 0 )->getQuantity() );
		$this->assertEquals( [0 => $product], $this->object->getProducts()->toArray() );
	}


	public function testAddProductStablePosition()
	{
		$this->object->setProducts( $this->products );

		$product = $this->createProduct( 'prodid3' )->setQuantity( 5 );
		$this->object->addProduct( $product );

		$testProduct = $this->object->getProduct( 1 );
		$this->object->deleteProduct( 0 );
		$this->object->deleteProduct( 1 );
		$result = $this->object->addProduct( $testProduct, 1 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( [1 => $testProduct, 2 => $product], $this->object->getProducts()->toArray() );
	}


	public function testDeleteProduct()
	{
		$this->object->addProduct( $this->products[0] );
		$result = $this->object->deleteProduct( 0 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertSame( [], $this->object->getProducts()->toArray() );
	}


	public function testGetProducts()
	{
		$this->object->setProducts( $this->products );

		$this->assertSame( $this->products, $this->object->getProducts()->toArray() );
		$this->assertSame( $this->products[1], $this->object->getProduct( 1 ) );
	}


	public function testSetProducts()
	{
		$result = $this->object->setProducts( $this->products );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertSame( $this->products, $this->object->getProducts()->toArray() );
	}


	public function testAddAddress()
	{
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;
		$result = $this->object->addAddress( $this->addresses[$type][0], $type );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $this->addresses[$type], $this->object->getAddress( $type ) );
	}


	public function testAddAddressMultiple()
	{
		$this->object->addAddress( $this->addresses['payment'][0], 'payment' );
		$result = $this->object->addAddress( $this->addresses['payment'][0], 'payment' );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( 2, count( $this->object->getAddress( 'payment' ) ) );
	}


	public function testAddAddressPosition()
	{
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;

		$this->object->addAddress( $this->addresses[$type][0], $type );
		$result = $this->object->addAddress( $this->addresses[$type][0], $type, 0 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $this->addresses[$type], $this->object->getAddress( $type ) );
	}


	public function testDeleteAddress()
	{
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;
		$this->object->setAddresses( $this->addresses );
		$result = $this->object->deleteAddress( $type );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( [], $this->object->getAddress( $type ) );
	}


	public function testDeleteAddressPosition()
	{
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;
		$this->object->setAddresses( $this->addresses );

		$result = $this->object->deleteAddress( $type, 0 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( [], $this->object->getAddress( $type ) );
	}


	public function testDeleteAddressPositionInvalid()
	{
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;
		$this->object->setAddresses( $this->addresses );

		$result = $this->object->deleteAddress( $type, 1 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $this->addresses[$type], $this->object->getAddress( $type ) );
	}


	public function testGetAddress()
	{
		$this->object->setAddresses( $this->addresses );
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;

		$this->assertEquals( $this->addresses[$type], $this->object->getAddress( $type ) );
	}


	public function testGetAddressSingle()
	{
		$this->object->setAddresses( $this->addresses );
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;

		$this->assertEquals( $this->addresses[$type][0], $this->object->getAddress( $type, 0 ) );
	}


	public function testGetAddressException()
	{
		$this->expectException( \Aimeos\MShop\Order\Exception::class );
		$this->object->getAddress( \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT, 0 );
	}


	public function testSetAddresses()
	{
		$result = $this->object->setAddresses( $this->addresses );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $this->addresses, $this->object->getAddresses()->toArray() );
	}


	public function testAddService()
	{
		$type = \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT;
		$result = $this->object->addService( $this->services[$type][0], $type );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( 1, count( $this->object->getService( $type ) ) );
	}


	public function testAddServicePosition()
	{
		$type = \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT;

		$this->object->addService( $this->services[$type][0], $type );
		$result = $this->object->addService( $this->services[$type][0], $type, 0 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( 1, count( $this->object->getService( $type ) ) );
	}


	public function testDeleteService()
	{
		$type = \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT;
		$this->object->setServices( $this->services );

		$result = $this->object->deleteService( $type );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( [], $this->object->getService( $type ) );
	}


	public function testDeleteServicePosition()
	{
		$type = \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT;
		$this->object->setServices( $this->services );

		$result = $this->object->deleteService( $type, 0 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( [], $this->object->getService( $type ) );
	}


	public function testDeleteServicePositionInvalid()
	{
		$type = \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT;
		$this->object->setServices( $this->services );

		$result = $this->object->deleteService( $type, 1 );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $this->services[$type], $this->object->getService( $type ) );
	}


	public function testGetService()
	{
		$this->object->setServices( $this->services );

		$payments = $this->object->getService( \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT );
		$deliveries = $this->object->getService( \Aimeos\MShop\Order\Item\Service\Base::TYPE_DELIVERY );

		$this->assertEquals( 2, count( $this->object->getServices() ) );
		$this->assertEquals( 1, count( $payments ) );
		$this->assertEquals( 1, count( $deliveries ) );

		$this->assertEquals( $this->services['payment'], $payments );
		$this->assertEquals( $this->services['delivery'], $deliveries );
	}


	public function testGetServiceSingle()
	{
		$this->object->setServices( $this->services );

		$service = $this->object->getService( 'payment', 0 );
		$this->assertEquals( 'testpay', $service->getCode() );
	}


	public function testGetServiceException()
	{
		$this->expectException( \Aimeos\MShop\Order\Exception::class );
		$this->object->getService( 'payment', 100 );
	}


	public function testSetServices()
	{
		$result = $this->object->setServices( $this->services );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $this->services, $this->object->getServices()->toArray() );
	}


	public function testAddCoupon()
	{
		$result = $this->object->addCoupon( 'OPQR' );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( ['OPQR' => []], $this->object->getCoupons()->toArray() );
	}


	public function testDeleteCoupon()
	{
		$this->object->setCoupons( $this->coupons );
		$result = $this->object->deleteCoupon( 'OPQR' );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( [], $this->object->getCoupons()->toArray() );
	}


	public function testGetCoupons()
	{
		$this->object->setCoupons( $this->coupons );
		$this->assertEquals( $this->coupons, $this->object->getCoupons() );
	}


	public function testSetCoupon()
	{
		$result = $this->object->setCoupon( 'OPQR', $this->coupons['OPQR'] );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( map( ['OPQR' => $this->coupons['OPQR']] ), $this->object->getCoupons() );
	}


	public function testSetCoupons()
	{
		$result = $this->object->setCoupons( $this->coupons );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
		$this->assertEquals( $this->coupons, $this->object->getCoupons() );
	}


	public function testAddStatus()
	{
		$result = $this->object->addStatus( $this->statusItem );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
	}


	public function testGetStatus()
	{
		$this->object->addStatus( $this->statusItem );
		$item = $this->object->getStatus( 'test', 'value' );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Status\Iface::class, $item );
		$this->assertEquals( 'value', $item->getValue() );
	}


	public function testGetStatuses()
	{
		$this->object->addStatus( $this->statusItem );
		$items = $this->object->getStatuses();

		$this->assertEquals( 'value', $items['test']['value']->getValue() );
	}


	public function testGetTaxes()
	{
		$this->object->setProducts( $this->products );
		$this->object->setServices( $this->services );

		$result = $this->object->getTaxes();

		$this->assertArrayHasKey( 'tax', $result );
		$this->assertArrayHasKey( '0.00', $result['tax'] );
		$this->assertArrayHasKey( '0.50', $result['tax'] );
		$this->assertEquals( '0.0000', $result['tax']['0.00']->getTaxValue() );
		$this->assertEquals( '0.1095', $result['tax']['0.50']->getTaxValue() );
	}


	public function testGetCosts()
	{
		$this->object->setServices( $this->services );

		$this->assertEquals( '2.00', round( $this->object->getCosts( 'delivery' ), 2 ) );
		$this->assertEquals( '0.00', $this->object->getCosts( 'payment' ) );
	}


	public function testCheck()
	{
		foreach( $this->products as $product ) {
			$this->object->addProduct( $product );
		}

		foreach( $this->addresses as $type => $addresses )
		{
			foreach( $addresses as $address ) {
				$this->object->addAddress( $address, $type );
			}
		}

		foreach( $this->services as $type => $services )
		{
			foreach( $services as $service ) {
				$this->object->addService( $service, $type );
			}
		}

		$result = $this->object->check( ['order/address', 'order/coupon', 'order/product', 'order/service'] );
		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
	}


	public function testCheckAllFailure()
	{
		$this->expectException( \Aimeos\MShop\Order\Exception::class );
		$this->object->check( ['order/address', 'order/coupon', 'order/product', 'order/service'] );
	}


	public function testCheckProductsFailure()
	{
		$this->expectException( \Aimeos\MShop\Order\Exception::class );
		$this->object->check( ['order/product'] );
	}


	/**
	 * @param string $code
	 */
	protected function createProduct( $code )
	{
		$product = \Aimeos\MShop::create( \TestHelper::context(), 'order/product' )->create();
		$price = \Aimeos\MShop::create( \TestHelper::context(), 'price' )->create();
		$price->setValue( '2.99' );

		$product->setPrice( $price );
		$product->setProductCode( $code );

		return $product;
	}
}
