<?php
/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2012
 * @copyright Aimeos (aimeos.org), 2015-2025
 * @package MShop
 * @subpackage Index
 */


namespace Aimeos\MShop\Index\Manager\Price;


/**
 * Submanager for product prices.
 *
 * @package MShop
 * @subpackage Index
 */
class Standard
	extends \Aimeos\MShop\Index\Manager\DBBase
	implements \Aimeos\MShop\Index\Manager\Price\Iface, \Aimeos\MShop\Common\Manager\Factory\Iface
{
	private array $searchConfig = array(
		'index.price.id' => array(
			'code' => 'index.price.id',
			'internalcode' => 'mindpr."prodid"',
			'internaldeps'=>array( 'LEFT JOIN "mshop_index_price" AS mindpr ON mindpr."prodid" = mpro."id"' ),
			'label' => 'Product index price ID',
		),
		'index.price:value' => array(
			'code' => 'index.price:value()',
			'internalcode' => ':site AND mindpr."currencyid" = $1 AND mindpr."value"',
			'label' => 'Product price value, parameter(<currency ID>)',
			'type' => 'float',
			'public' => false,
		),
		'agg:index.price:value' => array(
			'code' => 'agg:index.price:value()',
			'internalcode' => 'mindpr."value"',
			'label' => 'Aggregate product price value, parameter(<currency ID>)',
			'type' => 'float',
			'public' => false,
		),
		'sort:index.price:value' => array(
			'code' => 'sort:index.price:value()',
			'internalcode' => 'mindpr."value"',
			'label' => 'Sort product price value, parameter(<currency ID>)',
			'type' => 'float',
			'public' => false,
		),
	);

	private ?array $subManagers = null;


	/**
	 * Initializes the manager instance.
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context )
	{
		parent::__construct( $context );

		$level = \Aimeos\MShop\Locale\Manager\Base::SITE_ALL;
		$level = $context->config()->get( 'mshop/index/manager/sitemode', $level );

		$name = 'index.price:value';
		$expr = $this->siteString( 'mindpr."siteid"', $level );
		$this->searchConfig[$name]['internalcode'] = str_replace( ':site', $expr, $this->searchConfig[$name]['internalcode'] );
	}


	/**
	 * Counts the number products that are available for the values of the given key.
	 *
	 * @param \Aimeos\Base\Criteria\Iface $search Search criteria
	 * @param string $key Search key (usually the ID) to aggregate products for
	 * @param string|null $value Search key for aggregating the value column
	 * @param string|null $type Type of the aggregation, empty string for count or "sum" or "avg" (average)
	 * @return \Aimeos\Map List of ID values as key and the number of counted products as value
	 */
	public function aggregate( \Aimeos\Base\Criteria\Iface $search, $key, ?string $value = null, ?string $type = null ) : \Aimeos\Map
	{
		return $this->aggregateBase( $search, $key, 'mshop/index/manager/aggregate', [], $value, $type );
	}


	/**
	 * Removes old entries from the storage.
	 *
	 * @param iterable $siteids List of IDs for sites whose entries should be deleted
	 * @return \Aimeos\MShop\Index\Manager\Iface Manager object for chaining method calls
	 */
	public function clear( iterable $siteids ) : \Aimeos\MShop\Common\Manager\Iface
	{
		parent::clear( $siteids );

		return $this->clearBase( $siteids, 'mshop/index/manager/price/delete' );
	}


	/**
	 * Removes all entries not touched after the given timestamp in the index.
	 * This can be a long lasting operation.
	 *
	 * @param string $timestamp Timestamp in ISO format (YYYY-MM-DD HH:mm:ss)
	 * @return \Aimeos\MShop\Index\Manager\Iface Manager object for chaining method calls
	 */
	public function cleanup( string $timestamp ) : \Aimeos\MShop\Index\Manager\Iface
	{
		/** mshop/index/manager/price/cleanup/mysql
		 * Deletes the index price records that haven't been touched
		 *
		 * @see mshop/index/manager/price/cleanup/ansi
		 */

		/** mshop/index/manager/price/cleanup/ansi
		 * Deletes the index price records that haven't been touched
		 *
		 * During the rebuild process of the product index, the entries of all
		 * active products will be removed and readded. Thus, no stale data for
		 * these products will remain in the database.
		 *
		 * All products that have been disabled since the last rebuild will be
		 * still part of the index. The cleanup statement removes all records
		 * that belong to products that haven't been touched during the index
		 * rebuild because these are the disabled ones.
		 *
		 * The SQL statement should conform to the ANSI standard to be
		 * compatible with most relational database systems. This also
		 * includes using double quotes for table and column names.
		 *
		 * @param string SQL statement for deleting the outdated price index records
		 * @since 2014.03
		 * @see mshop/index/manager/price/count/ansi
		 * @see mshop/index/manager/price/delete/ansi
		 * @see mshop/index/manager/price/insert/ansi
		 * @see mshop/index/manager/price/search/ansi
		 */
		return $this->cleanupBase( $timestamp, 'mshop/index/manager/price/cleanup' );
	}


	/**
	 * Removes multiple items.
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface|\Aimeos\Map|array|string $itemIds List of item objects or IDs of the items
	 * @return \Aimeos\MShop\Index\Manager\Price\Iface Manager object for chaining method calls
	 */
	public function delete( $itemIds ) : \Aimeos\MShop\Common\Manager\Iface
	{
		/** mshop/index/manager/price/delete/mysql
		 * Deletes the items matched by the given IDs from the database
		 *
		 * @see mshop/index/manager/price/delete/ansi
		 */

		/** mshop/index/manager/price/delete/ansi
		 * Deletes the items matched by the given IDs from the database
		 *
		 * Removes the records specified by the given IDs from the index database.
		 * The records must be from the site that is configured via the
		 * context item.
		 *
		 * The ":cond" placeholder is replaced by the name of the ID column and
		 * the given ID or list of IDs while the site ID is bound to the question
		 * mark.
		 *
		 * The SQL statement should conform to the ANSI standard to be
		 * compatible with most relational database systems. This also
		 * includes using double quotes for table and column names.
		 *
		 * @param string SQL statement for deleting index price records
		 * @since 2014.03
		 * @see mshop/index/manager/price/count/ansi
		 * @see mshop/index/manager/price/cleanup/ansi
		 * @see mshop/index/manager/price/insert/ansi
		 * @see mshop/index/manager/price/search/ansi
		 */
		return $this->deleteItemsBase( $itemIds, 'mshop/index/manager/price/delete' );
	}


	/**
	 * Returns a list of objects describing the available criterias for searching.
	 *
	 * @param bool $withsub Return also attributes of sub-managers if true
	 * @return array List of items implementing \Aimeos\Base\Criteria\Attribute\Iface
	 */
	public function getSearchAttributes( bool $withsub = true ) : array
	{
		$list = parent::getSearchAttributes( $withsub );

		/** mshop/index/manager/price/submanagers
		 * List of manager names that can be instantiated by the index price manager
		 *
		 * Managers provide a generic interface to the underlying storage.
		 * Each manager has or can have sub-managers caring about particular
		 * aspects. Each of these sub-managers can be instantiated by its
		 * parent manager using the getSubManager() method.
		 *
		 * The search keys from sub-managers can be normally used in the
		 * manager as well. It allows you to search for items of the manager
		 * using the search keys of the sub-managers to further limit the
		 * retrieved list of items.
		 *
		 * @param array List of sub-manager names
		 * @since 2014.03
		 */
		$path = 'mshop/index/manager/price/submanagers';

		return $list + $this->getSearchAttributesBase( $this->searchConfig, $path, [], $withsub );
	}


	/**
	 * Returns a new manager for product extensions.
	 *
	 * @param string $manager Name of the sub manager type in lower case
	 * @param string|null $name Name of the implementation, will be from configuration (or Default) if null
	 * @return \Aimeos\MShop\Common\Manager\Iface Manager for different extensions, e.g stock, tags, locations, etc.
	 */
	public function getSubManager( string $manager, ?string $name = null ) : \Aimeos\MShop\Common\Manager\Iface
	{
		/** mshop/index/manager/price/name
		 * Class name of the used index price manager implementation
		 *
		 * Each default index price manager can be replaced by an alternative imlementation.
		 * To use this implementation, you have to set the last part of the class
		 * name as configuration value so the manager factory knows which class it
		 * has to instantiate.
		 *
		 * For example, if the name of the default class is
		 *
		 *  \Aimeos\MShop\Index\Manager\Price\Standard
		 *
		 * and you want to replace it with your own version named
		 *
		 *  \Aimeos\MShop\Index\Manager\Price\Myprice
		 *
		 * then you have to set the this configuration option:
		 *
		 *  mshop/index/manager/price/name = Myprice
		 *
		 * The value is the last part of your own class name and it's case sensitive,
		 * so take care that the configuration value is exactly named like the last
		 * part of the class name.
		 *
		 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
		 * characters are possible! You should always start the last part of the class
		 * name with an upper case character and continue only with lower case characters
		 * or numbers. Avoid chamel case names like "MyPrice"!
		 *
		 * @param string Last part of the class name
		 * @since 2014.03
		 */

		/** mshop/index/manager/price/decorators/excludes
		 * Excludes decorators added by the "common" option from the index price manager
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "mshop/common/manager/decorators/default" before they are wrapped
		 * around the index price manager.
		 *
		 *  mshop/index/manager/price/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\MShop\Common\Manager\Decorator\*") added via
		 * "mshop/common/manager/decorators/default" for the index price manager.
		 *
		 * @param array List of decorator names
		 * @since 2014.03
		 * @see mshop/common/manager/decorators/default
		 * @see mshop/index/manager/price/decorators/global
		 * @see mshop/index/manager/price/decorators/local
		 */

		/** mshop/index/manager/price/decorators/global
		 * Adds a list of globally available decorators only to the index price manager
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\MShop\Common\Manager\Decorator\*") around the index price
		 * manager.
		 *
		 *  mshop/index/manager/price/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\MShop\Common\Manager\Decorator\Decorator1" only to the index
		 * price manager.
		 *
		 * @param array List of decorator names
		 * @since 2014.03
		 * @see mshop/common/manager/decorators/default
		 * @see mshop/index/manager/price/decorators/excludes
		 * @see mshop/index/manager/price/decorators/local
		 */

		/** mshop/index/manager/price/decorators/local
		 * Adds a list of local decorators only to the index price manager
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\MShop\Index\Manager\Price\Decorator\*") around the index
		 * price manager.
		 *
		 *  mshop/index/manager/price/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\MShop\Index\Manager\Price\Decorator\Decorator2" only to the
		 * index price manager.
		 *
		 * @param array List of decorator names
		 * @since 2014.03
		 * @see mshop/common/manager/decorators/default
		 * @see mshop/index/manager/price/decorators/excludes
		 * @see mshop/index/manager/price/decorators/global
		 */

		return $this->getSubManagerBase( 'index', 'price/' . $manager, $name );
	}


	/**
	 * Optimizes the index if necessary.
	 * Execution of this operation can take a very long time and shouldn't be
	 * called through a web server enviroment.
	 *
	 * @return \Aimeos\MShop\Index\Manager\Iface Manager object for chaining method calls
	 */
	public function optimize() : \Aimeos\MShop\Index\Manager\Iface
	{
		/** mshop/index/manager/price/optimize/mysql
		 * Optimizes the stored price data for retrieving the records faster
		 *
		 * @see mshop/index/manager/price/optimize/ansi
		 */

		/** mshop/index/manager/price/optimize/ansi
		 * Optimizes the stored price data for retrieving the records faster
		 *
		 * The SQL statement should reorganize the data in the DBMS storage to
		 * optimize access to the records of the table or tables. Some DBMS
		 * offer specialized statements to optimize indexes and records. This
		 * statement doesn't return any records.
		 *
		 * The SQL statement should conform to the ANSI standard to be
		 * compatible with most relational database systems. This also
		 * includes using double quotes for table and column names.
		 *
		 * @param string SQL statement for optimizing the stored price data
		 * @since 2014.09
		 * @see mshop/index/manager/price/count/ansi
		 * @see mshop/index/manager/price/search/ansi
		 * @see mshop/index/manager/price/aggregate/ansi
		 */
		return $this->optimizeBase( 'mshop/index/manager/price/optimize' );
	}


	/**
	 * Rebuilds the index price for searching products or specified list of products.
	 * This can be a long lasting operation.
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface[] $items Associative list of product IDs as keys and items as values
	 * @return \Aimeos\MShop\Index\Manager\Iface Manager object for chaining method calls
	 */
	public function rebuild( iterable $items = [] ) : \Aimeos\MShop\Index\Manager\Iface
	{
		if( ( $items = map( $items ) )->isEmpty() ) { return $this; }

		$items->implements( \Aimeos\MShop\Product\Item\Iface::class, true );

		$context = $this->context();
		$conn = $context->db( $this->getResourceName() );

			/** mshop/index/manager/price/insert/mysql
			 * Inserts a new price record into the product index database
			 *
			 * @see mshop/index/manager/price/insert/ansi
			 */

			/** mshop/index/manager/price/insert/ansi
			 * Inserts a new price record into the product index database
			 *
			 * During the product index rebuild, prices related to a product
			 * will be stored in the index for this product. All records
			 * are deleted before the new ones are inserted.
			 *
			 * The SQL statement must be a string suitable for being used as
			 * prepared statement. It must include question marks for binding
			 * the values from the order item to the statement before they are
			 * sent to the database server. The number of question marks must
			 * be the same as the number of columns listed in the INSERT
			 * statement. The order of the columns must correspond to the
			 * order in the rebuild() method, so the correct values are
			 * bound to the columns.
			 *
			 * The SQL statement should conform to the ANSI standard to be
			 * compatible with most relational database systems. This also
			 * includes using double quotes for table and column names.
			 *
			 * @param string SQL statement for inserting records
			 * @since 2014.03
			 * @see mshop/index/manager/price/cleanup/ansi
			 * @see mshop/index/manager/price/delete/ansi
			 * @see mshop/index/manager/price/search/ansi
			 * @see mshop/index/manager/price/count/ansi
			 */
			$stmt = $this->getCachedStatement( $conn, 'mshop/index/manager/price/insert' );

			foreach( $items as $item ) {
				$this->savePrices( $stmt, $item );
			}

		foreach( $this->getSubManagers() as $submanager ) {
			$submanager->rebuild( $items );
		}

		return $this;
	}


	/**
	 * Removes the products from the product index.
	 *
	 * @param iterable|string $ids Product ID or list of IDs
	 * @return \Aimeos\MShop\Index\Manager\Iface Manager object for chaining method calls
	 */
	public function remove( $ids ) : \Aimeos\MShop\Index\Manager\Iface
	{
		parent::remove( $ids )->delete( $ids );
		return $this;
	}


	/**
	 * Searches for items matching the given criteria.
	 *
	 * @param \Aimeos\Base\Criteria\Iface $search Search criteria object
	 * @param string[] $ref List of domains to fetch list items and referenced items for
	 * @param int|null &$total Number of items that are available in total
	 * @return \Aimeos\Map List of items implementing \Aimeos\MShop\Product\Item\Iface with ids as keys
	 */
	public function search( \Aimeos\Base\Criteria\Iface $search, array $ref = [], ?int &$total = null ) : \Aimeos\Map
	{
		/** mshop/index/manager/price/search/mysql
		 * Retrieves the records matched by the given criteria in the database
		 *
		 * @see mshop/index/manager/price/search/ansi
		 */

		/** mshop/index/manager/price/search/ansi
		 * Retrieves the records matched by the given criteria in the database
		 *
		 * Fetches the records matched by the given criteria from the product index
		 * database. The records must be from one of the sites that are
		 * configured via the context item. If the current site is part of
		 * a tree of sites, the SELECT statement can retrieve all records
		 * from the current site and the complete sub-tree of sites.
		 *
		 * As the records can normally be limited by criteria from sub-managers,
		 * their tables must be joined in the SQL context. This is done by
		 * using the "internaldeps" property from the definition of the ID
		 * column of the sub-managers. These internal dependencies specify
		 * the JOIN between the tables and the used columns for joining. The
		 * ":joins" placeholder is then replaced by the JOIN strings from
		 * the sub-managers.
		 *
		 * To limit the records matched, conditions can be added to the given
		 * criteria object. It can contain comparisons like column names that
		 * must match specific values which can be combined by AND, OR or NOT
		 * operators. The resulting string of SQL conditions replaces the
		 * ":cond" placeholder before the statement is sent to the database
		 * server.
		 *
		 * If the records that are retrieved should be ordered by one or more
		 * columns, the generated string of column / sort direction pairs
		 * replaces the ":order" placeholder. Columns of
		 * sub-managers can also be used for ordering the result set but then
		 * no index can be used.
		 *
		 * The number of returned records can be limited and can start at any
		 * number between the begining and the end of the result set. For that
		 * the ":size" and ":start" placeholders are replaced by the
		 * corresponding values from the criteria object. The default values
		 * are 0 for the start and 100 for the size value.
		 *
		 * The SQL statement should conform to the ANSI standard to be
		 * compatible with most relational database systems. This also
		 * includes using double quotes for table and column names.
		 *
		 * @param string SQL statement for searching items
		 * @since 2014.03
		 * @see mshop/index/manager/price/count/ansi
		 * @see mshop/index/manager/price/optimize/ansi
		 * @see mshop/index/manager/price/aggregate/ansi
		 */
		$cfgPathSearch = 'mshop/index/manager/price/search';

		/** mshop/index/manager/price/count/mysql
		 * Counts the number of records matched by the given criteria in the database
		 *
		 * @see mshop/index/manager/price/count/ansi
		 */

		/** mshop/index/manager/price/count/ansi
		 * Counts the number of records matched by the given criteria in the database
		 *
		 * Counts all records matched by the given criteria from the product index
		 * database. The records must be from one of the sites that are
		 * configured via the context item. If the current site is part of
		 * a tree of sites, the statement can count all records from the
		 * current site and the complete sub-tree of sites.
		 *
		 * As the records can normally be limited by criteria from sub-managers,
		 * their tables must be joined in the SQL context. This is done by
		 * using the "internaldeps" property from the definition of the ID
		 * column of the sub-managers. These internal dependencies specify
		 * the JOIN between the tables and the used columns for joining. The
		 * ":joins" placeholder is then replaced by the JOIN strings from
		 * the sub-managers.
		 *
		 * To limit the records matched, conditions can be added to the given
		 * criteria object. It can contain comparisons like column names that
		 * must match specific values which can be combined by AND, OR or NOT
		 * operators. The resulting string of SQL conditions replaces the
		 * ":cond" placeholder before the statement is sent to the database
		 * server.
		 *
		 * Both, the strings for ":joins" and for ":cond" are the same as for
		 * the "search" SQL statement.
		 *
		 * Contrary to the "search" statement, it doesn't return any records
		 * but instead the number of records that have been found. As counting
		 * thousands of records can be a long running task, the maximum number
		 * of counted records is limited for performance reasons.
		 *
		 * The SQL statement should conform to the ANSI standard to be
		 * compatible with most relational database systems. This also
		 * includes using double quotes for table and column names.
		 *
		 * @param string SQL statement for counting items
		 * @since 2014.03
		 * @see mshop/index/manager/price/search/ansi
		 * @see mshop/index/manager/price/optimize/ansi
		 * @see mshop/index/manager/price/aggregate/ansi
		 */
		$cfgPathCount = 'mshop/index/manager/price/count';

		return $this->searchItemsIndexBase( $search, $ref, $total, $cfgPathSearch, $cfgPathCount );
	}


	/**
	 * Returns the list of sub-managers available for the index attribute manager.
	 *
	 * @return \Aimeos\MShop\Index\Manager\Iface[] Associative list of the sub-domain as key and the manager object as value
	 */
	protected function getSubManagers() : array
	{
		if( $this->subManagers === null )
		{
			$this->subManagers = [];
			$config = $this->context()->config();

			/** mshop/index/manager/price/submanagers
			 * A list of sub-manager names used for indexing associated items to prices
			 *
			 * All items referenced by a product (e.g. texts, prices, media,
			 * etc.) are added to the product index via specialized index
			 * managers. You can add the name of new sub-managers to add more
			 * data to the index or remove existing ones if you don't want to
			 * index that data at all.
			 *
			 * This option configures the sub-managers that cares about
			 * indexing data associated to product prices.
			 *
			 * @param string List of index sub-manager names
			 * @since 2014.09
			 * @see mshop/index/manager/submanagers
			 */
			foreach( $config->get( 'mshop/index/manager/price/submanagers', [] ) as $domain )
			{
				$name = $config->get( 'mshop/index/manager/price/' . $domain . '/name' );
				$this->subManagers[$domain] = $this->object()->getSubManager( $domain, $name );
			}

			return $this->subManagers;
		}

		return $this->subManagers;
	}


	/**
	 * Saves the text items referenced indirectly by products
	 *
	 * @param \Aimeos\Base\DB\Statement\Iface $stmt Prepared SQL statement with place holders
	 * @param \Aimeos\MShop\Product\Item\Iface $item Product item containing associated price items
	 */
	protected function savePrices( \Aimeos\Base\DB\Statement\Iface $stmt, \Aimeos\MShop\Common\Item\ListsRef\Iface $item )
	{
		$prices = [];
		$context = $this->context();
		$siteid = $context->locale()->getSiteId();

		/** mshop/index/manager/price/types
		 * Use different product prices types for indexing
		 *
		 * In some cases, prices are stored with different types, eg. price per kg.
		 * This configuration option defines which types are incorporated in which
		 * order. If a price of the defined type with the lowest index is available,
		 * it will be indexed, otherwise the next lowest index price type. It is
		 * highly recommended to add the price type 'default' with the highest index.
		 *
		 * @param array List of price types codes
		 * @since 2019.04
		 */
		$types = $context->config()->get( 'mshop/index/manager/price/types', ['default'] );

		foreach( $types as $priceType )
		{
			foreach( $item->getListItems( 'price', 'default', $priceType ) as $listItem )
			{
				if( ( $refItem = $listItem->getRefItem() ) && $refItem->isAvailable() ) {
					$prices[$refItem->getCurrencyId()][$refItem->getQuantity()] = $refItem->getValue();
				}
			}
		}

		foreach( $prices as $currencyId => $list )
		{
			ksort( $list );

			$stmt->bind( 1, $item->getId(), \Aimeos\Base\DB\Statement\Base::PARAM_INT );
			$stmt->bind( 2, $currencyId );
			$stmt->bind( 3, reset( $list ) );
			$stmt->bind( 4, $context->datetime() ); // mtime
			$stmt->bind( 5, $siteid );

			try {
				$stmt->execute()->finish();
			} catch( \Aimeos\Base\DB\Exception $e ) { ; } // Ignore duplicates
		}
	}
}
