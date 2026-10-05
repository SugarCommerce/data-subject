<?php
/**
 * Whether stored text mentions a person.
 *
 * @package   SugarCommerce\DataSubject
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\DataSubject;

/**
 * Class Mentions
 *
 * An erasure that cleans the tables and leaves the queues is not one. A
 * pending email, a webhook waiting to retry, a logged payload — each can
 * carry the address being erased, and none has a column to find it by.
 * So they are searched as text.
 *
 * Two ways that search quietly misses: case, because the address was typed
 * differently on the day the payload was written; and JSON, which escapes
 * `/` as `\/` — legal in an address's local part — so the address as typed
 * is not a substring of the payload that carries it. Both are covered here.
 *
 * @since 1.0.0
 */
final readonly class Mentions {

	/**
	 * An address as a matching key: trimmed and case-folded.
	 *
	 * For comparing, never for sending. RFC 5321 lets a mail server treat
	 * the local part as case-sensitive; a person asking to be erased does
	 * not care which case they typed it in.
	 *
	 * @since 1.0.0
	 *
	 * @param string $address As stored or typed.
	 *
	 * @return string
	 */
	public static function key( string $address ): string {
		return mb_strtolower( trim( $address ) );
	}

	/**
	 * Whether text mentions any of these values.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $text   A stored payload, opened.
	 * @param string[] $values Addresses or other identifiers. Empty ones are
	 *                         ignored — '' is a substring of everything.
	 *
	 * @return bool
	 */
	public static function any( string $text, array $values ): bool {
		$text = mb_strtolower( $text );

		foreach ( $values as $value ) {
			$value = self::key( (string) $value );

			if ( '' === $value ) {
				continue;
			}

			if ( str_contains( $text, $value ) || str_contains( $text, trim( (string) json_encode( $value ), '"' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The distinct, non-empty matching keys among some values.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $values Addresses, perhaps repeated or blank.
	 *
	 * @return string[]
	 */
	public static function keys( array $values ): array {
		return array_values( array_unique( array_filter( array_map( static fn ( $value ): string => self::key( (string) $value ), $values ), static fn ( string $key ): bool => '' !== $key ) ) );
	}
}
