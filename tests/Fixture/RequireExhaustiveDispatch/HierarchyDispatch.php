<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExhaustiveDispatch;

final class HierarchyDispatch
{
    public function partialMatch(Payment $payment): string
    {
        return match ($payment::class) {
            Visa::class => 'visa',
            MasterCard::class => 'mastercard',
            default => 'other',
        };
    }

    public function completeMatch(Payment $payment): string
    {
        return match ($payment::class) {
            Visa::class => 'visa',
            MasterCard::class => 'mastercard',
            BankTransfer::class => 'transfer',
            default => 'unreachable',
        };
    }

    public function partialSwitch(Payment $payment): string
    {
        switch (get_class($payment)) {
            case Visa::class:
                return 'visa';
            case MasterCard::class:
                return 'mastercard';
        }

        return 'other';
    }

    public function partialSwitchWithDefault(CardPayment $payment): string
    {
        switch ($payment::class) {
            case Visa::class:
                return 'visa';
            default:
                return 'other';
        }
    }

    public function unrelatedMatch(Payment $payment): string
    {
        return match ($payment::class) {
            'not-a-class-of-this-hierarchy' => 'none',
            default => 'other',
        };
    }

    public function instanceOfMatch(Payment $payment): string
    {
        return match (true) {
            $payment instanceof Visa => 'visa',
            default => 'other',
        };
    }
}
