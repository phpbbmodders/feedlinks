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

/**
 * phpBB's HTTP authentication for feeds ("Enable HTTP authentication" in
 * ACP > Feed settings), applied to the RSS routes.
 *
 * phpBB only applies it to routes named phpbb_feed_*. This subscriber runs
 * phpBB's own code for the RSS routes, so both formats log feed readers in
 * the same way.
 */
class http_auth_subscriber extends \phpbb\feed\event\http_auth_subscriber
{
	/** Prefix of this extension's RSS route names */
	const ROUTE_PREFIX = 'phpbbmodders_feedlinks_rss_';

	/**
	 * Handle HTTP authentication for the RSS routes.
	 *
	 * The request event class differs between phpBB 3.3 and 4.0, so the
	 * parameter has no type.
	 *
	 * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
	 * @return void
	 */
	public function on_kernel_request($event)
	{
		$request = $event->getRequest();
		$route = (string) $request->attributes->get('_route');

		if (strpos($route, self::ROUTE_PREFIX) !== 0)
		{
			return;
		}

		// phpBB's check only looks at the route name prefix; give it a
		// feed route name for the duration of the check
		$request->attributes->set('_route', 'phpbb_feed_' . substr($route, strlen(self::ROUTE_PREFIX)));

		try
		{
			parent::on_kernel_request($event);
		}
		finally
		{
			$request->attributes->set('_route', $route);
		}
	}
}
