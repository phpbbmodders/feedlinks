<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks\migrations\v10x;

/**
 * Adds the settings that choose whether the feed links show in forum lists
 * and on the forum page
 */
class placement extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritdoc}
	 */
	public function effectively_installed()
	{
		return isset($this->config['feedlinks_show_list']);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function depends_on()
	{
		return ['\phpbbmodders\feedlinks\migrations\v10x\install'];
	}

	/**
	 * Both places show the links, as before these settings existed
	 *
	 * {@inheritdoc}
	 */
	public function update_data()
	{
		return [
			['config.add', ['feedlinks_show_list', 1]],
			['config.add', ['feedlinks_show_forum', 1]],
		];
	}
}
