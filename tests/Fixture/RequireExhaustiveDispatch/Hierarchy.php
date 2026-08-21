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
