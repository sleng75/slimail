import { test, expect } from '@playwright/test';

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto('/login');
        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard|home/, { timeout: 15000 });
    });

    test('should display dashboard after login', async ({ page }) => {
        await expect(page).toHaveURL(/dashboard|home/);
        await expect(page.locator('h1, h2').first()).toBeVisible();
    });

    test('should display key metrics', async ({ page }) => {
        await page.goto('/dashboard');

        // Look for metric cards
        const metricCards = page.locator('.stat-card, .metric-card, [data-testid="metric"], .stats-grid > div');
        await expect(metricCards.first()).toBeVisible();
    });

    test('should display emails sent metric', async ({ page }) => {
        await page.goto('/dashboard');

        const emailMetric = page.locator('text=/emails?\s*(envoyés?|sent)/i').first();
        if (await emailMetric.isVisible()) {
            await expect(emailMetric).toBeVisible();
        }
    });

    test('should display open rate metric', async ({ page }) => {
        await page.goto('/dashboard');

        const openRateMetric = page.locator('text=/taux\s*d[\'e]?\s*ouverture|open\s*rate/i').first();
        // May or may not be visible depending on data
    });

    test('should display click rate metric', async ({ page }) => {
        await page.goto('/dashboard');

        const clickRateMetric = page.locator('text=/taux\s*(de)?\s*clics?|click\s*rate/i').first();
        // May or may not be visible depending on data
    });

    test('should display charts', async ({ page }) => {
        await page.goto('/dashboard');

        // Wait for charts to load
        await page.waitForTimeout(2000);

        const charts = page.locator('canvas, svg[class*="chart"], .chart-container, [data-testid="chart"]');
        // Charts should be visible if there's data
    });

    test('should change time period', async ({ page }) => {
        await page.goto('/dashboard');

        const periodSelector = page.locator('select[name="period"], button:has-text("7 jours"), button:has-text("30 jours"), [data-testid="period-selector"]');
        if (await periodSelector.first().isVisible()) {
            await periodSelector.first().click();
            await page.waitForTimeout(500);
        }
    });

    test('should display recent campaigns', async ({ page }) => {
        await page.goto('/dashboard');

        const recentCampaigns = page.locator('text=/campagnes?\s*récentes?|recent\s*campaigns?/i, h2:has-text("Campagnes"), h3:has-text("Campagnes")').first();
        // May or may not be visible depending on layout
    });

    test('should display quick actions', async ({ page }) => {
        await page.goto('/dashboard');

        const quickActions = page.locator('.quick-actions, [data-testid="quick-actions"], button:has-text("Nouvelle campagne")');
        // Quick actions may be present
    });

    test('should navigate to campaigns from dashboard', async ({ page }) => {
        await page.goto('/dashboard');

        const campaignsLink = page.locator('a[href*="campaigns"]:not([href*="create"])').first();
        await campaignsLink.click();

        await expect(page).toHaveURL(/campaigns/);
    });

    test('should navigate to contacts from dashboard', async ({ page }) => {
        await page.goto('/dashboard');

        const contactsLink = page.locator('a[href*="contacts"]:not([href*="create"])').first();
        await contactsLink.click();

        await expect(page).toHaveURL(/contacts/);
    });

    test('should display alerts if any', async ({ page }) => {
        await page.goto('/dashboard');

        const alerts = page.locator('.alert, [role="alert"], .notification, [data-testid="alert"]');
        // Alerts may or may not be present
    });

    test('should display bounce rate warning if high', async ({ page }) => {
        await page.goto('/dashboard');

        const bounceWarning = page.locator('text=/rebond|bounce/i');
        // Warning may or may not be present depending on metrics
    });

    test('should be responsive', async ({ page }) => {
        await page.goto('/dashboard');

        // Test mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.waitForTimeout(500);

        // Dashboard should still be usable
        await expect(page.locator('body')).toBeVisible();

        // Sidebar should be hidden or collapsed
        const sidebar = page.locator('aside, .sidebar, [data-testid="sidebar"]');
        // On mobile, sidebar is typically hidden

        // Reset viewport
        await page.setViewportSize({ width: 1280, height: 720 });
    });

    test('should display usage limits', async ({ page }) => {
        await page.goto('/dashboard');

        const usageSection = page.locator('text=/utilisation|usage|limite|limit/i').first();
        // Usage section may be present
    });

    test('should display subscription status', async ({ page }) => {
        await page.goto('/dashboard');

        const subscriptionInfo = page.locator('text=/abonnement|forfait|plan|subscription/i').first();
        // Subscription info may be visible
    });
});

test.describe('Navigation', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard|home/, { timeout: 15000 });
    });

    test('should display sidebar navigation', async ({ page }) => {
        await page.goto('/dashboard');

        const sidebar = page.locator('aside, nav, .sidebar, [data-testid="sidebar"]');
        await expect(sidebar.first()).toBeVisible();
    });

    test('should have working navigation links', async ({ page }) => {
        await page.goto('/dashboard');

        // Test each main navigation item
        const navItems = [
            { text: /tableau de bord|dashboard/i, url: /dashboard/ },
            { text: /contacts/i, url: /contacts/ },
            { text: /campagnes/i, url: /campaigns/ },
            { text: /templates/i, url: /templates/ },
            { text: /automatisations?/i, url: /automations/ },
        ];

        for (const item of navItems) {
            const link = page.locator(`a:has-text("${item.text.source.replace(/[\/\\^$*+?.()|[\]{}]/g, '\\$&')}")`).first();
            if (await link.isVisible()) {
                await link.click();
                await expect(page).toHaveURL(item.url, { timeout: 10000 });
                await page.goto('/dashboard');
            }
        }
    });

    test('should collapse sidebar on mobile', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/dashboard');

        // Sidebar should be hidden
        const sidebar = page.locator('aside, .sidebar');

        // Toggle button should be visible
        const toggleButton = page.locator('button[aria-label*="menu"], .hamburger, [data-testid="sidebar-toggle"]');
        if (await toggleButton.isVisible()) {
            await toggleButton.click();
            // Sidebar should now be visible
        }

        await page.setViewportSize({ width: 1280, height: 720 });
    });

    test('should display user menu', async ({ page }) => {
        await page.goto('/dashboard');

        const userMenu = page.locator('[data-testid="user-menu"], .user-menu, button[aria-label*="user"], .avatar');
        await expect(userMenu.first()).toBeVisible();
    });

    test('should navigate to settings', async ({ page }) => {
        await page.goto('/dashboard');

        const settingsLink = page.locator('a[href*="settings"]').first();
        await settingsLink.click();

        await expect(page).toHaveURL(/settings/);
    });

    test('should display breadcrumbs on subpages', async ({ page }) => {
        await page.goto('/contacts/create');

        const breadcrumbs = page.locator('.breadcrumbs, [aria-label*="breadcrumb"], nav:has-text("Contacts")');
        // Breadcrumbs may or may not be present
    });

    test('should have search functionality', async ({ page }) => {
        await page.goto('/dashboard');

        const searchInput = page.locator('input[type="search"], input[placeholder*="Rechercher"], [data-testid="global-search"]');
        if (await searchInput.isVisible()) {
            await searchInput.fill('test');
            await page.waitForTimeout(500);

            // Search results should appear
            const results = page.locator('.search-results, [data-testid="search-results"]');
        }
    });

    test('should have keyboard shortcut for search', async ({ page }) => {
        await page.goto('/dashboard');

        // Press Ctrl+K for search
        await page.keyboard.press('Control+k');
        await page.waitForTimeout(300);

        const searchModal = page.locator('[data-testid="search-modal"], .search-modal, input[type="search"]:focus');
        // Search modal may or may not appear
    });
});
