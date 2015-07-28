<?php

namespace Janrain\CLI;

use Janrain\Client;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Application extends BaseApplication {
	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Client
	 */
	protected $client;

	/**
	 * @var ContainerBuilder
	 */
	protected $container;

	public function __construct( $name, $version, Config $config, Client $client ) {
		parent::__construct( $name, $version );

		$this->config = $config;
		$this->client = $client;
	}

	public function getContainer() {
		if ( null === $this->container ) {
			$this->container = $this->createContainer();
		}

		return $this->container;
	}

	protected function createContainer() {
		$container = new ContainerBuilder();

		$container->set( 'config', $this->config );
		$container->set( 'client', $this->client );

		return $container;
	}
}
