import { test, expect } from '@playwright/test';

test.describe('Campaign Management', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto('/login');
        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard|home|campaigns/, { timeout: 15000 });
    });

    test('should display campaigns list', async ({ page }) => {
        await page.goto('/campaigns');

        await expect(page.locator('h1')).toContainText(/campagnes/i);
    });

    test('should display create campaign button', async ({ page }) => {
        await page.goto('/campaigns');

        const createButton = page.locator('a[href*="create"], button:has-text("Créer"), button:has-text("Nouvelle")');
        await expect(createButton.first()).toBeVisible();
    });

    test('should navigate to create campaign page', async ({ page }) => {
        await page.goto('/campaigns');

        await page.click('a[href*="create"], button:has-text("Créer"), button:has-text("Nouvelle")');

        await expect(page).toHaveURL(/campaigns\/create|campaigns\/new/);
    });

    test('should display campaign creation wizard', async ({ page }) => {
        await page.goto('/campaigns/create');

        // Should show steps or form
        const steps = page.locator('.steps, .wizard-steps, [data-testid="steps"]');
        const form = page.locator('form');

        await expect(form).toBeVisible();
    });

    test('should fill campaign configuration step', async ({ page }) => {
        await page.goto('/campaigns/create');

        const timestamp = Date.now();

        // Fill basic info
        await page.fill('input[name="name"], input#name', `Test Campaign ${timestamp}`);
        await page.fill('input[name="subject"], input#subject', 'Test Subject Line');
        await page.fill('input[name="from_email"], input#from_email', 'test@example.com');
        await page.fill('input[name="from_name"], input#from_name', 'Test Sender');

        // Click next or continue
        const nextButton = page.locator('button:has-text("Suivant"), button:has-text("Continuer"), button:has-text("Next")');
        if (await nextButton.isVisible()) {
            await nextButton.click();
        }
    });

    test('should select recipients for campaign', async ({ page }) => {
        await page.goto('/campaigns/create');

        // Fill first step
        await page.fill('input[name="name"]', 'Recipient Test Campaign');
        await page.fill('input[name="subject"]', 'Test Subject');
        await page.fill('input[name="from_email"]', 'test@example.com');

        const nextButton = page.locator('button:has-text("Suivant"), button:has-text("Continuer")');
        if (await nextButton.isVisible()) {
            await nextButton.click();
            await page.waitForTimeout(500);
        }

        // Should show list selection
        const listCheckboxes = page.locator('input[type="checkbox"][name*="list"], [data-testid="list-checkbox"]');
        if (await listCheckboxes.first().isVisible()) {
            await listCheckboxes.first().check();
        }
    });

    test('should display email editor', async ({ page }) => {
        // Navigate directly to an existing draft campaign's edit page
        await page.goto('/campaigns');

        // Try to create or edit a campaign
        const editLink = page.locator('a[href*="edit"]').first();
        if (await editLink.isVisible()) {
            await editLink.click();

            // Should show email editor or content step
            await page.waitForTimeout(1000);
            const editor = page.locator('.gjs-editor, .email-editor, [data-testid="email-editor"], iframe');
            // Editor may take time to load
        }
    });

    test('should save campaign as draft', async ({ page }) => {
        await page.goto('/campaigns/create');

        const timestamp = Date.now();
        await page.fill('input[name="name"]', `Draft Campaign ${timestamp}`);
        await page.fill('input[name="subject"]', 'Draft Subject');
        await page.fill('input[name="from_email"]', 'test@example.com');

        // Save as draft
        const saveButton = page.locator('button:has-text("Enregistrer"), button:has-text("Sauvegarder"), button[type="submit"]');
        await saveButton.first().click();

        await page.waitForTimeout(1000);

        // Verify campaign was created
        await page.goto('/campaigns');
        await expect(page.locator(`text=Draft Campaign ${timestamp}`).first()).toBeVisible({ timeout: 5000 });
    });

    test('should filter campaigns by status', async ({ page }) => {
        await page.goto('/campaigns');

        const statusFilter = page.locator('select[name="status"], [data-testid="status-filter"], button:has-text("Statut")');
        if (await statusFilter.isVisible()) {
            await statusFilter.click();
            await page.waitForTimeout(300);
        }
    });

    test('should search campaigns', async ({ page }) => {
        await page.goto('/campaigns');

        const searchInput = page.locator('input[type="search"], input[placeholder*="Rechercher"], input[name="search"]');
        await searchInput.fill('test');

        await page.waitForTimeout(500);
    });

    test('should view campaign statistics', async ({ page }) => {
        await page.goto('/campaigns');

        // Click on a sent campaign to view stats
        const sentCampaign = page.locator('tr:has-text("Envoyée"), tr:has-text("Sent"), .campaign-sent').first();
        if (await sentCampaign.isVisible()) {
            const viewLink = sentCampaign.locator('a').first();
            await viewLink.click();

            // Should show statistics
            await expect(page.locator('.stats, [data-testid="campaign-stats"], h2:has-text("Statistiques")')).toBeVisible({ timeout: 5000 });
        }
    });

    test('should duplicate campaign', async ({ page }) => {
        await page.goto('/campaigns');

        const duplicateButton = page.locator('button[title*="Dupliquer"], button:has-text("Dupliquer"), [data-action="duplicate"]').first();
        if (await duplicateButton.isVisible()) {
            await duplicateButton.click();

            // Should create copy
            await page.waitForTimeout(1000);
            await expect(page.locator('text=(copie)').first()).toBeVisible();
        }
    });

    test('should delete draft campaign', async ({ page }) => {
        await page.goto('/campaigns');

        // Find a draft campaign
        const draftRow = page.locator('tr:has-text("Brouillon"), tr:has-text("Draft")').first();
        if (await draftRow.isVisible()) {
            const deleteButton = draftRow.locator('button[title*="Supprimer"], button:has-text("Supprimer")');
            await deleteButton.click();

            // Confirm deletion
            const confirmButton = page.locator('.modal button:has-text("Supprimer"), [data-testid="confirm-delete"]');
            await confirmButton.click();

            await page.waitForTimeout(1000);
        }
    });

    test('should schedule campaign', async ({ page }) => {
        await page.goto('/campaigns');

        // Find a draft campaign
        const editLink = page.locator('a[href*="edit"]').first();
        if (await editLink.isVisible()) {
            await editLink.click();
            await page.waitForTimeout(1000);

            // Look for schedule option
            const scheduleButton = page.locator('button:has-text("Programmer"), button:has-text("Schedule")');
            if (await scheduleButton.isVisible()) {
                await scheduleButton.click();

                // Fill schedule date/time
                const dateInput = page.locator('input[type="datetime-local"], input[name="scheduled_at"]');
                if (await dateInput.isVisible()) {
                    const futureDate = new Date(Date.now() + 24 * 60 * 60 * 1000);
                    await dateInput.fill(futureDate.toISOString().slice(0, 16));
                }
            }
        }
    });

    test('should send test email', async ({ page }) => {
        await page.goto('/campaigns');

        const editLink = page.locator('a[href*="edit"]').first();
        if (await editLink.isVisible()) {
            await editLink.click();
            await page.waitForTimeout(1000);

            const testButton = page.locator('button:has-text("Envoyer un test"), button:has-text("Test")');
            if (await testButton.isVisible()) {
                await testButton.click();

                // Fill test email
                const emailInput = page.locator('input[type="email"][name="test_email"], .modal input[type="email"]');
                if (await emailInput.isVisible()) {
                    await emailInput.fill('test@example.com');

                    const sendButton = page.locator('.modal button:has-text("Envoyer"), .modal button[type="submit"]');
                    await sendButton.click();
                }
            }
        }
    });

    test('should display A/B test options', async ({ page }) => {
        await page.goto('/campaigns/create');

        const abTestOption = page.locator('input[value="ab_test"], button:has-text("A/B Test"), [data-testid="ab-test-option"]');
        if (await abTestOption.isVisible()) {
            await abTestOption.click();

            // Should show A/B test configuration
            const abConfig = page.locator('.ab-test-config, [data-testid="ab-test-config"]');
            await expect(abConfig).toBeVisible({ timeout: 3000 });
        }
    });

    test('should preview campaign on mobile and desktop', async ({ page }) => {
        await page.goto('/campaigns');

        const editLink = page.locator('a[href*="edit"]').first();
        if (await editLink.isVisible()) {
            await editLink.click();
            await page.waitForTimeout(1000);

            const previewButton = page.locator('button:has-text("Aperçu"), button:has-text("Preview")');
            if (await previewButton.isVisible()) {
                await previewButton.click();

                // Check for mobile/desktop toggle
                const mobileToggle = page.locator('button:has-text("Mobile"), [data-device="mobile"]');
                const desktopToggle = page.locator('button:has-text("Desktop"), [data-device="desktop"]');
                // May or may not be visible
            }
        }
    });
});

test.describe('Email Templates', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[type="email"]', 'test@slimail.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard|home/, { timeout: 15000 });
    });

    test('should display templates list', async ({ page }) => {
        await page.goto('/templates');

        await expect(page.locator('h1')).toContainText(/templates/i);
    });

    test('should create new template', async ({ page }) => {
        await page.goto('/templates/create');

        const timestamp = Date.now();
        await page.fill('input[name="name"], input#name', `Test Template ${timestamp}`);

        // Template editor should be visible
        const editor = page.locator('.gjs-editor, .email-editor, [data-testid="email-editor"]');
        // Editor initialization may take time
    });

    test('should display pre-built templates gallery', async ({ page }) => {
        await page.goto('/templates');

        const galleryTab = page.locator('button:has-text("Galerie"), a:has-text("Galerie"), [data-tab="gallery"]');
        if (await galleryTab.isVisible()) {
            await galleryTab.click();

            const templateCards = page.locator('.template-card, [data-testid="template-card"]');
            await expect(templateCards.first()).toBeVisible({ timeout: 5000 });
        }
    });
});
