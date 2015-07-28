<?php

namespace Janrain\CLI;

class Config {

	private $config;

	private $filePath;

	public function __construct( $filePath ) {
		$this->filePath = $filePath;
		if ( ! file_exists( $this->filePath ) ) {
			$this->config = $this->getDefaultConfig();
			file_put_contents( $this->filePath, json_encode( $this->config ) );
		} else {
			$this->config = json_decode( file_get_contents( $this->filePath ), true);
		}
	}

	/**
	 * Get default config properties.
	 *
	 * @return array
	 */
	private function getDefaultConfig() {
		return array(
			'client_id'     => '',
			'client_secret' => '',
			'base_url'      => '',
			'default_type'  => 'user',
		);
	}

	/**
	 * Get config value from given key.
	 *
	 * @param  string $key Config's key
	 * @return mixed
	 */
	public function get( $key ) {
		if ( isset( $this->config[ $key ] ) ) {
			return $this->config[ $key ];
		}
		return null;
	}

	/**
	 * Return all configs.
	 *
	 * @return mixed
	 */
	public function getAll() {
		return $this->config;
	}

	/**
	 * Set config.
	 *
	 * @param string $key   Config's key
	 * @param mixed  $value Config's value
	 *
	 * @throws \Exception
	 */
	public function set( $key, $value ) {
		switch ( $key ) {
			case 'client_id':
				if ( ! preg_match( '/[a-zA-Z0-9]{32}/', $value ) ) {
					throw new \Exception( 'Invalid value for client_id' );
					return;
				}
				$this->config[ $key ] = $value;
				break;
			case 'client_secret':
				if ( ! preg_match( '/[a-zA-Z0-9]{32}/', $value ) ) {
					throw new \Exception( 'Invalid value for client_secret' );
					return;
				}
				$this->config[ $key ] = $value;
				break;
			case 'base_url':
				if ( false === filter_var( $value, FILTER_VALIDATE_URL ) ) {
					throw new \Exception( 'Invalid value for base_url' );
					return;
				}
				$this->config[ $key ] = $value;
				break;
			case 'default_type':
				if ( ! preg_match( '/[a-zA-Z_\-]+/', $value ) ) {
					throw new \Exception( 'Invalid value for default_type' );
					return;
				}
				$this->config[ $key ] = $value;
				break;
			default:
				throw new \Exception( 'Unknown config key ' . $key );
				return;
		}

		file_put_contents( $this->filePath, json_encode( $this->config ) );
	}
}
