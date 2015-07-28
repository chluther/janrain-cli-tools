<?php

namespace Janrain\CLI;

use Janrain\Client;
use Janrain\CLI\Command;

class ApplicationFactory {
	const NAME = 'jcli';

	const VERSION = '@package_version@';

	public function createApplication() {
		$filePath = getenv( 'HOME' ) . '/.jcli.json';
		$config = new Config( $filePath );
		$client = new Client();
		$client->setOption( 'base_url', $config->get( 'base_url' ) );
		$client->setOption( 'client_id', $config->get( 'client_id' ) );
		$client->setOption( 'client_secret', $config->get( 'client_secret' ) );

		$application = new Application( self::NAME, self::VERSION, $config, $client );
		$application->addCommands( $this->getDefaultCommands() );

		return $application;
	}

	protected function getDefaultCommands() {
		return [
			new Command\ClientListCommand(),

			new Command\ConfigCommand(),

			new Command\EntityCountCommand(),
			new Command\EntityCreateCommand(),
			new Command\EntityDeleteCommand(),
			new Command\EntityFindCommand(),
			new Command\EntityFillUnsubKeyCommand(),
			new Command\EntityUpdateCommand(),
			new Command\EntityViewCommand(),

			new Command\TypeListCommand(),
		];
	}
}
