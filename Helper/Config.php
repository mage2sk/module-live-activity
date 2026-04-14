<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLED = 'live_activity/general/enabled';
    const XML_PATH_POSITION = 'live_activity/general/position';
    const XML_PATH_DISPLAY_DELAY = 'live_activity/general/display_delay';
    const XML_PATH_DURATION = 'live_activity/general/notification_duration';
    const XML_PATH_INTERVAL = 'live_activity/general/interval';
    const XML_PATH_MAX_NOTIFICATIONS = 'live_activity/general/max_notifications';

    const XML_PATH_SHOW_PURCHASES = 'live_activity/activity_types/show_purchases';
    const XML_PATH_SHOW_CART_ADDS = 'live_activity/activity_types/show_cart_adds';
    const XML_PATH_SHOW_WISHLIST = 'live_activity/activity_types/show_wishlist_adds';
    const XML_PATH_SHOW_VIEWERS = 'live_activity/activity_types/show_live_viewers';
    const XML_PATH_SHOW_TRENDING = 'live_activity/activity_types/show_trending';
    const XML_PATH_SHOW_LOW_STOCK = 'live_activity/activity_types/show_low_stock';

    const XML_PATH_USE_REAL_DATA = 'live_activity/data_source/use_real_data';
    const XML_PATH_USE_SIMULATED = 'live_activity/data_source/use_simulated_data';
    const XML_PATH_TIME_RANGE = 'live_activity/data_source/time_range';
    const XML_PATH_ANONYMIZE = 'live_activity/data_source/anonymize_names';
    const XML_PATH_FEATURED_PRODUCTS = 'live_activity/data_source/featured_products';
    const XML_PATH_FAKE_NAMES = 'live_activity/data_source/fake_names';
    const XML_PATH_FAKE_LOCATIONS = 'live_activity/data_source/fake_locations';

    const XML_PATH_ANIMATION = 'live_activity/appearance/animation_style';
    const XML_PATH_SHOW_IMAGE = 'live_activity/appearance/show_product_image';
    const XML_PATH_SHOW_ICON = 'live_activity/appearance/show_icon';
    const XML_PATH_CUSTOM_CSS = 'live_activity/appearance/custom_css';

    const XML_PATH_EXCLUDE_CATEGORIES = 'live_activity/advanced/exclude_categories';
    const XML_PATH_MOBILE_ENABLED = 'live_activity/advanced/mobile_enabled';

    /**
     * Check if module is enabled
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get featured product IDs
     */
    public function getFeaturedProductIds(): array
    {
        $ids = $this->getConfig(self::XML_PATH_FEATURED_PRODUCTS);
        if (empty($ids)) {
            return [];
        }

        return array_filter(array_map('intval', explode(',', $ids)));
    }

    /**
     * Get custom CSS from configuration
     */
    public function getCustomCss(): string
    {
        return (string)$this->getConfig(self::XML_PATH_CUSTOM_CSS);
    }

    /**
     * Get excluded category IDs as array
     *
     * @return array
     */
    public function getExcludedCategoryIds(): array
    {
        $categories = $this->getConfig(self::XML_PATH_EXCLUDE_CATEGORIES);
        if (empty($categories)) {
            return [];
        }

        // Config stores multiselect as comma-separated string
        return array_filter(array_map('intval', explode(',', $categories)));
    }

    /**
     * Get enabled fake names from configuration
     *
     * @return array Array of fake name strings
     */
    public function getEnabledFakeNames(): array
    {
        $namesJson = $this->getConfig(self::XML_PATH_FAKE_NAMES);

        if (empty($namesJson)) {
            // Return default names if nothing configured
            return $this->getDefaultFakeNames();
        }

        try {
            $namesData = json_decode($namesJson, true);

            // Handle both formats: simple string array or legacy object array
            if (!is_array($namesData)) {
                return $this->getDefaultFakeNames();
            }

            // Check if it's a simple string array (new format)
            if (isset($namesData[0]) && is_string($namesData[0])) {
                // New format: simple array of strings ["John D.", "Sarah M.", ...]
                return array_values(array_filter($namesData));
            }

            // Legacy format: array of objects [{"name": "John", "enabled": true}, ...]
            if (isset($namesData[0]) && is_array($namesData[0]) && isset($namesData[0]['name'])) {
                // Filter only enabled names and extract just the name strings
                $enabledNames = array_filter($namesData, function($nameObj) {
                    return isset($nameObj['enabled']) && $nameObj['enabled'] === true;
                });

                $names = array_map(function($nameObj) {
                    return $nameObj['name'];
                }, $enabledNames);

                // If no names are enabled, return defaults
                if (empty($names)) {
                    return $this->getDefaultFakeNames();
                }

                return array_values($names);
            }

            return $this->getDefaultFakeNames();
        } catch (\Exception $e) {
            // On any error, return defaults
            return $this->getDefaultFakeNames();
        }
    }

    /**
     * Get default fake names (fallback)
     * Returns names in "FirstName L." format
     *
     * @return array
     */
    private function getDefaultFakeNames(): array
    {
        return [
            'James D.', 'John M.', 'Robert K.', 'Michael S.', 'William T.',
            'David R.', 'Sarah J.', 'Emily W.', 'Emma A.', 'Olivia E.',
            'Alex K.', 'Jordan M.', 'Taylor S.', 'Morgan R.', 'Riley B.'
        ];
    }

    /**
     * Get fake locations from configuration
     *
     * @return array Array of city name strings
     */
    public function getFakeLocations(): array
    {
        $value = $this->getConfig(self::XML_PATH_FAKE_LOCATIONS);
        if ($value) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }
        return [
            'New York', 'London', 'Dubai', 'Singapore', 'Sydney',
            'Toronto', 'Paris', 'Tokyo', 'Berlin', 'Amsterdam',
            'Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Pune'
        ];
    }

    /**
     * Get all configuration as array for frontend
     */
    public function getFrontendConfig(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'position' => $this->getConfig(self::XML_PATH_POSITION),
            'displayDelay' => (int)$this->getConfig(self::XML_PATH_DISPLAY_DELAY) * 1000, // Convert to ms
            'duration' => (int)$this->getConfig(self::XML_PATH_DURATION) * 1000, // Convert to ms
            'interval' => (int)$this->getConfig(self::XML_PATH_INTERVAL) * 1000, // Convert to ms
            'maxNotifications' => (int)$this->getConfig(self::XML_PATH_MAX_NOTIFICATIONS),
            'animation' => $this->getConfig(self::XML_PATH_ANIMATION),
            'showImage' => (bool)$this->getConfig(self::XML_PATH_SHOW_IMAGE),
            'showIcon' => (bool)$this->getConfig(self::XML_PATH_SHOW_ICON),
            'mobileEnabled' => (bool)$this->getConfig(self::XML_PATH_MOBILE_ENABLED),
        ];
    }
}
