<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_legal_name',
        'trade_license_number',
        'vat_registration_number',
        'incorporation_date',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'industry',
        'employee_count',
        'annual_revenue',
        'credit_limit',
        'payment_terms',
    ];

    protected $casts = [
        'incorporation_date' => 'date',
        'employee_count' => 'integer',
        'annual_revenue' => 'integer',
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Get the user that owns the corporate profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
