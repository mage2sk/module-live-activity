<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Controller\Ajax;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\LiveActivity\Controller\Ajax\GetActivity;
use Panth\LiveActivity\Helper\Config;
use Panth\LiveActivity\Model\ActivityProvider;
use PHPUnit\Framework\TestCase;

class GetActivityTest extends TestCase
{
    private ?array $data = null;
    private array $activityCalls = [];
    private array $statsCalls = [];

    private function controller(bool $enabled, $productId): GetActivity
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->data = $data;
            return $json;
        });
        $jsonFactory = $this->createStub(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($json);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key) => $key === 'product_id' ? $productId : null
        );

        $provider = $this->createStub(ActivityProvider::class);
        $provider->method('getRecentActivity')->willReturnCallback(function ($id = null) {
            $this->activityCalls[] = $id;
            return [['type' => 'purchase']];
        });
        $provider->method('getViewerStats')->willReturnCallback(function (int $id) {
            $this->statsCalls[] = $id;
            return ['current_viewers' => 5];
        });

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('getFrontendConfig')->willReturn(['position' => 'top-left']);

        return new GetActivity($jsonFactory, $request, $provider, $config);
    }

    public function testDisabledModuleReturnsFailureWithoutQueryingActivity(): void
    {
        $this->controller(false, '5')->execute();

        $this->assertSame(['success' => false, 'message' => 'Live Activity is disabled'], $this->data);
        $this->assertSame([], $this->activityCalls);
    }

    public function testWithoutProductReturnsActivityAndConfigOnly(): void
    {
        $this->controller(true, null)->execute();

        $this->assertSame([
            'success' => true,
            'activities' => [['type' => 'purchase']],
            'config' => ['position' => 'top-left'],
        ], $this->data);
        $this->assertSame([null], $this->activityCalls);
        $this->assertSame([], $this->statsCalls);
    }

    public function testProductIdIsCastAndStatsAdded(): void
    {
        $this->controller(true, '12abc')->execute();

        $this->assertSame([12], $this->activityCalls);
        $this->assertSame([12], $this->statsCalls);
        $this->assertSame(['current_viewers' => 5], $this->data['stats']);
    }
}
