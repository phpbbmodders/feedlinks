<?php
/**
 *
 * Feed Links extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\feedlinks\tests;

use phpbbmodders\feedlinks\atom_to_rss;

/**
 * Tests \phpbbmodders\feedlinks\atom_to_rss against Atom documents shaped
 * like the ones phpBB's feed.xml.twig builds
 */
class atom_to_rss_test extends \phpbb_test_case
{
	/**
	 * An Atom feed as phpBB builds it, with one entry
	 *
	 * @param string $subtitle Feed subtitle, empty for none
	 * @param string $content Entry HTML
	 * @return string
	 */
	protected function atom_feed($subtitle = 'A board &amp; more', $content = '<p>Hello <b>world</b></p>')
	{
		return '<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="en-gb">
	<link rel="self" type="application/atom+xml" href="http://example.com/app.php/rss/forum/2" />
	<title>Test &amp; Board</title>
	' . ($subtitle !== '' ? '<subtitle>' . $subtitle . '</subtitle>' : '') . '
	<link href="http://example.com/index.php" />
	<updated>2026-10-19T12:30:00+02:00</updated>
	<author><name><![CDATA[Test & Board]]></name></author>
	<id>http://example.com/app.php/rss/forum/2</id>
	<entry>
		<author><name><![CDATA[alice]]></name></author>
		<updated>2026-10-19T12:00:00+02:00</updated>
		<published>2026-10-18T09:15:00+02:00</published>
		<id>http://example.com/viewtopic.php?p=5#p5</id>
		<link href="http://example.com/viewtopic.php?p=5#p5"/>
		<title type="html"><![CDATA[Tom &amp; Jerry &lt;3]]></title>
		<category term="General" scheme="http://example.com/viewforum.php?f=2" label="General"/>
		<content type="html" xml:base="http://example.com/viewtopic.php?p=5#p5"><![CDATA[
' . $content . '<hr />
]]></content>
	</entry>
</feed>';
	}

	/**
	 * Parse the converted feed
	 *
	 * @param string $atom
	 * @return \SimpleXMLElement
	 */
	protected function convert($atom)
	{
		$converter = new atom_to_rss();
		$rss = simplexml_load_string($converter->convert($atom));
		$this->assertNotFalse($rss);

		return $rss;
	}

	public function test_channel()
	{
		$rss = $this->convert($this->atom_feed());

		$this->assertSame('rss', $rss->getName());
		$this->assertSame('2.0', (string) $rss['version']);
		$this->assertSame('Test & Board', (string) $rss->channel->title);
		$this->assertSame('http://example.com/index.php', (string) $rss->channel->link);
		$this->assertSame('A board & more', (string) $rss->channel->description);
		$this->assertSame('en-gb', (string) $rss->channel->language);
		$this->assertSame('Mon, 19 Oct 2026 12:30:00 +0200', (string) $rss->channel->lastBuildDate);

		$self = $rss->channel->children(atom_to_rss::ATOM_NS)->link;
		$this->assertSame('http://example.com/app.php/rss/forum/2', (string) $self->attributes()->href);
		$this->assertSame('self', (string) $self->attributes()->rel);
		$this->assertSame('application/rss+xml', (string) $self->attributes()->type);
	}

	public function test_description_falls_back_to_title()
	{
		$rss = $this->convert($this->atom_feed(''));

		$this->assertSame('Test & Board', (string) $rss->channel->description);
	}

	public function test_item()
	{
		$rss = $this->convert($this->atom_feed());
		$item = $rss->channel->item[0];

		$this->assertCount(1, $rss->channel->item);
		$this->assertSame('Tom & Jerry <3', (string) $item->title);
		$this->assertSame('http://example.com/viewtopic.php?p=5#p5', (string) $item->link);
		$this->assertSame('<p>Hello <b>world</b></p><hr />', (string) $item->description);
		$this->assertSame('alice', (string) $item->children(atom_to_rss::DC_NS)->creator);
		$this->assertSame('General', (string) $item->category);
		$this->assertSame('http://example.com/viewforum.php?f=2', (string) $item->category['domain']);
		$this->assertSame('http://example.com/viewtopic.php?p=5#p5', (string) $item->guid);
		$this->assertSame('true', (string) $item->guid['isPermaLink']);
		$this->assertSame('Sun, 18 Oct 2026 09:15:00 +0200', (string) $item->pubDate);
	}

	public function test_content_with_cdata_end_marker()
	{
		// "]]" followed by ">" can't sit in a CDATA section; the converter
		// escapes the text instead, so it survives
		$atom = str_replace('<![CDATA[
<p>', '<![CDATA[
<p>a ]]]]><![CDATA[> b', $this->atom_feed());
		$rss = $this->convert($atom);

		$this->assertStringContainsString('a ]]> b', (string) $rss->channel->item[0]->description);
	}

	public function test_published_falls_back_to_updated()
	{
		$atom = preg_replace('#<published>.*?</published>#', '', $this->atom_feed());
		$rss = $this->convert($atom);

		$this->assertSame('Mon, 19 Oct 2026 12:00:00 +0200', (string) $rss->channel->item[0]->pubDate);
	}

	public function test_empty_feed()
	{
		$atom = preg_replace('#<entry>.*</entry>#s', '', $this->atom_feed());
		$rss = $this->convert($atom);

		$this->assertCount(0, $rss->channel->item);
		$this->assertSame('Test & Board', (string) $rss->channel->title);
	}

	public static function invalid_data()
	{
		return [
			'not xml'		=> ['not xml at all'],
			'not atom'		=> ['<?xml version="1.0"?><rss version="2.0"><channel/></rss>'],
			'wrong ns'		=> ['<?xml version="1.0"?><feed><title>x</title></feed>'],
		];
	}

	/**
	 * @dataProvider invalid_data
	 */
	public function test_rejects_other_documents($xml)
	{
		$this->expectException(\RuntimeException::class);

		(new atom_to_rss())->convert($xml);
	}
}
