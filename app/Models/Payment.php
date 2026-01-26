<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_EXPIRED = 'expired';

    // Payment method constants
    public const METHOD_ORANGE_MONEY = 'orange_money';
    public const METHOD_MOOV_MONEY = 'moov_money';
    public const METHOD_MTN_MONEY = 'mtn_money';
    public const METHOD_WAVE = 'wave';
    public const METHOD_CARD = 'card';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'cinetpay_transaction_id',
        'cinetpay_payment_token',
        'cinetpay_payment_url',
        'transaction_id',
        'external_reference',
        'operator_id',
        'phone_number',
        'customer_name',
        'metadata',
        'failure_reason',
        'initiated_at',
        'completed_at',
        'failed_at',
        'expires_at',
        'is_refund',
        'refund_for_payment_id',
        'refund_amount',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_refund' => 'boolean',
        'refund_amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = self::generateTransactionId();
            }
            if (empty($payment->initiated_at)) {
                $payment->initiated_at = now();
            }
        });
    }

    /**
     * Generate a unique transaction ID.
     */
    public static function generateTransactionId(): string
    {
        return 'PAY-' . strtoupper(Str::random(12));
    }

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the original payment (if this is a refund).
     */
    public function originalPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'refund_for_payment_id');
    }

    /**
     * Get the refund (if this payment was refunded).
     */
    public function refund(): HasOne
    {
        return $this->hasOne(Payment::class, 'refund_for_payment_id');
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->expires_at && $this->expires_at->isPast() && $this->isPending());
    }

    /**
     * Check if payment was refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED || $this->refund()->exists();
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(string $externalReference = null, string $operatorId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'external_reference' => $externalReference ?? $this->external_reference,
            'operator_id' => $operatorId ?? $this->operator_id,
        ]);

        // Update invoice if linked
        if ($this->invoice) {
            $this->invoice->recordPayment($this->amount);
        }

        // Activate/renew subscription if linked
        if ($this->subscription) {
            $this->subscription->renew();
        }
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Create a refund for this payment.
     */
    public function createRefund(float $amount = null, string $reason = null): Payment
    {
        $refundAmount = $amount ?? $this->amount;

        $refund = self::create([
            'tenant_id' => $this->tenant_id,
            'invoice_id' => $this->invoice_id,
            'subscription_id' => $this->subscription_id,
            'amount' => $refundAmount,
            'currency' => $this->currency,
            'status' => self::STATUS_PENDING,
            'payment_method' => $this->payment_method,
            'is_refund' => true,
            'refund_for_payment_id' => $this->id,
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
        ]);

        $this->update(['status' => self::STATUS_REFUNDED]);

        return $refund;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        $prefix = $this->is_refund ? '-' : '';
        return $prefix . number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Get payment method label.
     */
    public function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            self::METHOD_ORANGE_MONEY => 'Orange Money',
            self::METHOD_MOOV_MONEY => 'Moov Money',
            self::METHOD_MTN_MONEY => 'MTN Money',
            self::METHOD_WAVE => 'Wave',
            self::METHOD_CARD => 'Carte bancaire',
            self::METHOD_BANK_TRANSFER => 'Virement bancaire',
            self::METHOD_CASH => 'Espèces',
            self::METHOD_OTHER => 'Autre',
            default => $this->payment_method ?? 'Non défini',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Complété',
            self::STATUS_FAILED => 'Échoué',
            self::STATUS_CANCELED => 'Annulé',
            self::STATUS_REFUNDED => 'Remboursé',
            self::STATUS_EXPIRED => 'Expiré',
            default => $this->status,
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELED => 'gray',
            self::STATUS_REFUNDED => 'purple',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get mobile money methods.
     */
    public static function getMobileMoneyMethods(): array
    {
        return [
            self::METHOD_ORANGE_MONEY,
            self::METHOD_MOOV_MONEY,
            self::METHOD_MTN_MONEY,
            self::METHOD_WAVE,
        ];
    }

    /**
     * Check if payment method is mobile money.
     */
    public function isMobileMoney(): bool
    {
        return in_array($this->payment_method, self::getMobileMoneyMethods());
    }

    /**
     * Scope for completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Scope for failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to exclude refunds.
     */
    public function scopeExcludeRefunds($query)
    {
        return $query->where('is_refund', false);
    }

    /**
     * Scope for refunds only.
     */
    public function scopeRefundsOnly($query)
    {
        return $query->where('is_refund', true);
    }
}
