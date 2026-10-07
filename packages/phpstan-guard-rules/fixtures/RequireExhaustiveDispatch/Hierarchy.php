<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

interface Payment
{
}

abstract class CardPayment implements Payment
{
}

class Visa extends CardPayment
{
}

final class MasterCard extends CardPayment
{
}

final class BankTransfer implements Payment
{
}

/**
 * A second implementation off the card branch, so a dispatch that misses more
 * than one class has more than one to report.
 *
 * This is a class rather than an enum on purpose. The package supports PHP 8.0,
 * where UnitEnum does not exist and the analyser therefore reflects no enum at
 * all: an enum here would be left out of the hierarchy on that version and the
 * rule would report a different set of classes than it reports on every other.
 * Enum dispatch is the subject of RequireExhaustiveDispatchRule, whose Suit
 * fixture reaches the analyser through the subject's type instead.
 */
final class Wallet implements Payment
{
}
