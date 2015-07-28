<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TypeListCommand extends AbstractCommand {
	protected function configure() {
		$this
			->setName( 'type:list' )
			->setDescription( 'Retrieve all entity types' )
			->setHelp(<<<EOT
This command allows you to retrieve all entity types.

	<comment>%command.full_name% list</comment>

EOT
			)
		;
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$client = $this->get( 'client' );

		try {
			$result = $client->api( 'entityType' )->getList();

			$types = array();
			if ( 'ok' === strtolower( $result['stat'] ) && ! empty( $result['results'] ) ) {
				$types = $result['results'];
			}

			$rows = array();
			foreach ( $types as $key => $val ) {
				$rows[] = array( $key + 1, $val );
			}

			$table = new Table( $output );
			$table
				->setHeaders( array( 'No', 'Type' ) )
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
