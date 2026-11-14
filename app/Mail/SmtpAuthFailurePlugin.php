<?php

namespace App\Mail;

use App\Services\MailAuthAlert;
use App\Services\MailPasswordStore;

final class SmtpAuthFailurePlugin implements \Swift_Events_TransportExceptionListener
{
    public function exceptionThrown(\Swift_Events_TransportExceptionEvent $evt)
    {
        if (MailPasswordStore::isSuppressed()) {
            return;
        }
        try {
            MailAuthAlert::report($evt->getException()->getMessage());
        } catch (\Throwable $e) {
            // Сбой оповещения не должен подменять исходную ошибку почты.
        }
    }
}
