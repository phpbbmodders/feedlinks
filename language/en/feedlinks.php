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
	// %s, %1$s and %2$s are the feed links, for example "Atom"
	'FEEDLINKS_AVAILABLE'		=> 'Subscribe to the feed, available in %s format.',
	'FEEDLINKS_AVAILABLE_BOTH'	=> 'Subscribe to the feed, available in %1$s or %2$s format.',
	'FEEDLINKS_ATOM'		=> 'Atom',
	'FEEDLINKS_RSS'			=> 'RSS',
	'FEEDLINKS_ATOM_TITLE'	=> 'Atom feed - %s',
	'FEEDLINKS_RSS_TITLE'	=> 'RSS feed - %s',
]);
