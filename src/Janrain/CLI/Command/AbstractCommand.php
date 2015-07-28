<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command {

	protected function get( $serviceName ) {
		return $this->getApplication()->getContainer()->get( $serviceName );
	}

	/**
	 * Get number of records of entity type.
	 *
	 * @param string $type   Entity type
	 * @param string $filter Records filter
	 *
	 * @throws \Exception
	 *
	 * @return int
	 */
	protected function getEntityCount( $type, $filter = '' ) {
		$client = $this->get( 'client' );
		$result = $client->api( 'entity' )->count( $type, $filter );

		$total_count = 0;
		if ( 'ok' === strtolower( $result['stat'] ) && ! empty( $result['total_count'] ) ) {
			$total_count = intval( $result['total_count'] );
		}

		return $total_count;
	}

	/**
	 * Flatten multidimensional array in which nested array will be prefixed with
	 * parent keys separated with dot char, e.g. given an array:
	 *
	 *     array(
	 *         'a' => array(
	 *             'b' => array(
	 *                 'c' => ...
	 *             )
	 *         )
	 *     )
	 *
	 * a flatten array would contain key 'a.b.c' => ...
	 *
	 * @param array  $arr    Array that may contains nested array
	 * @param string $prefix Prefix
	 *
	 * @return array Flattened array
	 */
	protected function flatten_array( $arr, $prefix = '' ) {
		$flattened = array();
		foreach ( $arr as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( sizeof( $value ) > 0 ) {
					$flattened = array_merge( $flattened, $this->flatten_array( $value, $prefix . $key . '.' ) );
				} else {
					$flattened[ $prefix . $key ] = '';
				}
			} else {
				$flattened[ $prefix . $key ] = $value;
			}
		}

		return $flattened;
	}

	/**
	 * Unflatten array will make key 'a.b.c' becomes nested array:
	 *
	 *     array(
	 *         'a' => array(
	 *             'b' => array(
	 *                 'c' => ...
	 *             )
	 *         )
	 *     )
	 *
	 * @param  array $arr Flattened array
	 * @return array
	 */
	protected function unflatten_array( $arr ) {
		$unflatten = array();

		foreach ( $arr as $key => $value ) {
			$key_list  = explode( '.', $key );
			$first_key = array_shift( $key_list );
			$first_key = $this->get_normalized_array_key( $first_key );
			if ( sizeof( $key_list ) > 0 ) {
				$remaining_keys = implode( '.', $key_list );
				$subarray       = $this->unflatten_array( array( $remaining_keys => $value ) );

				foreach ( $subarray as $sub_key => $sub_value ) {
					$sub_key = $this->get_normalized_array_key( $sub_key );
					if ( ! empty( $unflatten[ $first_key ][ $sub_key ] ) ) {
						$unflatten[ $first_key ][ $sub_key ] = array_merge( $unflatten[ $first_key ][ $sub_key ], $sub_value );
					} else {
						$unflatten[ $first_key ][ $sub_key ] = $sub_value;
					}
				}
			} else {
				$unflatten[ $first_key ] = $value;
			}
		}

		return $unflatten;
	}
}
