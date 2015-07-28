<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityCreateCommand extends AbstractCommand {
	protected function configure() {
		$this
			->setName( 'entity:create' )
			->setDescription( 'Create new data record' )
			->setDefinition( array(
				new InputOption( 'type', 't', InputOption::VALUE_REQUIRED, 'The type of the entity.'  ),
				new InputArgument( 'attributes', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Attributes, separated by space, to update.' ),
			) )
			->setHelp( <<<EOT
This command allows you to create a new data record in specified entity type. Once
created the new uuid is returned.

Example:

	<comment>%command.full_name% firstName=Bob lastName=Smith email=bob@example.com</comment>
EOT
			)
		;
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$config = $this->get( 'config' );
		$client = $this->get( 'client' );
		$type   = $input->getOption( 'type' );

		if ( empty( $type ) ) {
			$type = $config->get( 'default_type' );
		}

		$params = array(
			'type_name' => $type,
		);

		$attributes = array();
		foreach ( $input->getArgument( 'attributes' ) as $attr ) {
			$attr = explode( '=', $attr );
			$attributes[ $attr[0] ] = $attr[1];
		}

		$params['attributes'] = $attributes;

		try {
			$result = $client->api( 'entity' )->create( $params );

			if ( 'ok' === strtolower( $result['stat'] ) && ! empty( $result['uuid'] ) ) {
				$output->writeln( "<info>Record with uuid {$result['uuid']} successfully created</info>" );
			} else {
				throw new \Exception( 'Failed to create' );
			}

			exit(0);
		} catch ( \Exception $e ) {
			$output->writeln( '<error>' . $e->getMessage() . '</error>' );
			exit(1);
		}
	}
}
