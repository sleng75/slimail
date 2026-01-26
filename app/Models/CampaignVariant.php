<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'variant_key',
        'name',
        'subject',
        'from_name',
        'html_content',
        'preview_text',
        'percentage',
        'is_winner',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
        'percentage' => 'integer',
    ];

    protected $appends = ['open_rate', 'click_rate'];

    /**
     * Get the campaign this variant belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Calculate open rate for this variant.
     */
    public function getOpenRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }
        return round(($this->opened_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Calculate click rate for this variant.
     */
    public function getClickRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }
        return round(($this->clicked_count / $this->delivered_count) * 100, 2);
    }

    /**
     * Get bounce rate for this variant.
     */
    public function getBounceRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }
        return round(($this->bounced_count / $this->sent_count) * 100, 2);
    }

    /**
     * Mark this variant as the winner.
     */
    public function markAsWinner(): void
    {
        // First, unmark all other variants for this campaign
        static::where('campaign_id', $this->campaign_id)
            ->where('id', '!=', $this->id)
            ->update(['is_winner' => false]);

        $this->update(['is_winner' => true]);

        // Update the campaign with the winning variant
        $this->campaign->update(['winning_variant_id' => $this->id]);
    }

    /**
     * Increment a stat counter.
     */
    public function incrementStat(string $stat): void
    {
        $validStats = ['sent_count', 'delivered_count', 'opened_count', 'clicked_count', 'bounced_count'];
        if (in_array($stat, $validStats)) {
            $this->increment($stat);
        }
    }

    /**
     * Get the effective subject (variant or campaign fallback).
     */
    public function getEffectiveSubject(): string
    {
        return $this->subject ?? $this->campaign->subject;
    }

    /**
     * Get the effective from_name (variant or campaign fallback).
     */
    public function getEffectiveFromName(): string
    {
        return $this->from_name ?? $this->campaign->from_name;
    }

    /**
     * Get the effective HTML content (variant or campaign fallback).
     */
    public function getEffectiveHtmlContent(): ?string
    {
        return $this->html_content ?? $this->campaign->html_content;
    }
}
