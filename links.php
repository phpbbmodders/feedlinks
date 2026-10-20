<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;

/**
 * Decides which feed links a forum shows, and builds their URLs.
 */
class links
{
	/** Forum setting bit: show the Atom link */
	const ATOM = 1;

	/** Forum setting bit: show the RSS link */
	const RSS = 2;

	/** Forum setting: show both links */
	const BOTH = 3;

	/** @var auth */
	protected $auth;

	/** @var config */
	protected $config;

	/** @var helper */
	protected $helper;

	/**
	 * @param auth $auth
	 * @param config $config
	 * @param helper $helper
	 */
	public function __construct(auth $auth, config $config, helper $helper)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
	}

	/**
	 * The formats a forum shows links for, after checking that its feed can
	 * actually be read.
	 *
	 * The feed must work for the viewer: forum feeds enabled board-wide, the
	 * forum postable, not excluded from feeds, not password protected, and
	 * readable by the viewer. Otherwise the link would only lead to an error.
	 *
	 * @param array $forum Forum row with forum_id, forum_type, forum_options,
	 *                     forum_password and forum_feedlinks
	 * @return int Bits of self::ATOM and self::RSS; 0 for no links
	 */
	public function get_formats(array $forum)
	{
		$formats = isset($forum['forum_feedlinks']) ? ((int) $forum['forum_feedlinks'] & self::BOTH) : 0;

		if (!$formats
			|| !$this->config['feed_enable']
			|| !$this->config['feed_forum']
			|| (int) $forum['forum_type'] !== FORUM_POST
			|| phpbb_optionget(FORUM_OPTION_FEED_EXCLUDE, (int) $forum['forum_options'])
			|| !empty($forum['forum_password'])
			|| !$this->auth->acl_get('f_read', (int) $forum['forum_id']))
		{
			return 0;
		}

		return $formats;
	}

	/**
	 * Feed URLs for a forum
	 *
	 * @param array $forum Forum row, see get_formats()
	 * @return array ['atom' => URL or '', 'rss' => URL or '']
	 */
	public function get_urls(array $forum)
	{
		$formats = $this->get_formats($forum);
		$params = ['forum_id' => (int) $forum['forum_id']];

		return [
			'atom'	=> ($formats & self::ATOM) ? $this->helper->route('phpbb_feed_forum', $params) : '',
			'rss'	=> ($formats & self::RSS) ? $this->helper->route('phpbbmodders_feedlinks_rss_forum', $params) : '',
		];
	}

	/**
	 * RSS feed URL of a topic
	 *
	 * @param int $topic_id
	 * @return string
	 */
	public function get_topic_rss_url($topic_id)
	{
		return $this->helper->route('phpbbmodders_feedlinks_rss_topic', ['topic_id' => (int) $topic_id]);
	}
}
