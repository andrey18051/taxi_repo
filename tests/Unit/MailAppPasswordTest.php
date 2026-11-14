<?php

namespace Tests\Unit;

use App\Mail\MailAlertText;
use App\Mail\MailAppPassword;
use App\Mail\SmtpAuthFailure;
use PHPUnit\Framework\TestCase;

class MailAppPasswordTest extends TestCase
{
    public function test_normalize_accepts_spaced_app_password(): void
    {
        $this->assertSame('abcdefghijklmnop', MailAppPassword::normalize('abcd efgh ijkl mnop'));
    }

    public function test_normalize_rejects_account_password_and_short_value(): void
    {
        $this->assertNull(MailAppPassword::normalize('taxi180571'));
        $this->assertNull(MailAppPassword::normalize('abcd efgh'));
        $this->assertNull(MailAppPassword::normalize(''));
    }

    public function test_auth_failure_matches_gmail_smtp_refusal(): void
    {
        $this->assertTrue(SmtpAuthFailure::matches(
            'Failed to authenticate on SMTP server with username "taxi.easy.ua.sup@gmail.com" using 3 possible authenticators.'
        ));
        $this->assertTrue(SmtpAuthFailure::matches('534-5.7.9 Application-specific password required'));
        $this->assertFalse(SmtpAuthFailure::matches('Connection timed out'));
        $this->assertFalse(SmtpAuthFailure::matches('Expected response code 250 but got an empty response'));
    }

    public function test_command_parses_bot_suffix_and_rejects_empty(): void
    {
        $this->assertTrue(MailAlertText::isCommand('/mailpass'));
        $this->assertTrue(MailAlertText::isCommand('/mailpass@TaxiBot abcd efgh ijkl mnop'));
        $this->assertNull(MailAlertText::commandPassword('/mailpass'));
        $this->assertSame(
            'abcdefghijklmnop',
            MailAlertText::commandPassword("/mailpass@TaxiBot abcd efgh ijkl mnop")
        );
        $this->assertFalse(MailAlertText::isCommand('привіт'));
    }

    public function test_peer_and_pinned_text_name_the_server_without_a_password(): void
    {
        $this->assertSame('тест', MailAlertText::serverLabel('https://t.easy-order-taxi.site'));
        $this->assertSame('https://m.easy-order-taxi.site', MailAlertText::peerUrl('https://t.easy-order-taxi.site'));
        $this->assertSame('боевой', MailAlertText::serverLabel('https://m.easy-order-taxi.site'));
        $this->assertSame('https://t.easy-order-taxi.site', MailAlertText::peerUrl('https://m.easy-order-taxi.site'));

        $pinned = MailAlertText::pinned('https://t.easy-order-taxi.site');
        $this->assertStringContainsString('Сервер: тест', $pinned);
        $this->assertStringContainsString('/mailpass', $pinned);
        $this->assertStringNotContainsString('abcdefghijklmnop', $pinned);
    }
}
