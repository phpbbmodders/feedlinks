<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks\tests;

use phpbb\config\config;
use phpbbmodders\feedlinks\links;

/**
 * Tests \phpbbmodders\feedlinks\links
 */
class links_test extends \phpbb_test_case
{
	/**
	 * @param array $config_overrides
	 * @param bool $can_read Whether the viewer has f_read
	 * @return links
	 */
	protected function get_links(array $config_overrides = [], $can_read = true)
	{
		$auth = $this->getMockBuilder('\phpbb\auth\auth')->disableOriginalConstructor()->getMock();
		$auth->method('acl_get')
			->with('f_read', $this->anything())
			->willReturn($can_read);

		$helper = $this->getMockBuilder('\phpbb\controller\helper')->disableOriginalConstructor()->getMock();
		$helper->method('route')
			->willReturnCallback(function ($route, array $params = []) {
				return $route . '/' . implode('/', $params);
			});

		$config = new config(array_merge([
			'feed_enable'	=> 1,
			'feed_forum'	=> 1,
		], $config_overrides));

		return new links($auth, $config, $helper);
	}

	/**
	 * A postable forum row
	 *
	 * @param array $overrides
	 * @return array
	 */
	protected function forum(array $overrides = [])
	{
		return array_merge([
			'forum_id'			=> 7,
			'forum_type'		=> FORUM_POST,
			'forum_options'		=> 0,
			'forum_password'	=> '',
			'forum_feedlinks'	=> links::BOTH,
		], $overrides);
	}

	public static function formats_data()
	{
		return [
			'none'					=> [[], ['forum_feedlinks' => 0], true, 0],
			'atom'					=> [[], ['forum_feedlinks' => links::ATOM], true, links::ATOM],
			'rss'					=> [[], ['forum_feedlinks' => links::RSS], true, links::RSS],
			'both'					=> [[], [], true, links::BOTH],
			'unknown bits ignored'	=> [[], ['forum_feedlinks' => 6], true, links::RSS],
			'missing column'		=> [[], ['forum_feedlinks' => null], true, 0],
			'feeds off'				=> [['feed_enable' => 0], [], true, 0],
			'forum feeds off'		=> [['feed_forum' => 0], [], true, 0],
			'category'				=> [[], ['forum_type' => FORUM_CAT], true, 0],
			'link forum'			=> [[], ['forum_type' => FORUM_LINK], true, 0],
			'excluded'				=> [[], ['forum_options' => 1 << FORUM_OPTION_FEED_EXCLUDE], true, 0],
			'news forum'			=> [[], ['forum_options' => 1 << FORUM_OPTION_FEED_NEWS], true, links::BOTH],
			'password'				=> [[], ['forum_password' => 'hash'], true, 0],
			'no read permission'	=> [[], [], false, 0],
		];
	}

	/**
	 * @dataProvider formats_data
	 */
	public function test_get_formats(array $config, array $forum, $can_read, $expected)
	{
		$forum = $this->forum($forum);
		if ($forum['forum_feedlinks'] === null)
		{
			unset($forum['forum_feedlinks']);
		}

		$this->assertSame($expected, $this->get_links($config, $can_read)->get_formats($forum));
	}

	public function test_get_urls()
	{
		$links = $this->get_links();

		$this->assertSame([
			'atom'	=> 'phpbb_feed_forum/7',
			'rss'	=> 'phpbbmodders_feedlinks_rss_forum/7',
		], $links->get_urls($this->forum()));

		$this->assertSame([
			'atom'	=> '',
			'rss'	=> 'phpbbmodders_feedlinks_rss_forum/7',
		], $links->get_urls($this->forum(['forum_feedlinks' => links::RSS])));

		$this->assertSame([
			'atom'	=> '',
			'rss'	=> '',
		], $links->get_urls($this->forum(['forum_feedlinks' => 0])));
	}

	public function test_get_topic_rss_url()
	{
		$this->assertSame('phpbbmodders_feedlinks_rss_topic/12', $this->get_links()->get_topic_rss_url('12'));
	}
}
