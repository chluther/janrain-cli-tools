<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityCountCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName( 'entity:count' )
			->setDescription( 'Retrieve number of records of entity type' )
			->setDefinition( array(
				new InputOption( 'type', 't', InputOption::VALUE_REQUIRED, 'The entityType of the entity.' ),
			) )
			->addArgument(
				'filter',
				InputArgument::OPTIONAL,
				'Filter operators'
			)
			->setHelp(<<<EOT
This command allows you to retrieve number of records of entity type.

Retrieve count of entity type specified in config:

	<comment>%command.full_name%</comment>

Retrieve count of entity type specified in argument <comment>-t</comment>:

	<comment>%command.full_name% -t user</comment>

Retrieve count of female users only:

	<comment>%command.full_name% -t user "gender != 'male'"</comment>
EOT
			)
		;
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$config = $this->get( 'config' );
		$client = $this->get( 'client' );
		$type   = $input->getOption( 'type' );
		$filter = $input->getArgument( 'filter' );

		if ( empty( $type ) ) {
			$type = $config->get( 'default_type' );
		}

		try {
			$total_count = $this->getEntityCount( $type, $filter );
			$output->write( $total_count );
			exit(0);
		} catch ( \Exception $e ) {
			$output->writeln( '<error>' . $e->getMessage() . '</error>' );
			exit(1);
		}
	}
}
