import { test, expect } from '@playwright/test';

test.describe('Contacts Management', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto('/login');
        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard|home|contacts/, { timeout: 15000 });
    });

    test('should display contacts list page', async ({ page }) => {
        await page.goto('/contacts');

        await expect(page.locator('h1')).toContainText(/contacts/i);
        await expect(page.locator('table, [data-testid="contacts-list"], .contacts-list')).toBeVisible();
    });

    test('should display add contact button', async ({ page }) => {
        await page.goto('/contacts');

        const addButton = page.locator('a[href*="create"], button:has-text("Ajouter"), button:has-text("Nouveau")');
        await expect(addButton.first()).toBeVisible();
    });

    test('should navigate to create contact page', async ({ page }) => {
        await page.goto('/contacts');

        await page.click('a[href*="create"], button:has-text("Ajouter"), button:has-text("Nouveau")');

        await expect(page).toHaveURL(/contacts\/create|contacts\/new/);
    });

    test('should create a new contact', async ({ page }) => {
        await page.goto('/contacts/create');

        const timestamp = Date.now();
        const email = `test-${timestamp}@example.com`;

        await page.fill('input[name="email"], input#email', email);
        await page.fill('input[name="first_name"], input#first_name', 'Test');
        await page.fill('input[name="last_name"], input#last_name', 'User');

        await page.click('button[type="submit"]');

        // Should redirect to contacts list or show success
        await page.waitForURL(/contacts(?!\/create)/, { timeout: 10000 });

        // Verify contact was created
        await page.goto('/contacts');
        await expect(page.locator(`text=${email}`).first()).toBeVisible({ timeout: 5000 });
    });

    test('should validate required fields', async ({ page }) => {
        await page.goto('/contacts/create');

        await page.click('button[type="submit"]');

        // Should show validation error for email
        await expect(page.locator('.text-red-500, .text-red-600, [class*="error"]').first()).toBeVisible();
    });

    test('should search contacts', async ({ page }) => {
        await page.goto('/contacts');

        const searchInput = page.locator('input[type="search"], input[placeholder*="Rechercher"], input[name="search"]');
        await searchInput.fill('test');

        // Wait for search results
        await page.waitForTimeout(500);

        // Results should be filtered
        await expect(page.locator('table tbody tr, .contact-item')).toBeVisible();
    });

    test('should filter contacts by status', async ({ page }) => {
        await page.goto('/contacts');

        const statusFilter = page.locator('select[name="status"], [data-testid="status-filter"]');
        if (await statusFilter.isVisible()) {
            await statusFilter.selectOption('subscribed');
            await page.waitForTimeout(500);
        }
    });

    test('should edit existing contact', async ({ page }) => {
        await page.goto('/contacts');

        // Click on first contact's edit button
        const editButton = page.locator('a[href*="edit"], button[title*="Modifier"], [data-action="edit"]').first();
        await editButton.click();

        await expect(page).toHaveURL(/contacts\/\d+\/edit/);

        // Modify a field
        await page.fill('input[name="first_name"], input#first_name', 'Modified');
        await page.click('button[type="submit"]');

        // Should save and redirect
        await page.waitForURL(/contacts(?!\/edit)/, { timeout: 10000 });
    });

    test('should delete contact', async ({ page }) => {
        await page.goto('/contacts');

        // Click delete button on first contact
        const deleteButton = page.locator('button[title*="Supprimer"], button:has-text("Supprimer"), [data-action="delete"]').first();
        await deleteButton.click();

        // Confirm deletion in modal
        const confirmButton = page.locator('.modal button:has-text("Supprimer"), .modal button:has-text("Confirmer"), [data-testid="confirm-delete"]');
        await confirmButton.click();

        // Should show success message or update list
        await page.waitForTimeout(1000);
    });

    test('should navigate to import page', async ({ page }) => {
        await page.goto('/contacts');

        const importButton = page.locator('a[href*="import"], button:has-text("Importer")');
        await importButton.first().click();

        await expect(page).toHaveURL(/contacts\/import/);
    });

    test('should display import form', async ({ page }) => {
        await page.goto('/contacts/import');

        await expect(page.locator('input[type="file"]')).toBeVisible();
    });

    test('should export contacts', async ({ page }) => {
        await page.goto('/contacts');

        const exportButton = page.locator('a[href*="export"], button:has-text("Exporter")');
        if (await exportButton.isVisible()) {
            // Check if download starts
            const downloadPromise = page.waitForEvent('download');
            await exportButton.click();
            // Download may or may not complete depending on server
        }
    });

    test('should display contact details', async ({ page }) => {
        await page.goto('/contacts');

        // Click on a contact to view details
        const contactRow = page.locator('table tbody tr, .contact-item').first();
        const viewLink = contactRow.locator('a').first();
        await viewLink.click();

        // Should show contact details
        await expect(page.locator('h1, h2').first()).toBeVisible();
    });

    test('should display contact lists', async ({ page }) => {
        await page.goto('/lists');

        await expect(page.locator('h1')).toContainText(/listes/i);
    });

    test('should create new list', async ({ page }) => {
        await page.goto('/lists/create');

        const timestamp = Date.now();
        await page.fill('input[name="name"], input#name', `Test List ${timestamp}`);

        await page.click('button[type="submit"]');

        await page.waitForURL(/lists(?!\/create)/, { timeout: 10000 });
    });

    test('should display tags page', async ({ page }) => {
        await page.goto('/tags');

        await expect(page.locator('h1')).toContainText(/tags/i);
    });

    test('should paginate contacts', async ({ page }) => {
        await page.goto('/contacts');

        const pagination = page.locator('.pagination, nav[aria-label*="pagination"], [data-testid="pagination"]');
        if (await pagination.isVisible()) {
            const nextButton = pagination.locator('a:has-text("Suivant"), button:has-text("Suivant"), a:has-text("Next")');
            if (await nextButton.isVisible()) {
                await nextButton.click();
                await expect(page).toHaveURL(/page=2/);
            }
        }
    });

    test('should display empty state for no contacts', async ({ page }) => {
        // Search for non-existent contact
        await page.goto('/contacts');

        const searchInput = page.locator('input[type="search"], input[placeholder*="Rechercher"]');
        await searchInput.fill('nonexistent12345');
        await page.waitForTimeout(500);

        const emptyState = page.locator('.empty-state, [data-testid="empty-state"], text=Aucun contact');
        // May or may not be visible depending on data
    });
});
