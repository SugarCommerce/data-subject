<?php
/**
 * A stored consent answer, as the person would read it.
 *
 * @package   SugarCommerce\DataSubject
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\DataSubject;

/**
 * Enum Consent
 *
 * Consent is stored as 1, 0 or null, and the null is the one that matters:
 * "not asked" is not "no". An export that prints it as "no" tells the
 * person they declined something they were never offered; one that prints
 * it as "yes" is worse. Three answers, kept three.
 *
 * @since 1.0.0
 */
enum Consent: string {

	case Yes      = 'yes';
	case No       = 'no';
	case NotAsked = 'not asked';

	/**
	 * The answer a stored value records.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $stored 1 or '1' (or true) for yes; null or '' for not
	 *                      asked; anything else is no.
	 *
	 * @return self
	 */
	public static function of( mixed $stored ): self {
		return match ( true ) {
			null === $stored || '' === $stored => self::NotAsked,
			1 === (int) $stored                => self::Yes,
			default                            => self::No,
		};
	}

	/**
	 * For a spreadsheet: yes, no, or a blank cell when nobody asked.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function cell(): string {
		return self::NotAsked === $this ? '' : $this->value;
	}
}
