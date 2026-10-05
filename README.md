# Data Subject

The small, easily-botched pieces of answering a GDPR access or erasure request: the export without the columns a person must not be handed, finding an address in stored payloads however JSON escaped it, an audit trail that does not undo the erasure it records, and plain summaries of what was done. Zero dependencies.

## Why

Where your data lives is your schema, and no library can know it. What can be shared is the handful of decisions every access and erasure path makes, and gets wrong in the same few ways:

- **The export hands over a credential.** `SELECT *` on the orders table includes the download token — which works for whoever reads the export — and the lookup hash, which means nothing to the person and everything to whoever holds your key.
- **The erasure misses the queues.** A pending email or a webhook waiting to retry carries the address, has no column to find it by, and is searched as text. JSON writes `/` as `\/`, so an address with a slash in it is not a substring of the payload that carries it.
- **The audit trail undoes the erasure.** A log of "erased ada@example.com" is the address, kept. A log of nothing cannot show the second request was for the same person.
- **"Not asked" is exported as "no".** A null consent column is neither answer.

## Features

- ✂️ **`Redact`** — drop named columns from every row of a section.
- 🔍 **`Mentions`** — find addresses in stored text, case-folded and as JSON escaped them.
- 🕶️ **`Pseudonym`** — the start of a keyed digest, for an audit row that identifies a request without naming anyone.
- 🧾 **`Tally`** — "2 orders, 1 licenses — ticket 4411", leaving out the noughts.
- ☑️ **`Consent`** — yes, no and not asked, kept three.

## Requirements

PHP 8.5+

## Installation

```bash
composer require sugarcommerce/data-subject
```

## Usage

```php
use SugarCommerce\DataSubject\{Consent, Mentions, Pseudonym, Redact, Tally};

// Access: what is held, minus what must not leave.
$export = array(
    'orders'  => Redact::rows( $orders, 'access_token', 'email_hash' ),
    'consent' => array_map( fn ( $o ) => Consent::of( $o['marketing_consent'] )->value, $orders ),  // "not asked", never "no"
);

// Erasure: the queues, searched as text.
$addresses = Mentions::keys( array( $email, ...$emails_on_their_orders ) );
$doomed    = array_filter( $outbox, fn ( $row ) => Mentions::any( $row['payload'], $addresses ) );

// The audit row: who, without naming them; what, without listing it.
$actions->record( 'buyer.erase', Pseudonym::subject( $keyed_hash ), Tally::note( $counts, $ticket ) );
```

`Pseudonym::subject()` wants a digest from a **keyed** hash. An unkeyed SHA-256 of an address is reversed by hashing a list of addresses.

`Mentions::key()` is a matching key, never an address to send to: RFC 5321 lets a server treat the local part as case-sensitive.

## What it does not do

Find the rows, decide what an erasure keeps (an order is usually a financial record that must stay, without the person), or run it in a transaction. Those are your schema and your policy. Run the erasure all-or-nothing: one that stops halfway leaves records half-anonymised and an audit row saying it was done.

## Testing

```bash
composer install
composer test
```

17 tests: dropped and kept columns, case-folded and JSON-escaped matches, empty values that would match everything, summaries and notes, pseudonyms that refuse to be an address, and the three consent answers.

## License

GPL-2.0-or-later
