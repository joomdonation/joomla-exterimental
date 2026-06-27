<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  com_content
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\Content\Administrator\Helper;

use Joomla\Component\Content\Administrator\Helper\PreviewTokenHelper;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for \Joomla\Component\Content\Administrator\Helper\PreviewTokenHelper
 *
 * @since  __DEPLOY_VERSION__
 */
class PreviewTokenHelperTest extends UnitTestCase
{
	/**
	 * Test secret key
	 *
	 * @var string
	 */
	private string $secret = 'test_secret_key_12345';

	/**
	 * Test token creation
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testCreateToken(): void
	{
		$helper = new PreviewTokenHelper($this->secret);
		$token = $helper->createToken(123, 24);

		$this->assertIsString($token);
		$this->assertNotEmpty($token);
		$this->assertStringContainsString('.', $token);

		// Token should be URL-safe (no +, /, or = characters)
		$this->assertStringNotContainsString('+', $token);
		$this->assertStringNotContainsString('/', $token);
		$this->assertStringNotContainsString('=', $token);
	}

	/**
	 * Test valid token validation
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenValid(): void
	{
		$helper = new PreviewTokenHelper($this->secret);
		$articleId = 456;
		$token = $helper->createToken($articleId, 24);

		$this->assertTrue($helper->validateToken($token, $articleId));
	}

	/**
	 * Test token validation with wrong article ID
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenWrongArticleId(): void
	{
		$helper = new PreviewTokenHelper($this->secret);
		$token = $helper->createToken(123, 24);

		$this->assertFalse($helper->validateToken($token, 456));
	}

	/**
	 * Test token validation with wrong secret
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenWrongSecret(): void
	{
		$helper1 = new PreviewTokenHelper('secret1');
		$token = $helper1->createToken(123, 24);

		$helper2 = new PreviewTokenHelper('secret2');
		$this->assertFalse($helper2->validateToken($token, 123));
	}

	/**
	 * Test token validation with expired token
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenExpired(): void
	{
		$helper = new PreviewTokenHelper($this->secret);

		// Create a token that expires in 0 hours (immediately expired)
		// We need to manually create an expired token by mocking time
		$articleId = 789;

		// Create token with very short expiration
		$payload = base64_encode(json_encode([
			'id'  => $articleId,
			'exp' => time() - 3600, // 1 hour ago
		]));

		$payload = str_replace('=', '', strtr($payload, '+/', '-_'));
		$signature = str_replace('=', '', strtr(base64_encode(hash_hmac('sha256', $payload, $this->secret, true)), '+/', '-_'));
		$expiredToken = $payload . '.' . $signature;

		$this->assertFalse($helper->validateToken($expiredToken, $articleId));
	}

	/**
	 * Test token validation with malformed token
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenMalformed(): void
	{
		$helper = new PreviewTokenHelper($this->secret);

		// Token without dot separator
		$this->assertFalse($helper->validateToken('invalidtoken', 123));

		// Token with too many parts
		$this->assertFalse($helper->validateToken('part1.part2.part3', 123));

		// Empty token
		$this->assertFalse($helper->validateToken('', 123));
	}

	/**
	 * Test token validation with tampered payload
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenTampered(): void
	{
		$helper = new PreviewTokenHelper($this->secret);
		$token = $helper->createToken(123, 24);

		// Tamper with the token by changing the payload
		$parts = explode('.', $token);
		$parts[0] = str_replace('A', 'B', $parts[0]);
		$tamperedToken = implode('.', $parts);

		$this->assertFalse($helper->validateToken($tamperedToken, 123));
	}

	/**
	 * Test token validation with oversized token
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenOversized(): void
	{
		$helper = new PreviewTokenHelper($this->secret);

		// Create a token longer than 512 characters
		$oversizedToken = str_repeat('a', 513);

		$this->assertFalse($helper->validateToken($oversizedToken, 123));
	}

	/**
	 * Test token validation with invalid JSON in payload
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenInvalidJson(): void
	{
		$helper = new PreviewTokenHelper($this->secret);

		// Create a token with invalid JSON payload
		$payload = str_replace('=', '', strtr(base64_encode('not valid json'), '+/', '-_'));
		$signature = str_replace('=', '', strtr(base64_encode(hash_hmac('sha256', $payload, $this->secret, true)), '+/', '-_'));
		$invalidToken = $payload . '.' . $signature;

		$this->assertFalse($helper->validateToken($invalidToken, 123));
	}

	/**
	 * Test token validation with missing fields in payload
	 *
	 * @return void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function testValidateTokenMissingFields(): void
	{
		$helper = new PreviewTokenHelper($this->secret);

		// Token with missing 'exp' field
		$payload = str_replace('=', '', strtr(base64_encode(json_encode(['id' => 123])), '+/', '-_'));
		$signature = str_replace('=', '', strtr(base64_encode(hash_hmac('sha256', $payload, $this->secret, true)), '+/', '-_'));
		$invalidToken = $payload . '.' . $signature;

		$this->assertFalse($helper->validateToken($invalidToken, 123));
	}
}
