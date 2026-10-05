<?php
/**
 * Who an audit row is about, without naming them.
 *
 * @package   SugarCommerce\DataSubject
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\DataSubject;

/**
 * Class Pseudonym
 *
 * An audit trail of erasures that kept the addresses would undo every one
 * of them. One that kept nothing could not show that a second request was
 * for the same person. The start of a keyed hash of the address does both:
 * equal for the same address, and meaningless to anybody without the key.
 *
 * Keyed is the point — an unkeyed hash of an email address is reversed by
 * hashing a list of addresses. Hand this a digest from a keyed hash
 * (web-security's Hash), never `hash( 'sha256', $email )`.
 *
 * Short, so it identifies a request rather than serving as a lookup key
 * that could be joined against anything else. Sixteen hex characters is
 * 64 bits: collisions among one install's requests are not a concern.
 *
 * @since 1.0.0
 */
final readonly class Pseudonym {

	/**
	 * Characters kept.
	 */
	public const int LENGTH = 16;

	/**
	 * The audit subject for a keyed digest.
	 *
	 * @since 1.0.0
	 *
	 * @param string $digest Hex digest from a keyed hash.
	 * @param int    $length Characters kept.
	 *
	 * @return string Lower-case hex, or '' when the digest is not hex.
	 */
	public static function subject( string $digest, int $length = self::LENGTH ): string {
		$digest = strtolower( trim( $digest ) );

		return '' !== $digest && ctype_xdigit( $digest ) ? substr( $digest, 0, max( 1, $length ) ) : '';
	}
}
