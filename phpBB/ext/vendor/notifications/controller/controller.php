<?php

namespace vendor\notifications\controller;

class controller
{
	protected $template;
	protected $helper;
	protected $path_helper;
	protected $config;
	protected $user;
	protected $manager;

	public function __construct(\phpbb\controller\helper $helper, \phpbb\path_helper $path_helper, \phpbb\template\template $template, \phpbb\config\config $config, \phpbb\user $user, \phpbb\notification\manager $manager, $root_path, $php_ext)
	{
		$this->template = $template;
		$this->helper = $helper;
		$this->path_helper = $path_helper;
		$this->config = $config;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->manager = $manager;
	}

	public function handle()
	{
		$this->manager->add_notifications('test', array(
			'topic_id' => 1,
			'post_id' => rand(),
			'post_time' => time(),
		));
		return $this->helper->render('message_body.html');
	}
}
