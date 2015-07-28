<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityUpdateCommand extends AbstractCommand {
	protected function configure() {
		$this
			->setName( 'entity:update' )
			->setDescription( 'Update an entity' )
			->setDefinition( array(
				new InputOption( 'type', 't', InputOption::VALUE_REQUIRED, 'The type of the entity.' ),
			) )
			->addArgument(
				'selector',
				InputArgument::REQUIRED,
				'Entity to select'
			)
			->addArgument(
				'attributes',
				InputArgument::IS_ARRAY | InputArgument::REQUIRED,
				'Attributes, separated by space, to update.'
			)
			->setHelp(<<<EOT
This command allows you to update an entity by selecting entity to update first with
record selector. To select a record use <info>key_attribute=key_value</info>. Please note that
<info>key_attribute</info> is any attribute in schema with a <info>unique constraint</info>.

To update givenName and displayName of an entity with id <info>999</info>:

	<comment>%command.full_name% id=999 givenName=Akeda displayName="Akeda Bagus"</comment>
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
			$selector = explode( '=', $input->getArgument( 'selector' ) );
			if ( sizeof( $selector ) !== 2 ) {
				throw new \Exception( 'selector must be in form of key_attribute=key_value.' );
			}

			$attr_name = $selector[0];
			$attr_value = $selector[1];

			switch ( $attr_name ) {
				case 'uuid':
					$result = $client->api( 'entity' )->update( $attr_value, $params );
					break;
				case 'id';
					$result = $client->api( 'entity' )->updateById( $attr_value, $params );
					break;
				default:
					$result = $client->api( 'entity' )->updateByAttribute( $attr_name, $attr_value, $params );
					break;
			}

			if ( 'ok' !== strtolower( $result['stat'] ) ) {
				throw new \Exception( 'Failed to update' );
			} else {
				$output->writeln( '<info>Entity successfully updated</info>' );
			}
			exit(0);
		} catch ( \Exception $e ) {
			$output->writeln( '<error>' . $e->getMessage() . '</error>' );
			exit(1);
		}
	}
}
