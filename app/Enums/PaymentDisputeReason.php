<?php

namespace App\Enums;

enum PaymentDisputeReason: string
{
    case BankCannotProcess = 'bank_cannot_process';
    case CheckReturned = 'check_returned';
    case CreditNotProcessed = 'credit_not_processed';
    case CustomerInitiated = 'customer_initiated';
    case DebitNotAuthorized = 'debit_not_authorized';
    case Duplicate = 'duplicate';
    case Fraudulent = 'fraudulent';
    case General = 'general';
    case IncorrectAccountDetails = 'incorrect_account_details';
    case InsufficientFunds = 'insufficient_funds';
    case Noncompliant = 'noncompliant';
    case ProductNotReceived = 'product_not_received';
    case ProductUnacceptable = 'product_unacceptable';
    case SubscriptionCanceled = 'subscription_canceled';
    case Unrecognized = 'unrecognized';
    case Unknown = 'unknown';

    public static function normalize(?string $reason): self
    {
        return self::tryFrom((string) $reason) ?? self::Unknown;
    }
}
