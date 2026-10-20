<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks\tests\event;

use phpbb\config\config;
use phpbb\event\data;
use phpbbmodders\feedlinks\event\listener;
use phpbbmodders\feedlinks\links;

/**
 * Tests \phpbbmodders\feedlinks\event\listener
 */
class listener_test extends \phpbb_test_case
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\request\request_interface */
	protected $request;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\template\template */
	protected $template;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\Twig\Environment */
	protected $twig;

	/** @var \PHPUnit\Framework\MockObject\MockObject|links */
	protected $links;

	/** @var config */
	protected $config;

	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;
		include_once $phpbb_root_path . 'includes/functions_acp.' . $phpEx;

		$this->request = $this->getMockBuilder('\phpbb\request\request_interface')->getMock();
		$this->template = $this->getMockBuilder('\phpbb\template\template')->getMock();
		$this->twig = $this->getMockBuilder('\Twig\Environment')->disableOriginalConstructor()->getMock();
		$this->links = $this->getMockBuilder(links::class)->disableOriginalConstructor()->getMock();
		$this->config = new config(['feed_topic' => 1, 'feedlinks_rss_head' => 1]);
	}

	/**
	 * Record the arguments of every call to a template method, in order
	 * (withConsecutive() is gone from PHPUnit 10)
	 *
	 * @param string $method
	 * @return \ArrayObject Filled with one argument list per call
	 */
	protected function record_calls($method)
	{
		$calls = new \ArrayObject();
		$this->template->method($method)
			->willReturnCallback(function (...$args) use ($calls) {
				$calls[] = $args;
			});

		return $calls;
	}

	/**
	 * @return listener
	 */
	protected function get_listener()
	{
		$language = $this->getMockBuilder('\phpbb\language\language')->disableOriginalConstructor()->getMock();
		$language->method('lang')->willReturnArgument(0);

		return new listener($this->config, $language, $this->request, $this->template, $this->twig, $this->links);
	}

	public function test_subscribed_events()
	{
		$this->assertSame([
			'core.page_header',
			'core.display_forums_modify_template_vars',
			'core.viewforum_modify_page_title',
			'core.viewtopic_modify_page_title',
			'core.acp_manage_forums_request_data',
			'core.acp_manage_forums_initialise_data',
			'core.acp_manage_forums_display_form',
			'core.acp_board_config_edit_add',
		], array_keys(listener::getSubscribedEvents()));
	}

	public function test_rss_head_switch()
	{
		$this->template->expects($this->once())
			->method('assign_var')
			->with('S_FEEDLINKS_RSS_HEAD', true);

		$this->get_listener()->add_rss_head_switch();
	}

	public function test_forumlist_links()
	{
		$this->links->method('get_urls')->willReturn(['atom' => 'atom-url', 'rss' => '']);
		$this->twig->expects($this->once())
			->method('render')
			->with(listener::LINKS_TEMPLATE, [
				'U_ATOM'		=> 'atom-url',
				'U_RSS'			=> '',
				'FORUM_NAME'	=> 'Forum',
				'S_IN_LIST'		=> true,
				'S_NEW_LINE'	=> true,
			])
			->willReturn('<links>');

		$event = new data([
			'row'		=> ['forum_name' => 'Forum'],
			'forum_row'	=> ['FORUM_DESC' => 'Description'],
		]);
		$this->get_listener()->add_forumlist_links($event);

		$this->assertSame(['FORUM_DESC' => 'Description<links>'], $event['forum_row']);
	}

	public function test_forumlist_without_links()
	{
		$this->links->method('get_urls')->willReturn(['atom' => '', 'rss' => '']);
		$this->twig->expects($this->never())->method('render');

		$event = new data([
			'row'		=> ['forum_name' => 'Forum'],
			'forum_row'	=> ['FORUM_DESC' => 'Description'],
		]);
		$this->get_listener()->add_forumlist_links($event);

		$this->assertSame(['FORUM_DESC' => 'Description'], $event['forum_row']);
	}

	public function test_viewtopic_head_links()
	{
		$this->links->method('get_urls')->willReturn(['atom' => 'atom-url', 'rss' => 'forum-rss']);
		$this->links->method('get_topic_rss_url')->with(12)->willReturn('topic-rss');

		$assigned = $this->record_calls('assign_var');

		$this->get_listener()->add_viewtopic_head_link(new data(['topic_data' => ['topic_id' => '12']]));

		$this->assertSame([
			['U_FEEDLINKS_RSS', 'forum-rss'],
			['U_FEEDLINKS_RSS_TOPIC_HEAD', 'topic-rss'],
		], $assigned->getArrayCopy());
	}

	public function test_viewtopic_without_rss()
	{
		$this->links->method('get_urls')->willReturn(['atom' => 'atom-url', 'rss' => '']);
		$this->template->expects($this->never())->method('assign_var');

		$this->get_listener()->add_viewtopic_head_link(new data(['topic_data' => ['topic_id' => 12]]));
	}

	public static function request_data()
	{
		return [
			[0, 0],
			[1, 1],
			[2, 2],
			[3, 3],
			[7, 3],
			[4, 0],
		];
	}

	/**
	 * @dataProvider request_data
	 */
	public function test_acp_forum_request($submitted, $stored)
	{
		$this->request->method('variable')->with('forum_feedlinks', 0)->willReturn($submitted);

		$event = new data(['action' => 'edit', 'forum_data' => ['forum_name' => 'Forum']]);
		$this->get_listener()->acp_forum_request($event);

		$this->assertSame(['forum_name' => 'Forum', 'forum_feedlinks' => $stored], $event['forum_data']);
	}

	public function test_acp_forum_initialise()
	{
		$listener = $this->get_listener();

		$event = new data(['action' => 'add', 'update' => false, 'forum_data' => []]);
		$listener->acp_forum_initialise($event);
		$this->assertSame(['forum_feedlinks' => 0], $event['forum_data']);

		$event = new data(['action' => 'edit', 'update' => false, 'forum_data' => ['forum_feedlinks' => 2]]);
		$listener->acp_forum_initialise($event);
		$this->assertSame(['forum_feedlinks' => 2], $event['forum_data']);
	}

	public function test_acp_forum_display()
	{
		$assigned = $this->record_calls('assign_block_vars');

		$this->get_listener()->acp_forum_display(new data(['forum_data' => ['forum_feedlinks' => '2']]));

		$this->assertSame([
			['feedlinks_options', ['VALUE' => 0, 'LABEL' => 'FEEDLINKS_NONE', 'S_CHECKED' => false]],
			['feedlinks_options', ['VALUE' => links::ATOM, 'LABEL' => 'FEEDLINKS_ATOM', 'S_CHECKED' => false]],
			['feedlinks_options', ['VALUE' => links::RSS, 'LABEL' => 'FEEDLINKS_RSS', 'S_CHECKED' => true]],
			['feedlinks_options', ['VALUE' => links::BOTH, 'LABEL' => 'FEEDLINKS_BOTH', 'S_CHECKED' => false]],
		], $assigned->getArrayCopy());
	}

	public function test_acp_board_feed_settings()
	{
		$vars = [
			'legend4'			=> 'ACP_FEED_OTHER',
			'feed_exclude_id'	=> ['lang' => 'ACP_FEED_EXCLUDE_ID'],
			'legend5'			=> 'ACP_SUBMIT_CHANGES',
		];

		$event = new data(['mode' => 'feed', 'display_vars' => ['title' => 'ACP_FEED_MANAGEMENT', 'vars' => $vars]]);
		$this->get_listener()->acp_board_feed_settings($event);

		$this->assertSame([
			'legend4',
			'feed_exclude_id',
			'legend_feedlinks',
			'feedlinks_rss_head',
			'legend5',
		], array_keys($event['display_vars']['vars']));

		$event = new data(['mode' => 'settings', 'display_vars' => ['vars' => $vars]]);
		$this->get_listener()->acp_board_feed_settings($event);

		$this->assertSame($vars, $event['display_vars']['vars']);
	}
}
