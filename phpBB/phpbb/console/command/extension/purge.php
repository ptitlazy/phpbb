<?php
/**
*
* @package phpBB3
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace phpbb\console\command\extension;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class purge extends command
{
	protected function configure()
	{
		$this
			->setName('extension:purge')
			->setDescription('Purges the specified extension.')
			->addArgument(
				'extension-name',
				InputArgument::REQUIRED,
				'Name of the extension'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$name = $input->getArgument('extension-name');
		$this->manager->purge($name);
		$this->manager->load_extensions();

		if ($this->manager->enabled($name))
		{
			$output->writeln("<error>Could not purge extension $name</error>");
			return 1;
		}
		else
		{
			$this->log->add('admin', ANONYMOUS, '', 'LOG_EXT_PURGE', time(), array($name));
			$output->writeln("<info>Successfully purge extension $name</info>");
			return 0;
		}
	}
}
