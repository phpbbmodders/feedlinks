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

/**
 * Feed Links extension base
 */
class ext extends \phpbb\extension\base
{
	/**
	 * Refuse to enable below the minimum phpBB and PHP versions, or without
	 * PHP's DOM extension, which builds the RSS feeds.
	 *
	 * @return bool|string True if enableable, otherwise the reason
	 */
	public function is_enableable()
	{
		if (!$this->check_phpbb_version() || !$this->check_php_version() || !extension_loaded('dom'))
		{
			$language = $this->container->get('language');
			$language->add_lang('install_feedlinks', 'phpbbmodders/feedlinks');

			return $language->lang('FEEDLINKS_NOT_ENABLEABLE');
		}

		return true;
	}

	/**
	 * Require phpBB 3.3.19
	 *
	 * @return bool
	 */
	public function check_phpbb_version()
	{
		return phpbb_version_compare(PHPBB_VERSION, '3.3.19', '>=');
	}

	/**
	 * Require PHP 7.4
	 *
	 * @return bool
	 */
	public function check_php_version()
	{
		return PHP_VERSION_ID >= 70400;
	}
}
