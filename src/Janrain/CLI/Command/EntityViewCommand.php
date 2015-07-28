<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityViewCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName( 'entity:view' )
			->setDescription( 'Retrieve a single entity' )
			->setDefinition( array(
				new InputOption( 'type', 't', InputOption::VALUE_REQUIRED, 'The entityType of the entity.' ),
				new InputOption( 'attributes', 'attrs', InputOption::VALUE_OPTIONAL, 'This is a JSON array of attributes. This works the same as attribute_name, only returning the specified attributes instead of the entire record.' ),
			) )
			->addArgument(
				'selector',
				InputArgument::REQUIRED,
				'Entity to select'
			)
			->setHelp(<<<EOT
This command allows you to retrieve a single entity (and any nested objects) with
record selector. To select a record use <info>key_attribute=key_value</info>. Please note that
<info>key_attribute</info> is any attribute in schema with a <info>unique constraint</info>.

Retrieve user data with the id <info>999</info>:

	<comment>%command.full_name% id=999</comment>

Retrieve user data with uuid <info>c0613105-f632-41ce-80eb-56668df7fc83</info>:

	<comment>%command.full_name% uuid=c0613105-f632-41ce-80eb-56668df7fc83</comment>

Limit the output with <info>--attributes=id,uuid,email,primaryAddress.phone</info>:

	<comment>%command.full_name% uuid=c0613105-f632-41ce-80eb-56668df7fc83 --attributes=id,uuid,email,primaryAddress.phone</comment>
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

		if ( ! empty( $input->getOption( 'attributes' ) ) ) {
			$params['attributes'] = explode( ',', $input->getOption( 'attributes' ) );
		}

		try {
			$selector = explode( '=', $input->getArgument( 'selector' ) );
			if ( sizeof( $selector ) !== 2 ) {
				throw new \Exception( 'selector must be in form of key_attribute=key_value.' );
			}

			$attr_name = $selector[0];
			$attr_value = $selector[1];

			switch ( $attr_name ) {
				case 'uuid':
					$result = $client->api( 'entity' )->view( $attr_value, $params );
					break;
				case 'id';
					$result = $client->api( 'entity' )->viewById( $attr_value, $params );
					break;
				default:
					$result = $client->api( 'entity' )->viewByAttribute( $attr_name, $attr_value, $params );
					break;
			}

			$entity = array();
			if ( 'ok' === strtolower( $result['stat'] ) && ! empty( $result['result'] ) ) {
				$entity = $this->flatten_array( $result['result'] );
			}

			$rows = array();
			foreach ( $entity as $key => $val ) {
				if ( is_array( $val ) ) {
					$val = json_encode( $val );
				}
				$rows[] = array( $key, $val );
			}

			$table = new Table( $output );
			$table
				->setHeaders( array( 'Key', 'Value' ) )
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
