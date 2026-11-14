<?php

namespace Tests\Unit;

use App\Http\Controllers\WfpController;
use App\Models\Orderweb;
use App\Models\WfpInvoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class WfpRefundMerchantPreferenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required');
        }

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['app.merchantAccountMy' => 'm_easy_order_taxi_site']);
        config(['app.merchantSecretKeyMy' => 'card-secret']);
        config(['app.merchantAccount' => 'play_google_com_f183e']);
        config(['app.merchantSecretKey' => 'gpay-secret']);

        Schema::create('wfp_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('merchantAccount')->nullable();
            $table->string('orderReference')->nullable();
            $table->string('amount')->nullable();
            $table->string('transactionStatus')->nullable();
            $table->string('reason')->nullable();
            $table->string('reasonCode')->nullable();
            $table->timestamps();
        });
        Schema::create('orderwebs', function (Blueprint $table) {
            $table->id();
            $table->string('dispatching_order_uid')->nullable();
            $table->string('wfp_order_id')->nullable();
            $table->string('comment')->nullable();
            $table->string('city')->nullable();
            $table->string('server')->nullable();
            $table->string('pay_system')->nullable();
            $table->timestamps();
        });

        WfpInvoice::unsetEventDispatcher();
        Orderweb::unsetEventDispatcher();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('orderwebs');
        Schema::dropIfExists('wfp_invoices');
        parent::tearDown();
    }

    public function test_resolve_merchant_prefers_invoice_card_over_google_pay_dispatch(): void
    {
        $invoice = new WfpInvoice();
        $invoice->dispatching_order_uid = 'uid-hold';
        $invoice->merchantAccount = 'm_easy_order_taxi_site';
        $invoice->orderReference = 'V_OLD_HOLD';
        $invoice->amount = '7';
        $invoice->transactionStatus = 'WaitingAuthComplete';
        $invoice->save();

        $method = new ReflectionMethod(WfpController::class, 'resolveMerchantForInvoice');
        $method->setAccessible(true);
        $resolved = $method->invoke(
            new WfpController(),
            $invoice,
            ['merchantAccount' => 'play_google_com_f183e']
        );

        $this->assertSame('m_easy_order_taxi_site', $resolved['merchantAccount']);
        $this->assertSame('card-secret', $resolved['secretKey']);
    }

    public function test_order_not_found_response_detected(): void
    {
        $method = new ReflectionMethod(WfpController::class, 'isWfpOrderNotFoundResponse');
        $method->setAccessible(true);
        $ctrl = new WfpController();

        $this->assertTrue($method->invoke($ctrl, [
            'transactionStatus' => 'Declined',
            'reasonCode' => 1127,
            'reason' => 'Order Not Found',
        ]));
        $this->assertFalse($method->invoke($ctrl, [
            'transactionStatus' => 'Declined',
            'reasonCode' => 1129,
        ]));
    }

    public function test_collect_candidates_includes_card_and_google_pay(): void
    {
        $invoice = new WfpInvoice();
        $invoice->merchantAccount = 'play_google_com_f183e';
        $invoice->orderReference = 'V_X';
        $invoice->save();

        $method = new ReflectionMethod(WfpController::class, 'collectRefundMerchantCandidates');
        $method->setAccessible(true);
        $candidates = $method->invoke(new WfpController(), $invoice, null);
        $accounts = array_column($candidates, 'merchantAccount');

        $this->assertContains('play_google_com_f183e', $accounts);
        $this->assertContains('m_easy_order_taxi_site', $accounts);
    }
}
