<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2011
 * @copyright Aimeos (aimeos.org), 2015-2025
 * @package MW
 * @subpackage Tree
 */


namespace Aimeos\MW\Tree\Manager;


/**
 * Tree manager using nested sets stored in a database.
 *
 * @package MW
 * @subpackage Tree
 */
class DBNestedSet extends \Aimeos\MW\Tree\Manager\Base
{
	private array $searchConfig = [];
	private array $config;
	private \Aimeos\Base\DB\Connection\Iface $conn;


	/**
	 * Initializes the tree manager.
	 *
	 * The config['search] array must contain these key/array pairs suitable for \Aimeos\Base\Criteria\Attribute\Standard:
	 *	[id] => Array describing unique ID codes/types/labels
	 *	[label] => Array describing codes/types/labels for descriptive labels
	 *	[status] => Array describing codes/types/labels for status values
	 *	[parentid] => Array describing codes/types/labels for parentid values
	 *	[level] => Array describing codes/types/labels for height levels of tree nodes
	 *	[left] => Array describing codes/types/labels for nodes left values
	 *	[right] => Array describing codes/types/labels for nodes right values
	 *
	 * The config['sql] array must contain these statement:
	 *	[delete] =>
	 *		DELETE FROM treetable WHERE left >= ? AND right <= ?
	 *	[get] =>
	 *		SELECT node.*
	 *		FROM treetable AS parent, treetable AS node
	 *		WHERE node.left >= parent.left AND node.left <= parent.right
	 *		AND parent.id = ? AND node.level <= parent.level + ? AND :cond
	 *		ORDER BY node.left
	 *	[insert] =>
	 *		INSERT INTO treetable ( label, code, status, parentid, level, left, right ) VALUES ( ?, ?, ?, ?, ? )
	 *	[move-left] =>
	 *		UPDATE treetable
	 *		SET left = left + ?, level = level + ?
	 *		WHERE left >= ? AND left <= ?
	 *	[move-right] =>
	 *		UPDATE treetable
	 *		SET right = right + ?
	 *		WHERE right >= ? AND right <= ?
	 *	[search] =>
	 *		SELECT * FROM treetable
	 *		WHERE left >= ? AND right <= ? AND :cond
	 *		ORDER BY :order
	 *	[update] =>
	 *		UPDATE treetable SET label = ?, code = ?, status = ? WHERE id = ?
	 *	[update-parentid] =>
	 *		UPDATE treetable SET parentid = ? WHERE id = ?
	 *	[newid] =>
	 *		SELECT LAST_INSERT_ID()
	 *
	 * @param array $config Associative array holding the SQL statements
	 * @param \Aimeos\Base\DB\Connection\Iface $resource Database connection
	 */
	public function __construct( array $config, \Aimeos\Base\DB\Connection\Iface $resource )
	{
		if( !isset( $config['search'] ) ) {
			throw new \Aimeos\MW\Tree\Exception( 'Search config is missing' );
		}

		if( !isset( $config['sql'] ) ) {
			throw new \Aimeos\MW\Tree\Exception( 'SQL config is missing' );
		}

		$this->checkSearchConfig( $config['search'] );
		$this->checkSqlConfig( $config['sql'] );

		$this->searchConfig = $config['search'];
		$this->config = $config['sql'];
		$this->conn = $resource;
	}


	/**
	 * Returns a list of attributes which can be used in the search method.
	 *
	 * @return \Aimeos\Base\Criteria\Attribute\Iface[] List of search attribute items
	 */
	public function getSearchAttributes() : array
	{
		$attributes = [];

		foreach( $this->searchConfig as $values ) {
			$attributes[] = new \Aimeos\Base\Criteria\Attribute\Standard( $values );
		}

		return $attributes;
	}


	/**
	 * Creates a new search object for storing search criterias.
	 *
	 * @return \Aimeos\Base\Criteria\Iface Search object instance
	 */
	public function createSearch() : \Aimeos\Base\Criteria\Iface
	{
		return new \Aimeos\Base\Criteria\SQL( $this->conn );
	}


	/**
	 * Creates a new node object.
	 *
	 * @return \Aimeos\MW\Tree\Node\Iface Empty node object
	 */
	public function createNode() : \Aimeos\MW\Tree\Node\Iface
	{
		return $this->createNodeBase();
	}


	/**
	 * Deletes a node and its descendants from the storage.
	 *
	 * @param string|null $id Delete the node with the ID and all nodes below
	 * @return \Aimeos\MW\Tree\Manager\Iface Manager object for method chaining
	 */
	public function deleteNode( ?string $id = null ) : Iface
	{
		$node = $this->getNode( $id, \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );

		$stmt = $this->conn->create( $this->config['delete'] );
		$stmt->bind( 1, $node->left, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, $node->right, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->execute()->finish();

		$diff = $node->right - $node->left + 1;

		$stmt = $this->conn->create( $this->config['move-left'] );
		$stmt->bind( 1, -$diff, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, 0, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 3, $node->right + 1, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 4, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->execute()->finish();

		$stmt = $this->conn->create( $this->config['move-right'] );
		$stmt->bind( 1, -$diff, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, $node->right + 1, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 3, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->execute()->finish();

		return $this;
	}


	/**
	 * Returns a node and its descendants depending on the given resource.
	 *
	 * @param string|null $id Retrieve nodes starting from the given ID
	 * @param int $level One of the level constants from \Aimeos\MW\Tree\Manager\Base
	 * @param \Aimeos\Base\Criteria\Iface|null $condition Optional criteria object with conditions
	 * @return \Aimeos\MW\Tree\Node\Iface Node, maybe with subnodes
	 */
	public function getNode( ?string $id = null, int $level = Base::LEVEL_TREE, ?\Aimeos\Base\Criteria\Iface $condition = null ) : \Aimeos\MW\Tree\Node\Iface
	{
		if( $id === null )
		{
			if( ( $node = $this->getRootNode() ) === null ) {
				throw new \Aimeos\MW\Tree\Exception( 'No root node available' );
			}

			if( $level === \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE ) {
				return $node;
			}
		}
		else
		{
			$node = $this->getNodeById( $id );

			if( $level === \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE ) {
				return $node;
			}
		}


		$id = $node->getId();

		$numlevel = $this->getLevelFromConstant( $level );
		$search = $condition ?: $this->createSearch();

		$types = $this->getSearchTypes( $this->searchConfig );
		$funcs = $this->getSearchFunctions( $this->searchConfig );
		$translations = $this->getSearchTranslations( $this->searchConfig );
		$conditions = $search->getConditionSource( $types, $translations, [], $funcs );


		$stmt = $this->conn->create( str_replace( ':cond', $conditions, $this->config['get'] ) );
		$stmt->bind( 1, $id, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, $numlevel, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$result = $stmt->execute();

		if( ( $row = $result->fetch() ) === null ) {
			throw new \Aimeos\MW\Tree\Exception( sprintf( 'No node with ID "%1$d" found', $id ) );
		}

		$node = $this->createNodeBase( $row );
		$this->createTree( $result, $node );

		return $node;
	}


	/**
	 * Inserts a new node before the given reference node to the parent in the storage.
	 *
	 * @param \Aimeos\MW\Tree\Node\Iface $node New node that should be inserted
	 * @param string|null $parentId ID of the parent node where the new node should be inserted below (null for root node)
	 * @param string|null $refId ID of the node where the node should be inserted before (null to append)
	 * @return \Aimeos\MW\Tree\Node\Iface Updated node item
	 */
	public function insertNode( \Aimeos\MW\Tree\Node\Iface $node, ?string $parentId = null, ?string $refId = null ) : \Aimeos\MW\Tree\Node\Iface
	{
		$node->parentid = $parentId;

		if( $refId !== null )
		{
			$refNode = $this->getNode( $refId, \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );
			$node->left = $refNode->left;
			$node->right = $refNode->left + 1;
			$node->level = $refNode->level;
		}
		else if( $parentId !== null )
		{
			$parentNode = $this->getNode( $parentId, \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );
			$node->left = $parentNode->right;
			$node->right = $parentNode->right + 1;
			$node->level = $parentNode->level + 1;
		}
		else
		{
			$node->left = 1;
			$node->right = 2;
			$node->level = 0;
			$node->parentid = 0;

			if( ( $root = $this->getRootNode( '-' ) ) !== null )
			{
				$node->left = $root->right + 1;
				$node->right = $root->right + 2;
			}
		}


		$stmt = $this->conn->create( $this->config['move-left'] );
		$stmt->bind( 1, 2, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, 0, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 3, $node->left, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 4, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->execute()->finish();

		$stmt = $this->conn->create( $this->config['move-right'] );
		$stmt->bind( 1, 2, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, $node->left, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 3, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->execute()->finish();

		$stmt = $this->conn->create( $this->config['insert'] );
		$stmt->bind( 1, $node->getLabel(), \Aimeos\Base\DB\Statement\Base::PARAM_STR );
		$stmt->bind( 2, $node->getCode(), \Aimeos\Base\DB\Statement\Base::PARAM_STR );
		$stmt->bind( 3, $node->getStatus(), \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 4, (int) $node->parentid, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 5, $node->level, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 6, $node->left, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 7, $node->right, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->execute()->finish();


		$result = $this->conn->create( $this->config['newid'] )->execute();

		if( ( $row = $result->fetch( \Aimeos\Base\DB\Result\Base::FETCH_NUM ) ) === false ) {
			throw new \Aimeos\MW\Tree\Exception( sprintf( 'No new record ID available' ) );
		}
		$result->finish();

		$node->setId( $row[0] );

		return $node;
	}


	/**
	 * Moves an existing node to the new parent in the storage.
	 *
	 * @param string $id ID of the node that should be moved
	 * @param string|null $oldParentId ID of the old parent node which currently contains the node that should be removed
	 * @param string|null $newParentId ID of the new parent node where the node should be moved to
	 * @param string|null $newRefId ID of the node where the node should be inserted before (null to append)
	 * @return \Aimeos\MW\Tree\Manager\Iface Manager object for method chaining
	 */
	public function moveNode( string $id, ?string $oldParentId = null, ?string $newParentId = null, ?string $newRefId = null ) : Iface
	{
		$node = $this->getNode( $id, \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );
		$diff = $node->right - $node->left + 1;

		if( $newRefId !== null )
		{
			$refNode = $this->getNode( $newRefId, \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );

			$leveldiff = $refNode->level - $node->level;

			$openNodeLeftBegin = $refNode->left;
			$openNodeRightBegin = $refNode->left + 1;

			if( $refNode->left < $node->left )
			{
				$moveNodeLeftBegin = $node->left + $diff;
				$moveNodeLeftEnd = $node->right + $diff - 1;
				$moveNodeRightBegin = $node->left + $diff + 1;
				$moveNodeRightEnd = $node->right + $diff;
				$movesize = $refNode->left - $node->left - $diff;
			}
			else
			{
				$moveNodeLeftBegin = $node->left;
				$moveNodeLeftEnd = $node->right - 1;
				$moveNodeRightBegin = $node->left + 1;
				$moveNodeRightEnd = $node->right;
				$movesize = $refNode->left - $node->left;
			}

			$closeNodeLeftBegin = $node->left + $diff;
			$closeNodeRightBegin = $node->left + $diff;
		}
		else
		{
			$refNode = $this->getNode( $newParentId, \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );

			if( $newParentId === null )
			{
				//make virtual root
				if( ( $root = $this->getRootNode( '-' ) ) !== null )
				{
					$refNode->left = $root->right;
					$refNode->right = $root->right + 1;
					$refNode->level = -1;
				}
			}

			$leveldiff = $refNode->level - $node->level + 1;
			$openNodeLeftBegin = $refNode->right + 1;
			$openNodeRightBegin = $refNode->right;

			if( $refNode->right < $node->right )
			{
				$moveNodeLeftBegin = $node->left + $diff;
				$moveNodeLeftEnd = $node->right + $diff - 1;
				$moveNodeRightBegin = $node->left + $diff + 1;
				$moveNodeRightEnd = $node->right + $diff;
				$movesize = $refNode->right - $node->left - $diff;
			}
			else
			{
				$moveNodeLeftBegin = $node->left;
				$moveNodeLeftEnd = $node->right - 1;
				$moveNodeRightBegin = $node->left + 1;
				$moveNodeRightEnd = $node->right;
				$movesize = $refNode->right - $node->left;
			}

			$closeNodeLeftBegin = $node->left + $diff;
			$closeNodeRightBegin = $node->left + $diff;
		}


		$stmtLeft = $this->conn->create( $this->config['move-left'] );
		$stmtRight = $this->conn->create( $this->config['move-right'] );
		$updateParentId = $this->conn->create( $this->config['update-parentid'] );
		// open gap for inserting node or subtree

		$stmtLeft->bind( 1, $diff, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 2, 0, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 3, $openNodeLeftBegin, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 4, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->execute()->finish();

		$stmtRight->bind( 1, $diff, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->bind( 2, $openNodeRightBegin, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->bind( 3, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->execute()->finish();

		// move node or subtree to the new gap

		$stmtLeft->bind( 1, $movesize, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 2, $leveldiff, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 3, $moveNodeLeftBegin, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 4, $moveNodeLeftEnd, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->execute()->finish();

		$stmtRight->bind( 1, $movesize, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->bind( 2, $moveNodeRightBegin, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->bind( 3, $moveNodeRightEnd, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->execute()->finish();

		// close gap opened by moving the node or subtree to the new location

		$stmtLeft->bind( 1, -$diff, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 2, 0, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 3, $closeNodeLeftBegin, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->bind( 4, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtLeft->execute()->finish();

		$stmtRight->bind( 1, -$diff, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->bind( 2, $closeNodeRightBegin, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->bind( 3, 0x7FFFFFFF, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmtRight->execute()->finish();


		$updateParentId->bind( 1, (int) $newParentId, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$updateParentId->bind( 2, $id, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$updateParentId->execute()->finish();

		return $this;
	}


	/**
	 * Stores the values of the given node to the storage.
	 *
	 * This method does only store values like the node label but doesn't change
	 * the tree layout by adding, moving or deleting nodes.
	 *
	 * @param \Aimeos\MW\Tree\Node\Iface $node Tree node object
	 * @return \Aimeos\MW\Tree\Node\Iface Updated node item
	 */
	public function saveNode( \Aimeos\MW\Tree\Node\Iface $node ) : \Aimeos\MW\Tree\Node\Iface
	{
		if( $node->getId() === null ) {
			throw new \Aimeos\MW\Tree\Exception( sprintf( 'Unable to save newly created nodes, use insert method instead' ) );
		}

		if( $node->isModified() === false ) {
			return $node;
		}

		$stmt = $this->conn->create( $this->config['update'] );
		$stmt->bind( 1, $node->getLabel(), \Aimeos\Base\DB\Statement\Base::PARAM_STR );
		$stmt->bind( 2, $node->getCode(), \Aimeos\Base\DB\Statement\Base::PARAM_STR );
		$stmt->bind( 3, $node->getStatus(), \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 4, $node->getId(), \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->execute()->finish();

		return $node;
	}


	/**
	 * Retrieves a list of nodes from the storage matching the given search criteria.
	 *
	 * @param \Aimeos\Base\Criteria\Iface $search Search criteria object
	 * @param string|null $id Search nodes starting at the node with the given ID
	 * @return \Aimeos\MW\Tree\Node\Iface[] List of tree nodes
	 */
	public function searchNodes( \Aimeos\Base\Criteria\Iface $search, ?string $id = null ) : array
	{
		$left = 1;
		$right = 0x7FFFFFFF;

		if( $id !== null )
		{
			$node = $this->getNodeById( $id );

			$left = $node->left;
			$right = $node->right;
		}

		if( $search->getSortations() === [] ) {
			$search->setSortations( [$search->sort( '+', $this->searchConfig['left']['code'] )] );
		}

		$types = $this->getSearchTypes( $this->searchConfig );
		$funcs = $this->getSearchFunctions( $this->searchConfig );
		$translations = $this->getSearchTranslations( $this->searchConfig );
		$conditions = $search->getConditionSource( $types, $translations, [], $funcs );
		$sortations = $search->getSortationSource( $types, $translations, $funcs );

		$sql = str_replace(
			[':cond', ':order', ':size', ':start'],
			[$conditions, $sortations, $search->getLimit(), $search->getOffset()],
			$this->config['search']
		);

		$stmt = $this->conn->create( $sql );
		$stmt->bind( 1, $left, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, $right, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$result = $stmt->execute();

		try
		{
			$nodes = [];
			while( $row = $result->fetch() ) {
				$nodes[$row['id']] = $this->createNodeBase( $row );
			}
		}
		catch( \Exception $e )
		{
			$result->finish();
			throw $e;
		}

		return $nodes;
	}


	/**
	 * Returns a list if node IDs, that are in the path of given node ID.
	 *
	 * @param string $id ID of node to get the path for
	 * @return \Aimeos\MW\Tree\Node\Iface[] List of tree nodes
	 */
	public function getPath( string $id ) : array
	{
		$result = [];
		$node = $this->getNode( $id, \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );

		$search = $this->createSearch();

		$expr = array(
			$search->compare( '<=', $this->searchConfig['left']['code'], $node->left ),
			$search->compare( '>=', $this->searchConfig['right']['code'], $node->right ),
		);

		$search->setConditions( $search->and( $expr ) );
		$search->setSortations( array( $search->sort( '+', $this->searchConfig['left']['code'] ) ) );

		foreach( $this->searchNodes( $search ) as $item ) {
			$result[$item->getId()] = $item;
		}

		return $result;
	}


	/**
	 * Checks if all required search configurations are available.
	 *
	 * @param array $config Associative list of search configurations
	 * @throws \Aimeos\MW\Tree\Exception If one ore more search configurations are missing
	 */
	protected function checkSearchConfig( array $config )
	{
		$required = array( 'id', 'label', 'status', 'level', 'left', 'right' );

		foreach( $required as $key => $entry )
		{
			if( isset( $config[$entry] ) ) {
				unset( $required[$key] );
			}
		}

		if( count( $required ) > 0 )
		{
			$msg = 'Search config in given configuration are missing: "%1$s"';
			throw new \Aimeos\MW\Tree\Exception( sprintf( $msg, implode( ', ', $required ) ) );
		}
	}


	/**
	 * Checks if all required SQL statements are available.
	 *
	 * @param array $config Associative list of SQL statements
	 * @throws \Aimeos\MW\Tree\Exception If one ore more SQL statements are missing
	 */
	protected function checkSqlConfig( array $config )
	{
		$required = array(
			'delete', 'get', 'insert', 'move-left',
			'move-right', 'search', 'update', 'newid'
		);

		foreach( $required as $key => $entry )
		{
			if( isset( $config[$entry] ) ) {
				unset( $required[$key] );
			}
		}

		if( count( $required ) > 0 )
		{
			$msg = 'SQL statements in given configuration are missing: "%1$s"';
			throw new \Aimeos\MW\Tree\Exception( sprintf( $msg, implode( ', ', $required ) ) );
		}
	}


	/**
	 * Creates a new node object.
	 *
	 * @param array $values List of attributes that should be stored in the new node
	 * @param \Aimeos\MW\Tree\Node\Iface[] $children List of child nodes
	 * @return \Aimeos\MW\Tree\Node\Iface Empty node object
	 */
	protected function createNodeBase( array $values = [], array $children = [] ) : \Aimeos\MW\Tree\Node\Iface
	{
		return new \Aimeos\MW\Tree\Node\DBNestedSet( $values, $children );
	}


	/**
	 * Creates a tree from the result set returned by the database.
	 *
	 * @param \Aimeos\Base\DB\Result\Iface $result Database result
	 * @param \Aimeos\MW\Tree\Node\Iface $node Current node to add children to
	 */
	protected function createTree( \Aimeos\Base\DB\Result\Iface $result, \Aimeos\MW\Tree\Node\Iface $node ) : ?\Aimeos\MW\Tree\Node\Iface
	{
		while( $record = $result->fetch() )
		{
			$newNode = $this->createNodeBase( $record );

			while( $this->isChild( $newNode, $node ) )
			{
				if( $newNode->__get( 'level' ) > $node->__get( 'level' ) + 1 ) {
					continue 2;
				}

				$node->addChild( $newNode );

				if( ( $newNode = $this->createTree( $result, $newNode ) ) === null ) {
					return null;
				}
			}

			return $newNode;
		}

		return null;
	}


	/**
	 * Tests if the first node is a child of the second node.
	 *
	 * @param \Aimeos\MW\Tree\Node\Iface $node Node to test
	 * @param \Aimeos\MW\Tree\Node\Iface $parent Parent node
	 * @return bool True if not is a child of the second node, false if not
	 */
	protected function isChild( \Aimeos\MW\Tree\Node\Iface $node, \Aimeos\MW\Tree\Node\Iface $parent ) : bool
	{
		return $node->__get( 'left' ) > $parent->__get( 'left' ) && $node->__get( 'right' ) < $parent->__get( 'right' );
	}


	/**
	 * Converts the level constant to the depth of the tree.
	 *
	 * @param int $level Level constant from \Aimeos\MW\Tree\Manager\Base
	 * @return int Number of tree levels
	 * @throws \Aimeos\MW\Tree\Exception if level constant is invalid
	 */
	protected function getLevelFromConstant( int $level ) : int
	{
		switch( $level )
		{
			case \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE:
				return 0;
			case \Aimeos\MW\Tree\Manager\Base::LEVEL_LIST:
				return 1;
			case \Aimeos\MW\Tree\Manager\Base::LEVEL_TREE:
				return 0x3FFF; // max. possible level / 2 to prevent smallint overflow
			default:
				throw new \Aimeos\MW\Tree\Exception( sprintf( 'Invalid level constant "%1$d"', $level ) );
		}
	}


	/**
	 * Returns a single node identified by its ID.
	 *
	 * @param string $id Unique ID
	 * @return \Aimeos\MW\Tree\Node\Iface Tree node
	 * @throws \Aimeos\MW\Tree\Exception If node is not found
	 * @throws \Exception If anything unexcepted occurs
	 */
	protected function getNodeById( string $id ) : \Aimeos\MW\Tree\Node\Iface
	{
		$stmt = $this->conn->create( str_replace( ':cond', '1=1', $this->config['get'] ) );
		$stmt->bind( 1, $id, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$stmt->bind( 2, 0, \Aimeos\Base\DB\Statement\Base::PARAM_INT );
		$result = $stmt->execute();

		if( ( $row = $result->fetch() ) === null ) {
			throw new \Aimeos\MW\Tree\Exception( sprintf( 'No node with ID "%1$d" found', $id ) );
		}

		return $this->createNodeBase( $row );
	}


	/**
	 * Returns the first tree root node depending on the sorting direction.
	 *
	 * @param string $sort Sort direction, '+' is ascending, '-' is descending
	 * @return \Aimeos\MW\Tree\Node\Iface|null Tree root node
	 */
	protected function getRootNode( string $sort = '+' ) : ?\Aimeos\MW\Tree\Node\Iface
	{
		$search = $this->createSearch();
		$search->setConditions( $search->compare( '==', $this->searchConfig['level']['code'], 0 ) );
		$search->setSortations( array( $search->sort( $sort, $this->searchConfig['left']['code'] ) ) );
		$nodes = $this->searchNodes( $search );

		if( ( $node = reset( $nodes ) ) !== false ) {
			return $node;
		}

		return null;
	}
}
