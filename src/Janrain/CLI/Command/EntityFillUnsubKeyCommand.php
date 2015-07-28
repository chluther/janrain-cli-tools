<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class EntityFillUnsubKeyCommand extends AbstractCommand {

	const UNSUBSCRIBE_ATTRIBUTE = 'ETUID';

	protected function configure() {
		$this
			->setName( 'entity:fill-unsub-key' )
			->setDescription( 'Fill empty unsubscribe key on records' )
			->setDefinition( array(
				new InputOption( 'type', 't', InputOption::VALUE_REQUIRED, 'The entityType of the entity.' ),
			) )
			->addArgument(
				'filter',
				InputArgument::OPTIONAL,
				'Additional filter operators'
			)
			->setHelp(<<<EOT
This command allows you to update records with empty / null unsubscribe attribute.
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

		$filter = sprintf( '%s is null', self::UNSUBSCRIBE_ATTRIBUTE );

		if ( ! empty( $input->getArgument( 'filter' ) ) ) {
			$filter .= ' AND (' . $input->getArgument( 'filter' ) . ')';
		}

		// Total count records with unsubscribe attribute value empty / null.
		$total_count = $this->getEntityCount( $type, $filter );

		$output->writeln( sprintf( '<info>Found %d records where "%s"</info>', $total_count, $filter ) );

		// Confirm update.
		$question = new ConfirmationQuestion(
			sprintf( 'Update these %d records? [y/n] ', $total_count ),
			false
		);

		$helper = $this->getHelper( 'question' );
		if ( ! $helper->ask( $input, $output, $question ) ) {
			exit(0);
		}

		$progress = new ProgressBar( $output, $total_count );
		$progress->setFormat( " %current%/%max% [%bar%] %percent:3s%% \n%message%" );

		$updateParams = array(
			'type_name'  => $type,
			'attributes' => array(
				self::UNSUBSCRIBE_ATTRIBUTE => '', // Fill with `sha1( $user['uuid'] . $user['created'] )`
			)
		);

		$succeed = 0;
		$failed  = 0;
		$offset  = 0;

		$records = $this->retrieveRecords( $this->getQueryParams( $offset, $type, $filter ) );
		if ( empty( $records ) ) {
			$output->write( 'No records to update.' );
			exit(0);
		}

		$progress->setMessage( 'Retrieving records...' );
		$progress->start();

		while ( ! empty( $records ) ) {
			foreach ( $records as $record ) {
				$updateParams['attributes'][ self::UNSUBSCRIBE_ATTRIBUTE ] = sha1( $record['uuid'] . $record['created'] );

				$progress->setMessage( sprintf( 'Update record with id %s', $record['id'] ) );

				$result = $client->api( 'entity' )->updateById( $record['id'], $updateParams );
				if ( 'ok' === strtolower( $result['stat'] ) ) {
					$succeed++;
				} else {
					$failed++;
				}
				$progress->advance();
			}

			$offset += sizeof( $records );

			$progress->setMessage( 'Retrieving records...' );
			$records = $this->retrieveRecords( $this->getQueryParams( $offset, $type, $filter ) );
		}
		if ( $failed > 0 ) {
			$progress->setMessage( sprintf( '<info>%d</info> records are successfully updated, but<error>%d</error> records are failed to update. Please try run this command again.', $succeed, $failed ) );
		} else {
			$progress->setMessage( sprintf( '<info>%d</info> records are successfully updated.', $succeed ) );
		}
		$progress->finish();

		exit( $failed > 0 ? 1 : 0 );
	}

	private function retrieveRecords( array $params ) {
		$client = $this->get( 'client' );

		$result = $client->api( 'entity' )->find( $params );

		$records = array();
		if ( 'ok' === strtolower( $result['stat'] ) && ! empty( $result['results'] ) ) {
			$records = $result['results'];
		}

		return $records;
	}

	private function getQueryParams( $offset, $type, $filter ) {
		return array(
			'max_results'  => 10000,
			'first_result' => $offset,
			'type_name'    => $type,
			'attributes'   => array( 'id', 'uuid', 'created' ),
			'sort_on'      => array( 'id' ),
			'filter'       => $filter,
		);
	}
}
