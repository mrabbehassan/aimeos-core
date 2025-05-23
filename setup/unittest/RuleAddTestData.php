<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2025
 */


namespace Aimeos\Upscheme\Task;


/**
 * Adds rule test data and all items from other domains.
 */
class RuleAddTestData extends Base
{
	/**
	 * Returns the list of task names which this task depends on.
	 *
	 * @return string[] List of task names
	 */
	public function after() : array
	{
		return ['Rule', 'MShopSetLocale'];
	}


	/**
	 * Adds rule test data.
	 */
	public function up()
	{
		$this->info( 'Adding rule test data', 'vv' );
		$this->context()->setEditor( 'core' );

		$this->addRuleData();
	}


	/**
	 * Adds the rule test data.
	 *
	 * @throws \RuntimeException If no type ID is found
	 */
	private function addRuleData()
	{
		$path = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'rule.php';

		if( ( $testdata = include( $path ) ) == false ) {
			throw new \RuntimeException( sprintf( 'No file "%1$s" found for rule domain', $path ) );
		}

		$ruleManager = \Aimeos\MShop::create( $this->context(), 'rule', 'Standard' );
		$ruleManager->begin();

		foreach( $testdata['rule'] as $dataset ) {
			$ruleManager->save( $ruleManager->create()->fromArray( $dataset ), false );
		}

		$ruleManager->commit();
	}
}
