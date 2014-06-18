<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/
namespace phpbb\console\command\search;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class create_index extends \phpbb\console\command\command
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\auth\auth */
	protected $auth;//

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\config\config */
	protected $config;

	protected $max_post_id;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\user $user,  \phpbb\config\config $config)
	{
		$this->db = $db;
		$this->auth = $auth;
		$this->user = $user;
		$this->config = $config;
		parent::__construct();
	}

	protected function configure()
	{
		$this
			->setName('search:create-index')
			->setDescription($this->user->lang('CLI_DESCRIPTION_SEARCH_CREATE_INDEX'))
			->addOption('index_type', null, InputOption::VALUE_REQUIRED, $this->user->lang('CLI_DESCRIPTION_SEARCH_CREATE_INDEX_OPTION_INDEX_TYPE'))
			->addOption('batch_size', null, InputOption::VALUE_REQUIRED, $this->user->lang('CLI_DESCRIPTION_SEARCH_CREATE_INDEX_OPTION_BATCH_SIZE'))
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$search = null;
		$error = false;
		$post_counter = 0;

		$progress = $this->getHelperSet()->get('progress');

		if (!$index_type = $input->getOption('index_type'))
		{
			$index_type = $this->config['search_type'];
		}
		if (!$batch_size = $input->getOption('batch_size'))
		{
			$batch_size = 2000;
		}

		if ($this->init_search($index_type, $search, $error))
		{
			$output->writeln('<error>' . $error . '</error>');
			return 1;
		}
		$name = $search->get_name();

		$max_post_id = $this->get_max_post_id();
		$progress->start($output, $max_post_id);
		while ($post_counter < $max_post_id)
		{
			if (method_exists($search, 'create_index'))
			{
				if ($error = $search->create_index($this, null))
				{
					$output->writeln('<error>' . $error . '</error>');
					return 1;
				}
			}
			else
			{
				$sql = 'SELECT forum_id, enable_indexing
					FROM ' . FORUMS_TABLE;
				$result = $this->db->sql_query($sql, 3600);

				while ($row = $this->db->sql_fetchrow($result))
				{
					$forums[$row['forum_id']] = (bool) $row['enable_indexing'];
				}
				$this->db->sql_freeresult($result);

				$starttime = explode(' ', microtime());
				$starttime = $starttime[1] + $starttime[0];
				$row_count = 0;
				while (still_on_time() && $post_counter <= $max_post_id)
				{
					$sql = 'SELECT post_id, post_subject, post_text, poster_id, forum_id
						FROM ' . POSTS_TABLE . '
						WHERE post_id >= ' . (int) ($post_counter + 1) . '
						AND post_id <= ' . (int) ($post_counter + $batch_size);
					$result = $this->db->sql_query($sql);

					$buffer = $this->db->sql_buffer_nested_transactions();

					//
					if ($buffer)
					{
						$rows = $this->db->sql_fetchrowset($result);
						$rows[] = false; // indicate end of array for while loop below

						$this->db->sql_freeresult($result);
					}

					$i = 0;
					while ($row = ($buffer ? $rows[$i++] : $this->db->sql_fetchrow($result)))
					{
						//
						// Indexing enabled for this forum
						if (isset($forums[$row['forum_id']]) && $forums[$row['forum_id']])
						{
							$search->index('post', $row['post_id'], $row['post_text'], $row['post_subject'], $row['poster_id'], $row['forum_id']);
						}
						$row_count++;
						$progress->advance();
					}
					if (!$buffer)
					{
						$this->db->sql_freeresult($result);
					}

					$post_counter += $batch_size;
				}

				// pretend the number of posts was as big as the number of ids we indexed so far
				// just an estimation as it includes deleted posts
				$num_posts = $config['num_posts'];
				$config['num_posts'] = min($config['num_posts'], $post_counter);
				$search->tidy();
				$config['num_posts'] = $num_posts;
			}
		}

		$progress->finish();
		$search->tidy();

		add_log('admin', 'LOG_SEARCH_INDEX_CREATED', $name);

		$output->writeln('<info>' . $this->user->lang('SEARCH_INDEX_CREATED') . '</info>');
	}

	protected function get_max_post_id()
	{
		$sql = 'SELECT MAX(post_id) as max_post_id
			FROM '. POSTS_TABLE;
		$result = $this->db->sql_query($sql);
		$max_post_id = (int) $this->db->sql_fetchfield('max_post_id');
		$this->db->sql_freeresult($result);

		return $max_post_id;
	}

	/**
	 * Initialises a search backend object
	 *
	 * @return false if no error occurred else an error message
	 */
	function init_search($type, &$search)
	{
		global $phpbb_root_path, $phpEx;

		if (!class_exists($type) || !method_exists($type, 'keyword_search'))
		{
			$error = $this->user->lang('NO_SUCH_SEARCH_MODULE');
			return $error;
		}

		$search = new $type($error, $phpbb_root_path, $phpEx, $this->auth, $this->config, $this->db, $this->user);

		return false;
	}

}
