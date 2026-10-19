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
	'FEEDLINKS_SUBSCRIBE'	=> 'Subscribe to the feed:',
	'FEEDLINKS_OR'			=> 'or',
	'FEEDLINKS_ATOM'		=> 'Atom',
	'FEEDLINKS_RSS'			=> 'RSS',
	'FEEDLINKS_ATOM_TITLE'	=> 'Atom feed - %s',
	'FEEDLINKS_RSS_TITLE'	=> 'RSS feed - %s',
]);
