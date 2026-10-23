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
 * is not a substring of the payload that carries it — nor is `é`, which it
 * writes as `\u00e9`, unless the encoder was told otherwise. Every way PHP's
 * json_encode() writes an address is looked for, as well as the address.
 *
 * Unicode normalisation is not: an address typed with a decomposed accent
 * and stored with a composed one does not match. Normalise both first, with
 * intl's Normalizer, if your addresses can carry accents.
 *
 * @since 1.0.0
 */
final readonly class Mentions {

	/**
	 * Something@something, stopping at space, brackets, quotes and list
	 * punctuation. No modifiers, so strip() can add /u and fall back
	 * without it.
	 *
	 * @since 1.2.0
	 */
	private const string ADDRESS = '/[^\\s<>()\\[\\]"\',;:]+@[^\\s<>()\\[\\]"\',;:]+/';

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
	 * @param string       $text   A stored payload, opened.
	 * @param list<string> $values Addresses or other identifiers. Empty ones
	 *                             are ignored — '' is a substring of
	 *                             everything.
	 *
	 * @return bool
	 */
	public static function any( string $text, array $values ): bool {
		$text = mb_strtolower( $text );

		foreach ( $values as $value ) {
			foreach ( self::forms( self::key( (string) $value ) ) as $form ) {
				if ( str_contains( $text, $form ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Text with every email address in it replaced.
	 *
	 * For text kept in the clear but written by somebody else — a mail
	 * provider's bounce, an upstream error — which may name the person it
	 * was about, and which no erasure will ever find by column. Rather than
	 * search it later, keep it without the addresses.
	 *
	 * Anything with an @ between two runs of characters that are not spaces,
	 * brackets, quotes or list punctuation counts. That is deliberately
	 * looser than an address validator: a false positive costs a word of an
	 * error message, a miss keeps an address nobody can find to erase. The
	 * brackets and quotes stop it at `<ada@example.com>` and inside a JSON
	 * string; a JSON-escaped slash or `\u00e9` stays inside the run, so an
	 * address json_encode() wrote is replaced whole.
	 *
	 * @since 1.2.0
	 *
	 * @param string $text Free text, perhaps naming someone.
	 * @param string $with What each address becomes.
	 *
	 * @return string
	 */
	public static function strip( string $text, string $with = '[address]' ): string {
		$address = static fn (): string => $with;

		// Invalid UTF-8 fails a /u match outright; strip byte-wise then,
		// rather than hand the text back with the address still in it.
		return preg_replace_callback( self::ADDRESS . 'u', $address, $text )
			?? (string) preg_replace_callback( self::ADDRESS, $address, $text );
	}

	/**
	 * Every way a matching key can appear in stored text.
	 *
	 * As it is, and inside a JSON string as json_encode() writes it by
	 * default, with unescaped slashes, and with unescaped Unicode. An empty
	 * form is never returned: it would match everything.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key A matching key, from key().
	 *
	 * @return list<string>
	 */
	private static function forms( string $key ): array {
		if ( '' === $key ) {
			return array();
		}

		$forms = array( $key );

		foreach ( array( 0, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE ) as $flags ) {
			$encoded = json_encode( $key, $flags );

			if ( is_string( $encoded ) && strlen( $encoded ) > 2 ) {
				$forms[] = substr( $encoded, 1, -1 );
			}
		}

		return array_values( array_unique( $forms ) );
	}

	/**
	 * The distinct, non-empty matching keys among some values.
	 *
	 * @since 1.0.0
	 *
	 * @param list<string> $values Addresses, perhaps repeated or blank.
	 *
	 * @return list<string>
	 */
	public static function keys( array $values ): array {
		return array_values( array_unique( array_filter( array_map( static fn ( $value ): string => self::key( (string) $value ), $values ), static fn ( string $key ): bool => '' !== $key ) ) );
	}
}
