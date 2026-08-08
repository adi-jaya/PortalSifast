<?php

namespace App\Models;

use App\Enums\WebOfficialFeedbackStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebOfficialFeedback extends Model
{
    /** @use HasFactory<\Database\Factories\WebOfficialFeedbackFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'full_name',
        'phone',
        'service_unit',
        'rating',
        'message',
        'status',
        'source',
        'ip_address',
        'user_agent',
        'admin_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'resolved_at' => 'datetime',
            'status' => WebOfficialFeedbackStatus::class,
        ];
    }

    /**
     * @param  Builder<WebOfficialFeedback>  $query
     * @return Builder<WebOfficialFeedback>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('status', '!=', WebOfficialFeedbackStatus::Archived);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicResponseArray(): array
    {
        return [
            'id' => $this->id,
            'submittedAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'phone' => $this->phone,
            'serviceUnit' => $this->service_unit,
            'rating' => $this->rating,
            'message' => $this->message,
            'status' => $this->status->value,
            'source' => $this->source,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'resolvedAt' => $this->resolved_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminDetailArray(): array
    {
        return [
            ...$this->toAdminListArray(),
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'adminNotes' => $this->admin_notes,
        ];
    }
}
