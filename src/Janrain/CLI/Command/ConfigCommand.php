<?php

namespace Janrain\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName( 'config' )
			->setDescription( 'Janrain config' )
			->setDefinition( array(
				new InputOption( 'list', 'l' ),
				new InputArgument( 'config-key', null, 'Config key' ),
				new InputArgument( 'config-value', null, 'Config valuey' ),
			))
			->setHelp(<<<EOT
Set config that will be saved in <info>~/.jcli.json</info>.

To set <info>client_id</info>, <info>client_secret</info>, and <info>base_url</info>:

	<comment>%command.full_name% client_id YOUR_CLIENT_ID</comment>
	<comment>%command.full_name% client_secret YOUR_CLIENT_SECRET</comment>
	<comment>%command.full_name% base_url YOUR_BASE_URL</comment>

If second argument is omitted, then value for that config key is returned:

	<comment>%command.full_name% client_id</comment>

In addition to <info>client_id</info>, <info>client_secret</info>, and <info>base_url</info>, you can also set <info>default_type</info> for default entity type.
EOT
			)
		;
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$config = $this->get( 'config' );

		if ( $input->getOption( 'list' ) ) {
			$rows = array();
			foreach ( $config->getAll() as $key => $val ) {
				$rows[] = array( $key, $val );
			}

			$table = new Table( $output );
			$table
				->setHeaders( array( 'Key', 'Value' ) )
				->setRows( $rows )
			;
			$table->render();
			exit( 0 );
		}

		$configKey = $input->getArgument( 'config-key' );
		$configVal = $input->getArgument( 'config-value' );

		try {
			if ( ! empty( $configVal ) ) {
				$config->set( $configKey, $configVal );
			} else {
				$val = $config->get( $configKey );
				if ( ! empty( $val ) ) {
					$output->write( $val );
				}
			}
			exit(0);
		} catch ( \Exception $e ) {
			$output->write( '<error>' . $e->getMessage() . '</error>' );
			exit(1);
		}
	}
}
