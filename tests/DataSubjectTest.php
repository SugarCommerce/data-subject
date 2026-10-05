<?php
/**
 * Data subject request helpers test suite.
 *
 * @package   SugarCommerce\DataSubject
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\DataSubject\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SugarCommerce\DataSubject\{Consent, Mentions, Pseudonym, Redact, Tally};

/**
 * Class DataSubjectTest
 *
 * The pieces that go wrong quietly: a credential in an export, an address a
 * search missed, an audit row that names the person it erased.
 */
final class DataSubjectTest extends TestCase {

	/* ─── Redact ───────────────────────────────────────────────────────── */

	public function test_columns_are_dropped_and_the_rest_kept(): void {
		$rows = array( array( 'id' => 1, 'access_token' => 'secret', 'email_hash' => 'abc', 'amount' => 900 ) );

		$this->assertSame( array( array( 'id' => 1, 'amount' => 900 ) ), Redact::rows( $rows, 'access_token', 'email_hash' ) );
		$this->assertSame( array( 'id' => 1 ), ( Redact::without( 'x' ) )( array( 'id' => 1 ) ), 'A column that is not there is no error.' );
	}

	/* ─── Mentions ─────────────────────────────────────────────────────── */

	public function test_an_address_is_found_whatever_its_case(): void {
		$this->assertTrue( Mentions::any( '{"to":"Ada@Example.com"}', array( 'ada@example.com' ) ) );
		$this->assertTrue( Mentions::any( 'to ada@example.com', array( ' ADA@example.com ' ) ) );
	}

	/**
	 * json_encode writes `/` as `\/`, so the address as typed is not in
	 * the payload that carries it.
	 */
	public function test_an_address_is_found_as_json_wrote_it(): void {
		$payload = (string) json_encode( array( 'email' => 'a/b@example.com' ) );

		$this->assertStringNotContainsString( 'a/b@example.com', $payload );
		$this->assertTrue( Mentions::any( $payload, array( 'a/b@example.com' ) ) );
	}

	public function test_nothing_mentions_an_empty_value(): void {
		$this->assertFalse( Mentions::any( 'anything at all', array( '', '  ' ) ) );
		$this->assertFalse( Mentions::any( 'other@example.com', array( 'ada@example.com' ) ) );
	}

	public function test_keys_are_distinct_folded_and_non_empty(): void {
		$this->assertSame( array( 'ada@example.com', 'bo@example.com' ), Mentions::keys( array( 'Ada@Example.com', '', ' ada@example.com', 'bo@example.com', null ) ) );
		$this->assertSame( 'ada@example.com', Mentions::key( "  ADA@example.com\n" ) );
	}

	/* ─── Tally ────────────────────────────────────────────────────────── */

	public function test_a_summary_leaves_out_the_noughts(): void {
		$this->assertSame( '2 orders, 1 licenses', Tally::summary( array( 'orders' => 2, 'gifts' => 0, 'licenses' => 1 ) ) );
		$this->assertSame( Tally::NOTHING, Tally::summary( array( 'orders' => 0 ) ) );
		$this->assertSame( 'none', Tally::summary( array(), 'none' ) );
	}

	public function test_counts_are_taken_by_kind(): void {
		$this->assertSame( array( 'orders' => 2, 'reports' => 0 ), Tally::count( array( 'orders' => array( 1, 2 ), 'reports' => array() ) ) );
	}

	public function test_a_note_is_the_summary_then_the_reason(): void {
		$this->assertSame( '1 orders — ticket 4411', Tally::note( array( 'orders' => 1 ), ' ticket 4411 ' ) );
		$this->assertSame( 'nothing held', Tally::note( array(), '' ) );
	}

	/* ─── Pseudonym ────────────────────────────────────────────────────── */

	public function test_the_subject_is_the_start_of_the_digest(): void {
		$digest = hash_hmac( 'sha256', 'ada@example.com', 'key' );

		$this->assertSame( substr( $digest, 0, 16 ), Pseudonym::subject( $digest ) );
		$this->assertSame( substr( $digest, 0, 8 ), Pseudonym::subject( strtoupper( $digest ), 8 ) );
	}

	public function test_an_address_is_never_a_subject(): void {
		$this->assertSame( '', Pseudonym::subject( 'ada@example.com' ) );
		$this->assertSame( '', Pseudonym::subject( '' ) );
	}

	/* ─── Consent ──────────────────────────────────────────────────────── */

	/**
	 * @return array<string, array{mixed, Consent, string}>
	 */
	public static function answers(): array {
		return array(
			'yes'        => array( 1, Consent::Yes, 'yes' ),
			'yes text'   => array( '1', Consent::Yes, 'yes' ),
			'no'         => array( 0, Consent::No, 'no' ),
			'no text'    => array( '0', Consent::No, 'no' ),
			'not asked'  => array( null, Consent::NotAsked, '' ),
			'empty'      => array( '', Consent::NotAsked, '' ),
		);
	}

	#[DataProvider( 'answers' )]
	public function test_not_asked_is_never_no( mixed $stored, Consent $answer, string $cell ): void {
		$this->assertSame( $answer, Consent::of( $stored ) );
		$this->assertSame( $cell, Consent::of( $stored )->cell() );
	}

	public function test_the_reading_says_not_asked(): void {
		$this->assertSame( 'not asked', Consent::of( null )->value );
	}
}
