<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks\event;

use phpbb\config\config;
use phpbb\event\data;
use phpbb\language\language;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbbmodders\feedlinks\links;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

/**
 * Feed Links event listener
 *
 * Adds the per-forum setting to ACP > Forums, the board-wide RSS discovery
 * setting to ACP > Feed settings, and the feed links to the board index,
 * forum pages and page head.
 */
class listener implements EventSubscriberInterface
{
	/** Template that renders a forum's feed links */
	const LINKS_TEMPLATE = '@phpbbmodders_feedlinks/feedlinks_links.html';

	/** @var config */
	protected $config;

	/** @var language */
	protected $language;

	/** @var request_interface */
	protected $request;

	/** @var template */
	protected $template;

	/** @var Environment */
	protected $twig;

	/** @var links */
	protected $links;

	/** @var bool Whether the front-end language file is loaded */
	protected $language_loaded = false;

	/**
	 * @param config $config
	 * @param language $language
	 * @param request_interface $request
	 * @param template $template
	 * @param Environment $twig
	 * @param links $links
	 */
	public function __construct(config $config, language $language, request_interface $request, template $template, Environment $twig, links $links)
	{
		$this->config = $config;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->twig = $twig;
		$this->links = $links;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.page_header'							=> 'add_rss_head_switch',
			'core.display_forums_modify_template_vars'	=> 'add_forumlist_links',
			'core.viewforum_modify_page_title'			=> 'add_viewforum_links',
			'core.viewtopic_modify_page_title'			=> 'add_viewtopic_head_link',
			'core.acp_manage_forums_request_data'		=> 'acp_forum_request',
			'core.acp_manage_forums_initialise_data'	=> 'acp_forum_initialise',
			'core.acp_manage_forums_display_form'		=> 'acp_forum_display',
			'core.acp_board_config_edit_add'			=> 'acp_board_feed_settings',
		];
	}

	/**
	 * Tell the page head whether to add RSS links for the board-wide feeds
	 *
	 * @return void
	 */
	public function add_rss_head_switch()
	{
		$this->template->assign_var('S_FEEDLINKS_RSS_HEAD', (bool) $this->config['feedlinks_rss_head']);
	}

	/**
	 * Add a forum's feed links below its description on forum lists
	 *
	 * phpBB has no template event after the description of a forum row (see
	 * docs/events/), so the rendered links are appended to FORUM_DESC. The
	 * URLs are also given to the row for styles that place them elsewhere.
	 *
	 * @param data $event
	 * @return void
	 */
	public function add_forumlist_links(data $event)
	{
		$urls = $this->links->get_urls($event['row']);

		if ($urls['atom'] === '' && $urls['rss'] === '')
		{
			return;
		}

		$forum_row = $event['forum_row'];
		$forum_row['U_FEEDLINKS_ATOM'] = $urls['atom'];
		$forum_row['U_FEEDLINKS_RSS'] = $urls['rss'];
		$forum_row['FORUM_DESC'] .= $this->render_links($urls, $event['row']['forum_name'], true);
		$event['forum_row'] = $forum_row;
	}

	/**
	 * Add a forum's feed links to its own page, and its RSS link to the head
	 *
	 * @param data $event
	 * @return void
	 */
	public function add_viewforum_links(data $event)
	{
		$urls = $this->links->get_urls($event['forum_data']);

		if ($urls['atom'] === '' && $urls['rss'] === '')
		{
			return;
		}

		$this->template->assign_vars([
			'U_FEEDLINKS_ATOM'		=> $urls['atom'],
			'U_FEEDLINKS_RSS'		=> $urls['rss'],
			'FEEDLINKS_FORUM_LINKS'	=> $this->render_links($urls, $event['forum_data']['forum_name'], false),
		]);
	}

	/**
	 * Add the RSS feeds of a topic and its forum to the page head when the
	 * forum shows RSS links, as phpBB does for the Atom feeds
	 *
	 * @param data $event
	 * @return void
	 */
	public function add_viewtopic_head_link(data $event)
	{
		$urls = $this->links->get_urls($event['topic_data']);

		if ($urls['rss'] === '')
		{
			return;
		}

		$this->template->assign_var('U_FEEDLINKS_RSS', $urls['rss']);

		if ($this->config['feed_topic'])
		{
			$this->template->assign_var('U_FEEDLINKS_RSS_TOPIC_HEAD', $this->links->get_topic_rss_url((int) $event['topic_data']['topic_id']));
		}
	}

	/**
	 * Read the forum setting when a forum is saved
	 *
	 * @param data $event
	 * @return void
	 */
	public function acp_forum_request(data $event)
	{
		$forum_data = $event['forum_data'];
		$forum_data['forum_feedlinks'] = $this->request->variable('forum_feedlinks', 0) & links::BOTH;
		$event['forum_data'] = $forum_data;
	}

	/**
	 * New forums start with no feed links
	 *
	 * @param data $event
	 * @return void
	 */
	public function acp_forum_initialise(data $event)
	{
		if ($event['action'] === 'add' && !$event['update'])
		{
			$forum_data = $event['forum_data'];
			$forum_data['forum_feedlinks'] = 0;
			$event['forum_data'] = $forum_data;
		}
	}

	/**
	 * Show the forum setting on the forum form
	 *
	 * @param data $event
	 * @return void
	 */
	public function acp_forum_display(data $event)
	{
		$this->language->add_lang('acp_feedlinks', 'phpbbmodders/feedlinks');

		$current = isset($event['forum_data']['forum_feedlinks']) ? (int) $event['forum_data']['forum_feedlinks'] : 0;
		$options = [
			0				=> 'FEEDLINKS_NONE',
			links::ATOM		=> 'FEEDLINKS_ATOM',
			links::RSS		=> 'FEEDLINKS_RSS',
			links::BOTH		=> 'FEEDLINKS_BOTH',
		];

		foreach ($options as $value => $lang_key)
		{
			$this->template->assign_block_vars('feedlinks_options', [
				'VALUE'		=> $value,
				'LABEL'		=> $this->language->lang($lang_key),
				'S_CHECKED'	=> $value === $current,
			]);
		}
	}

	/**
	 * Add the RSS discovery setting to ACP > Feed settings
	 *
	 * @param data $event
	 * @return void
	 */
	public function acp_board_feed_settings(data $event)
	{
		if ($event['mode'] !== 'feed')
		{
			return;
		}

		$this->language->add_lang('acp_feedlinks', 'phpbbmodders/feedlinks');

		$display_vars = $event['display_vars'];
		$display_vars['vars'] = phpbb_insert_config_array($display_vars['vars'], [
			'legend_feedlinks'		=> 'FEEDLINKS_LEGEND',
			'feedlinks_rss_head'	=> [
				'lang'		=> 'FEEDLINKS_RSS_HEAD',
				'validate'	=> 'bool',
				'type'		=> 'radio:yes_no',
				'explain'	=> true,
			],
		], ['after' => 'feed_exclude_id']);
		$event['display_vars'] = $display_vars;
	}

	/**
	 * Render a forum's feed links
	 *
	 * @param array $urls ['atom' => URL or '', 'rss' => URL or '']
	 * @param string $forum_name Forum name, already escaped
	 * @param bool $in_list Whether the links go in a forum list row
	 * @return string HTML
	 */
	protected function render_links(array $urls, $forum_name, $in_list)
	{
		if (!$this->language_loaded)
		{
			$this->language->add_lang('feedlinks', 'phpbbmodders/feedlinks');
			$this->language_loaded = true;
		}

		return $this->twig->render(self::LINKS_TEMPLATE, [
			'U_ATOM'		=> $urls['atom'],
			'U_RSS'			=> $urls['rss'],
			'FORUM_NAME'	=> $forum_name,
			'S_IN_LIST'		=> $in_list,
		]);
	}
}
