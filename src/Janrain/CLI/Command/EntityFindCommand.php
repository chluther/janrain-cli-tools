<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityFindCommand extends AbstractCommand {

	protected function configure()
	{
		$this
			->setName( 'entity:find' )
			->setDescription( 'Find entity' )
			->setDefinition( array(
				new InputOption( 'type', 't', InputOption::VALUE_REQUIRED, 'The type of the entity.' ),
				new InputOption( 'attributes', 'attrs', InputOption::VALUE_OPTIONAL, 'Comma separated attributes. To limit output attributes.' ),
				new InputOption( 'max', 'm', InputOption::VALUE_OPTIONAL, 'The maximum number of results to be returned. The default value is 100. The highest value you can enter is 10000.' ),
				new InputOption( 'first', 'f', InputOption::VALUE_OPTIONAL, 'Changes the first result displayed by the list to the next number specified. For example: changing this value to 3 will display the 4th user record. The default value is 1.' ),
				new InputOption( 'sort_on', 's', InputOption::VALUE_OPTIONAL, 'Comma separated of attributes to sort by. The default is ascending order, which can be reversed by including a minus sign (-) directly before the attribute name.' ),
			) )
			->addArgument(
				'filter',
				InputArgument::REQUIRED,
				'Filter operators'
			)
			->setHelp(<<<EOT
This command allows you to find entities with filter operators.

The following operators are supported filter, from highest to lowest precedence:

* is null, is not null (postfix)
* not, ! (prefix)
* >, >=, <, <= (infix)
* =, != (infix)
* and (infix)
* or (infix)

Please note that if string values specified by operators then the values must be
surrounded by single quotes, not double quotes. See some examples below.

To retrieve entities with a birthday:

	<comment>%command.full_name% 'birthday is not null'</comment>

To find female users only:

	<comment>%command.full_name% "gender != 'male'"</comment>

Adding more than one condition to a filter:

	<comment>%command.full_name% "gender='male' and birthday > '2012-06-13 18:02:56.012122 +0000'</comment>

	<comment>%command.full_name% "age > 25 and age < 50 and gender='female'"</comment>

Limit results:

	<comment>%command.full_name% 'birthday is not null' -m 500</comment>

Specify output attributes with <info>--attributes=id,uuid,email,primaryAddress.phone</info>:

	<comment>%command.full_name% 'birthday is not null' -m 500 --attributes=id,uuid,email,primaryAddress.phone</comment>

Sort result by lastUpdated descendingly:

	<comment>%command.full_name% 'birthday is not null' -m 500 -s='-lastUpdated'</comment>
EOT
			)
		;
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$config = $this->get( 'config' );
		$type   = $input->getOption( 'type' );

		if ( empty( $type ) ) {
			$type = $config->get( 'default_type' );
		}

		$params = array(
			'type_name' => $type,
			'filter'    => $input->getArgument( 'filter' ),
		);

		if ( ! empty( $input->getOption( 'attributes' ) ) ) {
			$params['attributes'] = explode( ',', $input->getOption( 'attributes' ) );
		} else {
			$params['attributes'] = array( 'id', 'uuid', 'email' );
		}

		if ( ! empty( $input->getOption( 'max' ) ) ) {
			$params['max_results'] = intval( $input->getOption( 'max' ) );
		}

		if ( ! empty( $input->getOption( 'first' ) ) ) {
			$params['first_result'] = intval( $input->getOption( 'first' ) );
		} else {
			$params['first_result'] = 0;
		}

		if ( ! empty( $input->getOption( 'sort_on' ) ) ) {
			$params['sort_on'] = explode( ',', $input->getOption( 'sort_on' ) );
		} else {
			$params['sort_on'] = array( 'id' );
		}

		try {
			$client = $this->get( 'client' );
			$result = $client->api( 'entity' )->find( $params );

			$entities = array();
			if ( 'ok' === strtolower( $result['stat'] ) && ! empty( $result['results'] ) ) {
				$entities = $result['results'];
			}

			$rows = array();
			foreach ( $entities as $entity ) {
				$entity = $this->flatten_array( $entity );
				$row = array();
				foreach ( $params['attributes'] as $field ) {
					$row[ $field ] = $entity[ $field ];
				}
				$rows[] = $row;
			}

			$table = new Table( $output );
			$table
				->setHeaders( $params['attributes'] )
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
