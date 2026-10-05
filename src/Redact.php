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
 * it leaves out and why.
 *
 * @since 1.0.0
 */
final readonly class Redact {

	/**
	 * A row mapper that drops columns, for array_map().
	 *
	 * @since 1.0.0
	 *
	 * @param string ...$columns Column names.
	 *
	 * @return \Closure fn( array $row ): array
	 */
	public static function without( string ...$columns ): \Closure {
		$drop = array_flip( $columns );

		return static fn ( array $row ): array => array_diff_key( $row, $drop );
	}

	/**
	 * Rows with columns dropped.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array> $rows       Rows.
	 * @param string            ...$columns Column names.
	 *
	 * @return array<int, array>
	 */
	public static function rows( array $rows, string ...$columns ): array {
		return array_map( self::without( ...$columns ), $rows );
	}
}
