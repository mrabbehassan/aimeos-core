<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2025
 */


namespace Aimeos\Upscheme\Task;


class Product extends Base
{
	public function before() : array
	{
		return ['Locale'];
	}


	public function up()
	{
		$this->info( 'Creating product schema', 'vv' );
		$db = $this->db( 'db-product' );

		foreach( $this->paths( 'default/schema/product.php' ) as $filepath )
		{
			if( ( $list = include( $filepath ) ) === false ) {
				throw new \RuntimeException( sprintf( 'Unable to get schema from file "%1$s"', $filepath ) );
			}

			foreach( $list['table'] ?? [] as $name => $fcn ) {
				$db->table( $name, $fcn );
			}
		}
	}
}
