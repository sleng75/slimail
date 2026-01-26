import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
    });

    test('should display login page', async ({ page }) => {
        await page.goto('/login');

        await expect(page.locator('h1, h2').first()).toContainText(/connexion|login/i);
        await expect(page.locator('input[type="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('should show validation errors for empty form', async ({ page }) => {
        await page.goto('/login');

        await page.click('button[type="submit"]');

        await expect(page.locator('.text-red-500, .text-red-600, [class*="error"]').first()).toBeVisible();
    });

    test('should show error for invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[type="email"]', 'invalid@example.com');
        await page.fill('input[type="password"]', 'wrongpassword');
        await page.click('button[type="submit"]');

        // Wait for error message
        await expect(page.locator('.text-red-500, .text-red-600, [class*="error"], [role="alert"]').first()).toBeVisible({ timeout: 10000 });
    });

    test('should navigate to register page', async ({ page }) => {
        await page.goto('/login');

        const registerLink = page.locator('a[href*="register"]');
        if (await registerLink.isVisible()) {
            await registerLink.click();
            await expect(page).toHaveURL(/register/);
        }
    });

    test('should navigate to forgot password page', async ({ page }) => {
        await page.goto('/login');

        const forgotLink = page.locator('a[href*="forgot"], a[href*="password"]').first();
        if (await forgotLink.isVisible()) {
            await forgotLink.click();
            await expect(page).toHaveURL(/forgot|password/);
        }
    });

    test('should display registration form', async ({ page }) => {
        await page.goto('/register');

        await expect(page.locator('input[name="name"], input[id="name"]')).toBeVisible();
        await expect(page.locator('input[type="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"]').first()).toBeVisible();
    });

    test('should validate registration form', async ({ page }) => {
        await page.goto('/register');

        await page.click('button[type="submit"]');

        // Should show validation errors
        await page.waitForTimeout(500);
        const errors = page.locator('.text-red-500, .text-red-600, [class*="error"]');
        await expect(errors.first()).toBeVisible();
    });

    test('should successfully login with valid credentials', async ({ page }) => {
        // This test requires a seeded test user
        await page.goto('/login');

        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        // Should redirect to dashboard
        await page.waitForURL(/dashboard|home/, { timeout: 15000 });
        await expect(page).toHaveURL(/dashboard|home/);
    });

    test('should logout successfully', async ({ page }) => {
        // First login
        await page.goto('/login');
        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        await page.waitForURL(/dashboard|home/, { timeout: 15000 });

        // Find and click logout
        const userMenu = page.locator('[data-testid="user-menu"], .user-menu, button:has-text("Déconnexion"), [aria-label*="user"], [aria-label*="menu"]').first();
        if (await userMenu.isVisible()) {
            await userMenu.click();
        }

        const logoutButton = page.locator('a[href*="logout"], button:has-text("Déconnexion"), button:has-text("Logout"), form[action*="logout"] button');
        await logoutButton.first().click();

        // Should redirect to login
        await expect(page).toHaveURL(/login|\/$/);
    });

    test('should remember user session', async ({ page, context }) => {
        await page.goto('/login');

        const rememberMe = page.locator('input[type="checkbox"][name*="remember"], input[type="checkbox"]#remember');
        if (await rememberMe.isVisible()) {
            await rememberMe.check();
        }

        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');

        await page.waitForURL(/dashboard|home/, { timeout: 15000 });

        // Close and reopen browser - session should persist
        const cookies = await context.cookies();
        expect(cookies.some(c => c.name.includes('session') || c.name.includes('remember'))).toBeTruthy();
    });
});

test.describe('Protected Routes', () => {
    test('should redirect unauthenticated users to login', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page).toHaveURL(/login/);
    });

    test('should redirect unauthenticated users from contacts', async ({ page }) => {
        await page.goto('/contacts');

        await expect(page).toHaveURL(/login/);
    });

    test('should redirect unauthenticated users from campaigns', async ({ page }) => {
        await page.goto('/campaigns');

        await expect(page).toHaveURL(/login/);
    });

    test('should redirect unauthenticated users from settings', async ({ page }) => {
        await page.goto('/settings');

        await expect(page).toHaveURL(/login/);
    });
});
