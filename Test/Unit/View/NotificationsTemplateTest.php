<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\View;

use PHPUnit\Framework\TestCase;

class NotificationsTemplateTest extends TestCase
{
    private string $template = '';

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 3) . '/view/frontend/templates/notifications.phtml';
        $this->assertTrue(is_file($path));
        $this->template = (string)file_get_contents($path);
    }

    public function testPoliteStatusRegionAndNoAssertiveAnnouncements(): void
    {
        $this->assertStringContainsString(
            '<div class="live-activity-sr" role="status" aria-live="polite" aria-atomic="true"></div>',
            $this->template
        );
        $this->assertStringNotContainsString('assertive', $this->template);
        $this->assertStringContainsString('this.announce(notifDiv);', $this->template);
    }

    public function testReducedMotionIsHonouredAtEveryWidth(): void
    {
        $this->assertMatchesRegularExpression(
            '/^@media \(prefers-reduced-motion: reduce\) \{\s+\.live-activity-container \.live-activity-notification,/m',
            $this->template
        );
    }

    public function testAnimationSelectorsTargetTheNotificationElementItself(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/\.live-activity-animation-[a-z]+(\.live-activity-[a-z-]+)? \.notification-enter/',
            $this->template
        );
        foreach (['slide', 'fade', 'bounce', 'scale'] as $style) {
            $this->assertStringContainsString(
                '.live-activity-animation-' . $style . '.notification-enter-start',
                $this->template
            );
        }
        $this->assertStringContainsString("' notification-enter notification-enter-start'", $this->template);
    }

    public function testDismissIsRememberedForTheSessionAndStopsTheLoop(): void
    {
        $this->assertStringContainsString("storageKey = 'panthLiveActivityDismissed'", $this->template);
        $this->assertStringContainsString('window.sessionStorage.setItem(this.storageKey', $this->template);
        $this->assertStringContainsString('if (this.isDismissed()) {', $this->template);
        $this->assertStringContainsString('if (this.isShowingNotification || this.stopped) {', $this->template);
        $this->assertStringContainsString('self.dismiss();', $this->template);
    }

    public function testProductIsAKeyboardReachableLinkAndCloseIsAButton(): void
    {
        $this->assertStringContainsString("productSpan = document.createElement('a');", $this->template);
        $this->assertStringContainsString('productSpan.href = notification.product_url;', $this->template);
        $this->assertStringContainsString("button.type = 'button';", $this->template);
        $this->assertStringContainsString("button.setAttribute('aria-label', 'Close notification');", $this->template);
        $this->assertStringNotContainsString('_hasTouch) {', $this->template);
    }

    public function testCloseButtonHasA44PixelHitArea(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.live-activity-container \.live-activity-close::before \{\s+content: "";\s+position: absolute;\s+'
            . 'top: -8px;\s+right: -8px;\s+bottom: -8px;\s+left: -8px;/',
            $this->template
        );
        $this->assertStringContainsString('width: 28px;', $this->template);
    }

    public function testTopPositionsAreOffsetBelowTheHeader(): void
    {
        $this->assertStringContainsString("indexOf('top') === 0", $this->template);
        $this->assertStringContainsString("document.querySelector('.page-header, header')", $this->template);
        $this->assertStringContainsString("notifDiv.style.top = Math.round(bottom + 12) + 'px';", $this->template);
    }

    public function testCloseIconColourMeetsNonTextContrast(): void
    {
        $this->assertStringNotContainsString('var(--live-activity-close, #999)', $this->template);
        $this->assertStringContainsString('color: var(--live-activity-close, #666);', $this->template);
        $this->assertStringContainsString('color: var(--live-activity-close, #64748b);', $this->template);
    }

    public function testCompactToastAvoidsPrimaryActions(): void
    {
        foreach ([
            "'#product-addtocart-button'",
            "'#product_addtocart_form input[name=\"qty\"]'",
            "'#product_addtocart_form .swatch-opt'",
            "'[data-sticky-add-to-cart]'",
            "'.checkout-methods-items'",
        ] as $selector) {
            $this->assertStringContainsString($selector, $this->template);
        }
        $this->assertStringContainsString('if (this.overlapsPrimaryAction(notifDiv)) {', $this->template);
        $this->assertStringContainsString("notifDiv.classList.add('live-activity-measuring');", $this->template);
        $this->assertStringContainsString("notifDiv.classList.remove('live-activity-measuring');", $this->template);
        $this->assertStringContainsString('this.avoidPrimaryActions(notification);', $this->template);
        $this->assertStringContainsString("window.addEventListener('scroll', onViewportChange, { passive: true });", $this->template);
        $this->assertStringContainsString(
            'if (notification.done || notification.timer || notification.suppressed) {',
            $this->template
        );
        $this->assertMatchesRegularExpression(
            '/\.live-activity-notification\.live-activity-suppressed \{\s+visibility: hidden;\s+pointer-events: none;/',
            $this->template
        );
    }

    public function testHiddenOnCheckoutAndBehindOverlays(): void
    {
        $this->assertStringContainsString('body.checkout-index-index .live-activity-container', $this->template);
        $this->assertStringContainsString('html:has(.minicart-wrapper.active) .live-activity-container', $this->template);
    }

    public function testDynamicTextIsInsertedAsTextNotHtml(): void
    {
        $this->assertStringNotContainsString('innerHTML', $this->template);
        $this->assertStringContainsString('strong.textContent = notification.customer_name;', $this->template);
    }
}
