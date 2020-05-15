<?php

namespace Drupal\Tests\mimemail\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mimemail\Utility\MimeMailFormatHelper;

/**
 * Tests that Mime Mail utility functions work properly.
 *
 * @coversDefaultClass \Drupal\mimemail\Utility\MimeMailFormatHelper
 *
 * @group mimemail
 */
class MimeMailKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'mailsystem',
    'mimemail',
  ];

  /**
   * Tests helper function for formatting URLs.
   *
   * @param string $url
   *   URL to test.
   * @param bool $absolute
   *   Whether the URL is absolute.
   * @param string $expected
   *   URL after formatting.
   * @param string $message
   *   Description of the result we are expecting.
   *
   * @dataProvider providerTestUrl
   * @covers ::mimeMailUrl
   */
  public function testUrl($url, $absolute, $expected, $message) {
    $result = MimeMailFormatHelper::mimeMailUrl($url, $absolute);
    $this->assertSame($result, $expected, $message);
  }

  /**
   * Provides test data for testUrl().
   */
  public function providerTestUrl() {
    // Format of each element is:
    // - url: URL to test.
    // - absolute: Whether the URL is absolute.
    // - expected: URL after formatting.
    // - message: Description of the result we are expecting.
    return [
      [
        '#',
        FALSE,
        '#',
        'Hash mark URL without fragment left intact.',
      ],
      [
        '/sites/default/files/styles/thumbnail/public/image.jpg?itok=Wrl6Qi9U',
        TRUE,
        '/sites/default/files/styles/thumbnail/public/image.jpg',
        'Security token removed from styled image URL.',
      ],
      [
        $expected = 'public://' . $this->randomMachineName() . ' ' . $this->randomMachineName() . '.' . $this->randomMachineName(3),
        TRUE,
        $expected,
        'Space in the filename of the attachment left intact.',
      ],
    ];
  }

  /**
   * Tests the regular expression for extracting the mail address.
   *
   * @covers ::mimeMailHeaders
   */
  public function testHeaders() {
    $chars = ['-', '.', '+', '_'];
    $name = $this->randomString();
    $local = $this->randomMachineName() . $chars[array_rand($chars)] . $this->randomMachineName();
    $domain = $this->randomMachineName() . '-' . $this->randomMachineName() . '.' . $this->randomMachineName(rand(2, 4));
    $headers = MimeMailFormatHelper::mimeMailHeaders([], "$name <$local@$domain>");
    $result = $headers['Return-Path'];
    $expected = "<$local@$domain>";
    $this->assertSame($result, $expected, 'Return-Path header field correctly set.');
  }

}
