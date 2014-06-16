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
			->addArgument('batch', InputArgument::REQUIRED, $this->user->lang('CLI_SERACH_CREATE_INDEX_ARGUMENT'))
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		// Code taken from acp_search.php & create_search_index.php

		$sql = 'SELECT post_id
		FROM ' . POSTS_TABLE . '
		ORDER BY post_id DESC';
		$max_post_id = (int) $this->db->sql_fetchfield('post_id');

		$this->state = explode(',', $this->config['search_indexing_state']);

$search_name = ucfirst(strtolower(str_replace('_', ' ', $class_name)));
$search_errors = array();
$search = new $class_name($search_errors);

		$batch_size = $input->getArgument('batch') ? $input->getArgument('batch') : 2000;

		$search_name = ucfirst(strtolower(str_replace('_', ' ', $class_name)));
		$search_errors = array();
		$search = new $class_name($search_errors);

if (method_exists($search, 'create_index'))
{//Cas particulier
	if ($error = $search->create_index(null, ''))
	{
		var_dump($error);
		$output->writeln('<error>' . 'Fail : could not create index.' . '</error>');
	}

else
{
	$sql = 'SELECT forum_id, enable_indexing
		FROM ' . FORUMS_TABLE;
	$result = $this->db->sql_query($sql);

	while ($row = $this->db->sql_fetchrow($result))
	{
		$forums[$row['forum_id']] = (bool) $row['enable_indexing'];
	}
	$this->db->sql_freeresult($result);

	$post_counter = 0;

//#################################################

	while ($post_counter <= $max_post_id)
	{
		$row_count = 0;
		$time = time();

		printf("Processing posts with %d <= post_id <= %d\n",
			$post_counter + 1,
			$post_counter + $batch_size
		);

		$sql = 'SELECT post_id, post_subject, post_text, poster_id, forum_id
			FROM ' . POSTS_TABLE . '
			WHERE post_id >= ' . (int) ($post_counter + 1) . '
				AND post_id <= ' . (int) ($post_counter + $batch_size);
		$result = $this->db->sql_query($sql);

		$buffer = $this->db->sql_buffer_nested_transactions();

		if ($buffer)
		{
			$rows = $this->db->sql_fetchrowset($result);
			$rows[] = false; // indicate end of array for while loop below

			$this->db->sql_freeresult($result);
		}

		$i = 0;
		while ($row = ($buffer ? $rows[$i++] : $this->db->sql_fetchrow($result)))
		{
			// Indexing enabled for this forum or global announcement?
			// Global announcements get indexed by default.
			if (!$row['forum_id'] || !empty($forums[$row['forum_id']]))
			{
				++$row_count;
/*
				$search->index('post',
					$row['post_id'],
					$row['post_text'],
					$row['post_subject'],
					$row['poster_id'],
					$row['forum_id']
				);
*/
				if ($row_count % 10 == 0)
				{
					echo '.';
				}
			}
		}

		if (!$buffer)
		{
			$this->db->sql_freeresult($result);
		}

		$post_counter += $batch_size;
	}
}
	}
}
}

