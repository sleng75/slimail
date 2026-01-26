<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show the settings page.
     */
    public function index()
    {
        $tenant = Auth::user()->tenant;

        return Inertia::render('Settings/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'country' => $tenant->country,
                'website' => $tenant->getSetting('website'),
                'tax_id' => $tenant->getSetting('tax_id'),
                'timezone' => $tenant->timezone ?? 'Africa/Abidjan',
                'locale' => $tenant->locale ?? 'fr',
                'currency' => $tenant->getSetting('currency', 'XOF'),
            ],
            'timezones' => $this->getTimezones(),
            'locales' => $this->getLocales(),
            'currencies' => $this->getCurrencies(),
        ]);
    }

    /**
     * Update tenant settings.
     */
    public function update(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:10'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        // Extract settings that go to the JSON column
        $settingsFields = ['website', 'tax_id', 'currency'];
        $settings = $tenant->settings ?? [];

        foreach ($settingsFields as $field) {
            if (isset($validated[$field])) {
                $settings[$field] = $validated[$field];
                unset($validated[$field]);
            }
        }

        $validated['settings'] = $settings;
        $tenant->update($validated);

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Update tenant logo.
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // 2MB max
        ]);

        $tenant = Auth::user()->tenant;

        // Delete old logo if exists
        if ($tenant->logo) {
            \Storage::disk('public')->delete($tenant->logo);
        }

        $path = $request->file('logo')->store('logos', 'public');

        $tenant->update(['logo' => $path]);

        return back()->with('success', 'Logo mis à jour avec succès.');
    }

    /**
     * Delete tenant logo.
     */
    public function deleteLogo()
    {
        $tenant = Auth::user()->tenant;

        if ($tenant->logo) {
            \Storage::disk('public')->delete($tenant->logo);
            $tenant->update(['logo' => null]);
        }

        return back()->with('success', 'Logo supprimé.');
    }

    /**
     * Show email settings page.
     */
    public function emailSettings()
    {
        $tenant = Auth::user()->tenant;

        return Inertia::render('Settings/Email', [
            'tenant' => [
                'id' => $tenant->id,
                'default_from_name' => $tenant->getSetting('default_from_name', $tenant->name),
                'default_from_email' => $tenant->getSetting('default_from_email', 'noreply@' . $tenant->slug . '.slimail.com'),
                'default_reply_to' => $tenant->getSetting('default_reply_to'),
                'email_footer' => $tenant->getSetting('email_footer'),
                'unsubscribe_page_text' => $tenant->getSetting('unsubscribe_page_text'),
            ],
        ]);
    }

    /**
     * Update email settings.
     */
    public function updateEmailSettings(Request $request)
    {
        $tenant = Auth::user()->tenant;

        $validated = $request->validate([
            'default_from_name' => ['nullable', 'string', 'max:255'],
            'default_from_email' => ['nullable', 'email', 'max:255'],
            'default_reply_to' => ['nullable', 'email', 'max:255'],
            'email_footer' => ['nullable', 'string', 'max:2000'],
            'unsubscribe_page_text' => ['nullable', 'string', 'max:2000'],
        ]);

        // Store in settings JSON
        $settings = $tenant->settings ?? [];
        foreach ($validated as $key => $value) {
            $settings[$key] = $value;
        }

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Paramètres d\'email mis à jour.');
    }

    /**
     * Get available timezones.
     */
    private function getTimezones(): array
    {
        return [
            'Africa/Abidjan' => 'Abidjan (GMT)',
            'Africa/Dakar' => 'Dakar (GMT)',
            'Africa/Lagos' => 'Lagos (GMT+1)',
            'Africa/Douala' => 'Douala (GMT+1)',
            'Africa/Kinshasa' => 'Kinshasa (GMT+1)',
            'Africa/Brazzaville' => 'Brazzaville (GMT+1)',
            'Africa/Casablanca' => 'Casablanca (GMT)',
            'Europe/Paris' => 'Paris (GMT+1)',
            'UTC' => 'UTC',
        ];
    }

    /**
     * Get available locales.
     */
    private function getLocales(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
        ];
    }

    /**
     * Get available currencies.
     */
    private function getCurrencies(): array
    {
        return [
            'XOF' => 'Franc CFA (XOF)',
            'XAF' => 'Franc CFA CEMAC (XAF)',
            'EUR' => 'Euro (EUR)',
            'USD' => 'Dollar US (USD)',
            'GNF' => 'Franc Guinéen (GNF)',
        ];
    }
}
