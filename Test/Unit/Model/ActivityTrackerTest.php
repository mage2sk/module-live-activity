<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LiveActivity\Helper\Config;
use Panth\LiveActivity\Model\Activity;
use Panth\LiveActivity\Model\ActivityFactory;
use Panth\LiveActivity\Model\ActivityTracker;
use Panth\LiveActivity\Model\ResourceModel\Activity as ActivityResource;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ActivityTrackerTest extends TestCase
{
    private ?Activity $saved = null;
    private array $logged = [];

    private function tracker(
        bool $enabled,
        bool $realData,
        ?DataObject $customer = null,
        ?\Exception $saveError = null
    ): ActivityTracker {
        $this->saved = null;
        $this->logged = [];

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('getConfig')->willReturnCallback(
            static fn(string $path) => $path === Config::XML_PATH_USE_REAL_DATA ? ($realData ? '1' : '0') : null
        );
        $config->method('shortenCustomerName')->willReturnCallback(
            static fn(string $first, string $last) => $first . '|' . $last
        );

        $activity = (new \ReflectionClass(Activity::class))->newInstanceWithoutConstructor();
        $factory = $this->createStub(ActivityFactory::class);
        $factory->method('create')->willReturn($activity);

        $resource = $this->createStub(ActivityResource::class);
        $resource->method('save')->willReturnCallback(function (Activity $a) use ($resource, $saveError) {
            if ($saveError) {
                throw $saveError;
            }
            $this->saved = $a;
            return $resource;
        });

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(4);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $session = $this->createStub(CustomerSession::class);
        $session->method('isLoggedIn')->willReturn($customer !== null);
        $session->method('getCustomer')->willReturn($customer);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function ($message) {
            $this->logged[] = $message;
        });

        return new ActivityTracker($factory, $resource, $config, $storeManager, $session, $logger);
    }

    public function testNothingSavedWhenModuleDisabled(): void
    {
        $this->tracker(false, true)->track(Activity::TYPE_CART_ADD, ['product_id' => 1]);
        $this->assertNull($this->saved);
    }

    public function testNothingSavedWhenRealDataOff(): void
    {
        $this->tracker(true, false)->track(Activity::TYPE_CART_ADD, ['product_id' => 1]);
        $this->assertNull($this->saved);
    }

    public function testLoggedInCustomerNameIsShortenedThroughConfig(): void
    {
        $customer = new DataObject(['firstname' => 'Jane', 'lastname' => 'Doe']);
        $this->tracker(true, true, $customer)->track(
            Activity::TYPE_PURCHASE,
            ['product_id' => 15, 'product_name' => 'Lamp']
        );

        $this->assertNotNull($this->saved);
        $this->assertSame([
            'activity_type' => 'purchase',
            'product_id' => 15,
            'product_name' => 'Lamp',
            'customer_name' => 'Jane|Doe',
            'customer_location' => null,
            'store_id' => 4,
            'is_real' => 1,
        ], $this->saved->getData());
    }

    public function testGuestGetsAnonymousNameWithInitial(): void
    {
        $this->tracker(true, true)->track(Activity::TYPE_WISHLIST_ADD, []);

        $this->assertNotNull($this->saved);
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z]\.$/', $this->saved->getData('customer_name'));
        $this->assertNull($this->saved->getData('product_id'));
        $this->assertNull($this->saved->getData('product_name'));
        $this->assertSame('wishlist_add', $this->saved->getData('activity_type'));
    }

    public function testSaveFailureIsLoggedNotThrown(): void
    {
        $this->tracker(true, true, null, new \RuntimeException('deadlock'))
            ->track(Activity::TYPE_CART_ADD, ['product_id' => 3]);

        $this->assertNull($this->saved);
        $this->assertSame(['Live Activity tracking error: deadlock'], $this->logged);
    }
}
