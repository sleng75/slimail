<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'number',
        'sequence_number',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'discount_reason',
        'total',
        'amount_paid',
        'balance_due',
        'currency',
        'issue_date',
        'due_date',
        'paid_at',
        'sent_at',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_tax_id',
        'line_items',
        'notes',
        'footer',
        'pdf_path',
        'reminder_count',
        'last_reminder_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'line_items' => 'array',
        'sequence_number' => 'integer',
        'reminder_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->number)) {
                $invoice->number = self::generateNumber();
                $invoice->sequence_number = self::getNextSequenceNumber();
            }
            if (empty($invoice->balance_due)) {
                $invoice->balance_due = $invoice->total - ($invoice->amount_paid ?? 0);
            }
        });
    }

    /**
     * Generate invoice number (OHADA compliant).
     * Format: FA-YYYY-NNNNNN
     */
    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $sequence = self::getNextSequenceNumber();
        return sprintf('FA-%s-%06d', $year, $sequence);
    }

    /**
     * Get the next sequence number.
     */
    public static function getNextSequenceNumber(): int
    {
        $year = now()->format('Y');
        $maxSequence = self::whereYear('created_at', $year)
            ->max('sequence_number');
        return ($maxSequence ?? 0) + 1;
    }

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the latest payment (for eager loading compatibility).
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE ||
               ($this->due_date && $this->due_date->isPast() && !$this->isPaid());
    }

    /**
     * Check if invoice is pending payment.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'amount_paid' => $this->total,
            'balance_due' => 0,
        ]);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => $this->status === self::STATUS_DRAFT ? self::STATUS_PENDING : $this->status,
            'sent_at' => now(),
        ]);
    }

    /**
     * Record a payment.
     */
    public function recordPayment(float $amount): void
    {
        $newAmountPaid = $this->amount_paid + $amount;
        $newBalanceDue = max(0, $this->total - $newAmountPaid);

        $status = $newBalanceDue <= 0
            ? self::STATUS_PAID
            : ($newAmountPaid > 0 ? self::STATUS_PARTIAL : $this->status);

        $this->update([
            'amount_paid' => $newAmountPaid,
            'balance_due' => $newBalanceDue,
            'status' => $status,
            'paid_at' => $status === self::STATUS_PAID ? now() : $this->paid_at,
        ]);
    }

    /**
     * Check and update overdue status.
     */
    public function checkOverdue(): void
    {
        if ($this->status === self::STATUS_PENDING && $this->due_date->isPast()) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
    }

    /**
     * Increment reminder count.
     */
    public function recordReminderSent(): void
    {
        $this->update([
            'reminder_count' => $this->reminder_count + 1,
            'last_reminder_at' => now(),
        ]);
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->total, 0, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get formatted balance due.
     */
    public function getFormattedBalanceDue(): string
    {
        return number_format($this->balance_due, 0, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PAID => 'Payée',
            self::STATUS_PARTIAL => 'Partiellement payée',
            self::STATUS_OVERDUE => 'En retard',
            self::STATUS_CANCELED => 'Annulée',
            self::STATUS_REFUNDED => 'Remboursée',
            default => $this->status,
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PAID => 'green',
            self::STATUS_PARTIAL => 'blue',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_CANCELED => 'gray',
            self::STATUS_REFUNDED => 'purple',
            default => 'gray',
        };
    }

    /**
     * Calculate totals from line items.
     */
    public function calculateTotals(): void
    {
        $subtotal = collect($this->line_items)->sum('total');
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $total = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'balance_due' => $total - $this->amount_paid,
        ]);
    }

    /**
     * Add a line item.
     */
    public function addLineItem(string $description, int $quantity, float $unitPrice): void
    {
        $lineItems = $this->line_items ?? [];
        $lineItems[] = [
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ];

        $this->update(['line_items' => $lineItems]);
        $this->calculateTotals();
    }

    /**
     * Scope for pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_PARTIAL,
            self::STATUS_OVERDUE,
        ]);
    }
}
