<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClientListCommand extends AbstractCommand {
	protected function configure() {
		$this
			->setName( 'client:list' )
			->setDescription( 'Retrieve list of the clients in your application' )
			->setDefinition( array(
				new InputOption( 'features', 'f', InputOption::VALUE_REQUIRED, 'Comma separated features.' )
			) )
			->setHelp( <<<EOT
This command allows you retrieve list of the clients in your application. Please
note that only <info>owner</info> client can make this call.

To retrieve list of clients:

	<comment>%command.full_name%</comment>

To retrieve list of clients that have <info>direct_access</info> and <info>access_issuer</info> features:

	<comment>%command.full_name% </comment>

Valid features are owner, access_issuer, direct_read_access, direct_access, and image_create.
EOT
			)
		;
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$client = $this->get( 'client' );

		$features = array();
		if ( ! empty( $input->getOption( 'features' ) ) ) {
			$features = explode( ',', $input->getOption( 'features' ) );
		}

		try {
			$result  = $client->api( 'clients' )->getList( $features );
			$clients = array();
			if ( 'ok' === strtolower( $result['stat'] ) && ! empty( $result['results'] ) ) {
				$clients = $result['results'];
			}

			$rows = array();
			foreach ( $clients as $client ) {
				$rows[] = array(
					$client['client_id'],
					$client['client_secret'],
					$client['description'],
				);
			}

			$table = new Table( $output );
			$table
				->setHeaders( array( 'client_id', 'client_secret', 'description' ) )
				->setRows( $rows )
			;
			$table->render();
			exit(0);
		} catch ( \Exception $e ) {
			$output->writeln( '<error>' . $e->getMessage() . '</error>' );
			exit(1);
		}
	}
}
