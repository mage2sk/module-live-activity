<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Panth\LiveActivity\Helper\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private function config(array $values): Config
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn(string $path) => $values[$path] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn(string $path) => !empty($values[$path])
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        return new Config($context);
    }

    public function testIsEnabledFollowsTheFlag(): void
    {
        $this->assertTrue($this->config([Config::XML_PATH_ENABLED => '1'])->isEnabled());
        $this->assertFalse($this->config([])->isEnabled());
    }

    public function testGetConfigReturnsRawValue(): void
    {
        $config = $this->config([Config::XML_PATH_POSITION => 'top-left']);
        $this->assertSame('top-left', $config->getConfig(Config::XML_PATH_POSITION));
        $this->assertNull($config->getConfig(Config::XML_PATH_ANIMATION));
    }

    public function testFeaturedProductIdsAreParsedAndZeroesDropped(): void
    {
        $config = $this->config([Config::XML_PATH_FEATURED_PRODUCTS => '5, 12,abc,,7']);
        $this->assertSame([0 => 5, 1 => 12, 4 => 7], $config->getFeaturedProductIds());
    }

    public function testFeaturedProductIdsEmptyWhenUnset(): void
    {
        $this->assertSame([], $this->config([])->getFeaturedProductIds());
    }

    public function testExcludedCategoryIdsAreParsed(): void
    {
        $config = $this->config([Config::XML_PATH_EXCLUDE_CATEGORIES => '3,0,9']);
        $this->assertSame([0 => 3, 2 => 9], $config->getExcludedCategoryIds());
        $this->assertSame([], $this->config([])->getExcludedCategoryIds());
    }

    public function testCustomCssIsAlwaysAString(): void
    {
        $this->assertSame('', $this->config([])->getCustomCss());
        $this->assertSame('.a{}', $this->config([Config::XML_PATH_CUSTOM_CSS => '.a{}'])->getCustomCss());
    }

    public static function nameProvider(): array
    {
        return [
            'first and last' => ['John', 'Smith', 'John S.'],
            'first only' => ['John', '', 'John'],
            'trimmed' => ['  Jane ', ' doe ', 'Jane D.'],
            'both empty' => ['', '', ''],
            'whitespace only' => ['  ', ' ', ''],
            'last only' => ['', 'smith', 'S.'],
            'email first name' => ['john@example.com', 'Smith', 'J.'],
            'email last name ignored' => ['John', 'x@y.z', 'John'],
            'lowercase last initial' => ['Anna', 'oeland', 'Anna O.'],
            'long first name truncated' => [str_repeat('a', 40), '', str_repeat('a', 30)],
        ];
    }

    #[DataProvider('nameProvider')]
    public function testShortenCustomerName(string $first, string $last, string $expected): void
    {
        $this->assertSame($expected, $this->config([])->shortenCustomerName($first, $last));
    }

    public function testAnonymizeStoredNameUsesFirstAndLastWord(): void
    {
        $config = $this->config([]);
        $this->assertSame('Mary W.', $config->anonymizeStoredName('Mary Ann  Watson'));
        $this->assertSame('Mary', $config->anonymizeStoredName('Mary'));
        $this->assertSame('', $config->anonymizeStoredName(null));
        $this->assertSame('', $config->anonymizeStoredName('   '));
        $this->assertSame('B.', $config->anonymizeStoredName('bob@example.com'));
    }

    public function testFakeNamesDefaultWhenUnsetOrInvalid(): void
    {
        $defaults = $this->config([])->getEnabledFakeNames();
        $this->assertCount(15, $defaults);
        $this->assertSame('James D.', $defaults[0]);
        $this->assertSame(
            $defaults,
            $this->config([Config::XML_PATH_FAKE_NAMES => 'not json'])->getEnabledFakeNames()
        );
        $this->assertSame(
            $defaults,
            $this->config([Config::XML_PATH_FAKE_NAMES => '{"a":1}'])->getEnabledFakeNames()
        );
    }

    public function testFakeNamesAsPlainStringListDropEmptyEntries(): void
    {
        $config = $this->config([Config::XML_PATH_FAKE_NAMES => '["Ann B.","","Carl D."]']);
        $this->assertSame(['Ann B.', 'Carl D.'], $config->getEnabledFakeNames());
    }

    public function testFakeNamesAsObjectsKeepOnlyEnabled(): void
    {
        $json = json_encode([
            ['name' => 'On One', 'enabled' => true],
            ['name' => 'Off', 'enabled' => false],
            ['name' => 'No Flag'],
            ['name' => 'On Two', 'enabled' => true],
        ]);
        $this->assertSame(
            ['On One', 'On Two'],
            $this->config([Config::XML_PATH_FAKE_NAMES => $json])->getEnabledFakeNames()
        );
    }

    public function testFakeNamesAsObjectsAcceptNumericAndStringFlags(): void
    {
        $json = json_encode([
            ['name' => 'Int On', 'enabled' => 1],
            ['name' => 'Str On', 'enabled' => '1'],
            ['name' => 'Int Off', 'enabled' => 0],
            ['name' => 'Str Off', 'enabled' => '0'],
        ]);
        $this->assertSame(
            ['Int On', 'Str On'],
            $this->config([Config::XML_PATH_FAKE_NAMES => $json])->getEnabledFakeNames()
        );
    }

    public function testFakeNamesAsObjectsAllDisabledFallBackToDefaults(): void
    {
        $json = json_encode([['name' => 'Off', 'enabled' => false]]);
        $names = $this->config([Config::XML_PATH_FAKE_NAMES => $json])->getEnabledFakeNames();
        $this->assertCount(15, $names);
        $this->assertNotContains('Off', $names);
    }

    public function testFakeLocationsUseConfiguredListOrDefaults(): void
    {
        $this->assertSame(
            ['Rome', 'Oslo'],
            $this->config([Config::XML_PATH_FAKE_LOCATIONS => '["Rome","Oslo"]'])->getFakeLocations()
        );
        $defaults = $this->config([])->getFakeLocations();
        $this->assertCount(15, $defaults);
        $this->assertSame('New York', $defaults[0]);
        $this->assertSame($defaults, $this->config([Config::XML_PATH_FAKE_LOCATIONS => '[]'])->getFakeLocations());
        $this->assertSame(
            $defaults,
            $this->config([Config::XML_PATH_FAKE_LOCATIONS => 'broken'])->getFakeLocations()
        );
    }

    public function testFrontendConfigConvertsSecondsAndFlags(): void
    {
        $config = $this->config([
            Config::XML_PATH_ENABLED => '1',
            Config::XML_PATH_POSITION => 'bottom-right',
            Config::XML_PATH_DISPLAY_DELAY => '3',
            Config::XML_PATH_DURATION => '5',
            Config::XML_PATH_INTERVAL => '10',
            Config::XML_PATH_MAX_NOTIFICATIONS => '4',
            Config::XML_PATH_ANIMATION => 'fade',
            Config::XML_PATH_SHOW_IMAGE => '1',
            Config::XML_PATH_SHOW_ICON => '0',
        ]);

        $this->assertSame([
            'enabled' => true,
            'position' => 'bottom-right',
            'displayDelay' => 3000,
            'duration' => 5000,
            'interval' => 10000,
            'maxNotifications' => 4,
            'animation' => 'fade',
            'showImage' => true,
            'showIcon' => false,
            'mobileEnabled' => false,
        ], $config->getFrontendConfig());
    }

    public function testFrontendConfigDefaultsToZeroes(): void
    {
        $data = $this->config([])->getFrontendConfig();
        $this->assertFalse($data['enabled']);
        $this->assertSame(0, $data['displayDelay']);
        $this->assertSame(0, $data['maxNotifications']);
        $this->assertNull($data['position']);
    }
}
