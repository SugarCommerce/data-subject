<?php
/**
 * Rows with the columns a person must not be handed taken out.
 *
 * @package   SugarCommerce\DataSubject
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\DataSubject;

/**
 * Class Redact
 *
 * An access request is answered with everything held about a person — and
 * the easy mistake is handing it over with `SELECT *`. Some columns in
 * those rows must not leave: a download token is a credential that works
 * for whoever reads the export; a lookup hash means nothing to the person
 * and everything to whoever holds the key; a gift's recipient address is
 * somebody else's personal data.
 *
 * A deny-list per section, applied by name, so the export says plainly what
 * it leaves out and why. Names match whatever their case — SQLite's do, so
 * `Access_Token` is the column `access_token` and must go with it.
 *
 * A deny-list fails open: a credential column added next year is exported
 * until somebody adds it here. For a section whose columns you can name,
 * only() keeps those and nothing else.
 *
 * Top-level columns only. A column holding JSON is one value to this class;
 * open it and redact inside it yourself.
 *
 * @since 1.0.0
 */
final readonly class Redact {

	/**
	 * A row mapper that drops columns, for array_map().
	 *
	 * @since 1.0.0
	 *
	 * @param string ...$columns Column names, in any case.
	 *
	 * @return \Closure(array<array-key, mixed>): array<array-key, mixed>
	 */
	public static function without( string ...$columns ): \Closure {
		$drop = array_flip( array_map( strtolower( ... ), $columns ) );

		return static fn ( array $row ): array => array_filter(
			$row,
			static fn ( int|string $name ): bool => ! isset( $drop[ strtolower( (string) $name ) ] ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * A row mapper that keeps only these columns, for array_map().
	 *
	 * The allow-list form: a column nobody named — one added after the
	 * export was written — stays out.
	 *
	 * @since 1.1.0
	 *
	 * @param string ...$columns Column names, in any case.
	 *
	 * @return \Closure(array<array-key, mixed>): array<array-key, mixed>
	 */
	public static function only( string ...$columns ): \Closure {
		$keep = array_flip( array_map( strtolower( ... ), $columns ) );

		return static fn ( array $row ): array => array_filter(
			$row,
			static fn ( int|string $name ): bool => isset( $keep[ strtolower( (string) $name ) ] ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Rows with columns dropped.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<array-key, mixed>> $rows       Rows.
	 * @param string                              ...$columns Column names, in
	 *                                                        any case.
	 *
	 * @return array<int, array<array-key, mixed>>
	 */
	public static function rows( array $rows, string ...$columns ): array {
		return array_map( self::without( ...$columns ), $rows );
	}
}
