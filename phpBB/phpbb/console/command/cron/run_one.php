<?php
/**
*
* @package phpBB3
* @copyright (c) 2014 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace phpbb\console\command\cron;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class run_one extends \phpbb\console\command\command
{
	/** @var \phpbb\cron\manager */
	protected $cron_manager;

	/** @var \phpbb\lock\db */
	protected $lock_db;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Construct method
	*
	* @param \phpbb\cron\manager $cron_manager The cron manager containing
	*							the cron tasks to be executed.
	* @param \phpbb\lock\db $lock_db The lock for accessing database.
	* @param \phobb\user $user The user object (used to get language information)
	*/
	public function __construct(\phpbb\cron\manager $cron_manager, \phpbb\lock\db $lock_db, \phpbb\user $user)
	{
		$this->cron_manager = $cron_manager;
		$this->lock_db = $lock_db;
		$this->user = $user;
		parent::__construct();
	}

	/**
	* Sets the command name and description
	*
	* @return null
	*/
	protected function configure()
	{
		$this
			->setName('cron:run')
			->setDescription($this->user->lang('CLI_DESCR_CRON_RUN'))
			->addArgument('name', InputArgument::REQUIRED, $this->user->lang('CLI_ARG_CRON_RUN_1'))
			;
	}

	/**
	* Executes the function.
	* There will be no output in case of successful execution.
	*
	* @param InputInterface input The input stream, unused here
	* @param OutputInterface output The output stream, used for printig verbose-mode
	*							and error information.
	* @return null
	*/
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$task = $this->cron_manager->find_task($input->getArgument('name'));

		if ($this->lock_db->acquire())
		{
			if ($task)
			{
				$task->run();
			}
			else
			{
				$output->writeln('<error>' . $this->user->lang('CRON_NO_TASK') . '</error>');
			}

			$this->lock_db->release();
		}
		else
		{
			$output->writeln('<error>' . $this->user->lang('CRON_LOCK_ERROR') . '</error>');
		}
	}

}
