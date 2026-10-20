<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks\tests\event;

use phpbb\config\config;
use phpbbmodders\feedlinks\event\http_auth_subscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests \phpbbmodders\feedlinks\event\http_auth_subscriber
 *
 * Runs phpBB's own HTTP authentication code; a guest with no credentials on
 * an HTTPS feed request gets phpBB's 401 challenge.
 */
class http_auth_subscriber_test extends \phpbb_test_case
{
	/**
	 * @return http_auth_subscriber
	 */
	protected function get_subscriber()
	{
		$auth = $this->getMockBuilder('\phpbb\auth\auth')->disableOriginalConstructor()->getMock();
		$auth->expects($this->never())->method('login');

		$language = $this->getMockBuilder('\phpbb\language\language')->disableOriginalConstructor()->getMock();
		$language->method('lang')->willReturnArgument(0);

		// No credentials sent
		$request = $this->getMockBuilder('\phpbb\request\request_interface')->getMock();
		$request->method('is_set')->willReturn(false);

		$user = $this->getMockBuilder('\phpbb\user')->disableOriginalConstructor()->getMock();
		$user->data = ['is_registered' => false];

		$config = new config(['feed_http_auth' => 1, 'sitename' => 'Test board']);

		return new http_auth_subscriber($auth, $config, $language, $request, $user);
	}

	/**
	 * A main request event for the route, on HTTPS
	 *
	 * phpBB 3.3 (Symfony 3.4) and 4.0 use different event classes.
	 *
	 * @param string $route
	 * @return \Symfony\Component\HttpKernel\Event\KernelEvent
	 */
	protected function get_event($route)
	{
		$request = Request::create('https://example.com/app.php/rss/forum/2');
		$request->attributes->set('_route', $route);

		$kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
		$class = class_exists('\Symfony\Component\HttpKernel\Event\RequestEvent')
			? '\Symfony\Component\HttpKernel\Event\RequestEvent'
			: '\Symfony\Component\HttpKernel\Event\GetResponseEvent';

		// MASTER_REQUEST was renamed MAIN_REQUEST in Symfony 5.3
		$type = defined(HttpKernelInterface::class . '::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST;

		return new $class($kernel, $request, $type);
	}

	public function test_challenges_rss_route()
	{
		$event = $this->get_event('phpbbmodders_feedlinks_rss_forum');
		$this->get_subscriber()->on_kernel_request($event);

		$this->assertTrue($event->hasResponse());
		$this->assertSame(401, $event->getResponse()->getStatusCode());
		$this->assertSame('Basic realm="Test board - Feed"', $event->getResponse()->headers->get('WWW-Authenticate'));

		// The route name is put back for the rest of the request
		$this->assertSame('phpbbmodders_feedlinks_rss_forum', $event->getRequest()->attributes->get('_route'));
	}

	public static function other_routes_data()
	{
		return [
			// phpBB's own subscriber handles its feed routes
			['phpbb_feed_forum'],
			['phpbbmodders_feedlinks_other'],
			['phpbb_index'],
		];
	}

	/**
	 * @dataProvider other_routes_data
	 */
	public function test_ignores_other_routes($route)
	{
		$event = $this->get_event($route);
		$this->get_subscriber()->on_kernel_request($event);

		$this->assertFalse($event->hasResponse());
		$this->assertSame($route, $event->getRequest()->attributes->get('_route'));
	}
}
