<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks\controller;

use phpbb\feed\feed_interface;
use phpbbmodders\feedlinks\atom_to_rss;
use Symfony\Component\HttpFoundation\Response;

/**
 * phpBB's feed controller, sending RSS 2.0 instead of Atom.
 *
 * Every route action (forums, news, topics, forum, topic, overall...) is
 * phpBB's own, so feed settings, permissions, events and the item limits
 * work the same as for the Atom feeds. Only the finished document is
 * converted.
 */
class rss extends \phpbb\feed\controller\feed
{
	/** @var atom_to_rss */
	protected $converter;

	/**
	 * Set the converter that turns phpBB's Atom document into RSS
	 *
	 * @param atom_to_rss $converter
	 * @return void
	 */
	public function set_converter(atom_to_rss $converter)
	{
		$this->converter = $converter;
	}

	/**
	 * Build the feed as phpBB does, then send it as RSS 2.0.
	 *
	 * Headers set by phpBB (Last-Modified, the bot marker) are kept.
	 *
	 * @param feed_interface $feed
	 * @return Response
	 */
	protected function send_feed_do(feed_interface $feed)
	{
		$response = parent::send_feed_do($feed);

		$response->setContent($this->converter->convert($response->getContent()));
		$response->headers->set('Content-Type', 'application/rss+xml; charset=UTF-8');

		return $response;
	}
}
