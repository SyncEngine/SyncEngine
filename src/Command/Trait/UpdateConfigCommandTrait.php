<?php

namespace SyncEngine\Command\Trait;

trait UpdateConfigCommandTrait
{
	/**
	 * Pass a key to only replace the value of that key.
	 * Pass `true` for $key to find and replace the key.
	 *
	 * @param $config
	 * @param $find
	 * @param $replace
	 * @param callable $key
	 *
	 * @return array|mixed
	 */
	protected function findReplaceRecursive( $config, $find, $replace, ?callable $condition = null )
	{
		if ( ! is_iterable( $config ) ) {
			if ( $config === $find ) {
				return $replace;
			}
			return $config;
		}

		foreach ( $config as $k => $v ) {
			if ( $v === $find && ( ! $condition || $condition( $k, $v, $config ) ) ) {
				$config[ $k ] = $replace;
				continue;
			}

			if ( is_iterable( $v ) ) {
				$config[ $k ] = $this->findReplaceRecursive( $v, $find, $replace, $condition );
			}
		}

		return $config;
	}

	/**
	 * Pass a key to only replace the value of that key.
	 * Pass `true` for $key to find and replace the key.
	 *
	 * @param $config
	 * @param $find
	 * @param $replace
	 * @param callable $key
	 *
	 * @return array|mixed
	 */
	protected function findReplaceKeyRecursive( $config, $find, $replace, ?callable $condition = null )
	{
		if ( ! is_iterable( $config ) ) {
			return $config;
		}

		foreach ( $config as $k => $v ) {
			if ( is_iterable( $v ) ) {
				$config[ $k ] = $this->findReplaceKeyRecursive( $v, $find, $replace );
			}

			if ( $k === $find && ( ! $condition || $condition( $k, $v, $config ) ) ) {
				$config[ $replace ] = $config[ $k ];
				unset( $config[ $k ] );
			}
		}

		return $config;
	}
}
