<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Panth\LiveActivity\Helper\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private function helper(ScopeConfigInterface $scopeConfig): Data
    {
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        return new Data($context);
    }

    public function testIsEnabledReadsStoreScopedValue(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())->method('getValue')
            ->with('live_activity/general/enabled', ScopeInterface::SCOPE_STORE, 3)
            ->willReturn('1');
        $this->assertTrue($this->helper($scopeConfig)->isEnabled(3));
    }

    public function testIsEnabledFalseWhenUnset(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(null);
        $this->assertFalse($this->helper($scopeConfig)->isEnabled());
    }
}
