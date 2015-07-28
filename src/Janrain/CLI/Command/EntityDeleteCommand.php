<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityDeleteCommand extends AbstractCommand {
	protected function configure() {
		$this
			->setName( 'entity:delete' )
			->setDescription( 'Delete a single record' )
			->setDefinition( array(
				new InputOption( 'type', 't', InputOption::VALUE_REQUIRED, 'The type of the entity.'  ),
				new InputArgument( 'selector', InputArgument::REQUIRED, 'Record selector.' )
			) )
			->setHelp( <<<EOT
This command allows you to delete a single record.

Example:

	<comment>%command.full_name% id=999</comment>
	<comment>%command.full_name% uuid=c0613105-f632-41ce-80eb-56668df7fc83</comment>
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

		try {
			$selector = explode( '=', $input->getArgument( 'selector' ) );
			if ( sizeof( $selector ) !== 2 ) {
				throw new \Exception( 'selector must be in form of key_attribute=key_value.' );
			}

			$attr_name = $selector[0];
			$attr_value = $selector[1];

			switch ( $attr_name ) {
				case 'uuid':
					$result = $client->api( 'entity' )->delete( $attr_value, $params );
					break;
				case 'id';
					$result = $client->api( 'entity' )->deleteById( $attr_value, $params );
					break;
				default:
					$result = $client->api( 'entity' )->deleteByAttribute( $attr_name, $attr_value, $params );
					break;
			}

			if ( 'ok' !== strtolower( $result['stat'] ) ) {
				throw new \Exception( 'Failed to delete' );
			} else {
				$output->writeln( '<info>Entity successfully deleted</info>' );
			}
			exit(0);
		} catch ( \Exception $e ) {
			$output->writeln( '<error>' . $e->getMessage() . '</error>' );
			exit(1);
		}
	}
}
