<?php

namespace Tests\Unit;

use App\Support\MantisBugReportGateway;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class MantisBugReportGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mantis.api_url' => 'http://127.0.0.1:8989/api/rest/issues',
            'services.mantis.url' => 'https://t.easy-order-taxi.site/mantis/',
        ]);
    }

    public function test_forwards_body_to_local_mantis_and_adds_view_url(): void
    {
        Http::fake([
            'http://127.0.0.1:8989/api/rest/issues' => Http::response([
                'issue' => ['id' => 99, 'summary' => 'crash'],
            ], 201),
        ]);

        $result = (new MantisBugReportGateway())->forward('app-token', '{"summary":"crash"}');

        $this->assertSame(201, $result['status']);
        $this->assertSame(99, $result['body']['issue']['id']);
        $this->assertSame(
            'https://t.easy-order-taxi.site/mantis/view.php?id=99',
            $result['body']['view_url']
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:8989/api/rest/issues'
                && $request->hasHeader('Authorization', 'app-token')
                && $request->body() === '{"summary":"crash"}';
        });
    }

    public function test_missing_token_does_not_call_mantis(): void
    {
        Http::fake();

        $result = (new MantisBugReportGateway())->forward('  ', '{"summary":"x"}');

        $this->assertSame(401, $result['status']);
        Http::assertNothingSent();
    }

    public function test_mantis_error_is_passed_through_without_view_url(): void
    {
        Http::fake([
            'http://127.0.0.1:8989/api/rest/issues' => Http::response(['message' => 'denied'], 401),
        ]);

        $result = (new MantisBugReportGateway())->forward('bad-token', '{"summary":"x"}');

        $this->assertSame(401, $result['status']);
        $this->assertArrayNotHasKey('view_url', $result['body']);
    }

    public function test_remote_mantis_url_is_rejected(): void
    {
        config(['services.mantis.api_url' => 'https://omp.net.ua/mantis/api/rest/issues']);
        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        (new MantisBugReportGateway())->forward('app-token', '{"summary":"x"}');
    }
}
