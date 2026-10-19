<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'FEEDLINKS_FORUM'			=> 'Show feed links',
	'FEEDLINKS_FORUM_EXPLAIN'	=> 'Shows links to this forum’s feed below its description on the board index and on the forum’s own page. Links only show while forum feeds are enabled in Feed settings, the forum is not excluded from feeds, it has no password, and the viewer can read it.',
	'FEEDLINKS_NONE'			=> 'None',
	'FEEDLINKS_ATOM'			=> 'Atom',
	'FEEDLINKS_RSS'				=> 'RSS',
	'FEEDLINKS_BOTH'			=> 'Atom and RSS',

	'FEEDLINKS_LEGEND'				=> 'Feed Links',
	'FEEDLINKS_RSS_HEAD'			=> 'Add RSS discovery links',
	'FEEDLINKS_RSS_HEAD_EXPLAIN'	=> 'Adds RSS links for the board-wide feeds enabled on this page to every page’s head, so browsers and feed readers can find them. phpBB always adds the Atom links. The RSS feeds work whether or not this is on.',
]);
