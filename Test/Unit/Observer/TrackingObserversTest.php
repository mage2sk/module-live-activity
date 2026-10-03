<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Panth\LiveActivity\Model\Activity;
use Panth\LiveActivity\Model\ActivityTracker;
use Panth\LiveActivity\Observer\TrackCartAdd;
use Panth\LiveActivity\Observer\TrackOrderPlacement;
use Panth\LiveActivity\Observer\TrackWishlistAdd;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrackingObserversTest extends TestCase
{
    private array $tracked = [];

    private function tracker(): ActivityTracker
    {
        $this->tracked = [];
        $tracker = $this->createStub(ActivityTracker::class);
        $tracker->method('track')->willReturnCallback(function (string $type, array $data) {
            $this->tracked[] = [$type, $data];
        });
        return $tracker;
    }

    private static function observer(array $eventData): Observer
    {
        return new Observer(['event' => new Event($eventData)]);
    }

    private function product(int $id, string $name): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getName')->willReturn($name);
        return $product;
    }

    public static function productObservers(): array
    {
        return [
            'cart' => [TrackCartAdd::class, Activity::TYPE_CART_ADD],
            'wishlist' => [TrackWishlistAdd::class, Activity::TYPE_WISHLIST_ADD],
        ];
    }

    #[DataProvider('productObservers')]
    public function testProductObserverTracksProduct(string $class, string $type): void
    {
        $observer = new $class($this->tracker());
        $observer->execute(self::observer(['product' => $this->product(42, 'Bag')]));

        $this->assertSame([[$type, ['product_id' => 42, 'product_name' => 'Bag']]], $this->tracked);
    }

    #[DataProvider('productObservers')]
    public function testProductObserverIgnoresMissingProduct(string $class, string $type): void
    {
        $observer = new $class($this->tracker());
        $observer->execute(self::observer([]));

        $this->assertNotContains($type, array_column($this->tracked, 0));
        $this->assertSame([], $this->tracked);
    }

    public function testOrderObserverTracksEveryVisibleItem(): void
    {
        $items = [];
        foreach ([[7, 'Shirt'], [9, 'Hat']] as [$id, $name]) {
            $item = $this->createStub(Item::class);
            $item->method('getProductId')->willReturn($id);
            $item->method('getName')->willReturn($name);
            $items[] = $item;
        }
        $order = $this->createStub(Order::class);
        $order->method('getAllVisibleItems')->willReturn($items);

        (new TrackOrderPlacement($this->tracker()))->execute(self::observer(['order' => $order]));

        $this->assertSame([
            [Activity::TYPE_PURCHASE, ['product_id' => 7, 'product_name' => 'Shirt']],
            [Activity::TYPE_PURCHASE, ['product_id' => 9, 'product_name' => 'Hat']],
        ], $this->tracked);
    }

    public function testOrderObserverIgnoresMissingOrderAndEmptyOrders(): void
    {
        $observer = new TrackOrderPlacement($this->tracker());
        $observer->execute(self::observer([]));

        $order = $this->createStub(Order::class);
        $order->method('getAllVisibleItems')->willReturn([]);
        $observer->execute(self::observer(['order' => $order]));

        $this->assertSame([], $this->tracked);
    }
}
