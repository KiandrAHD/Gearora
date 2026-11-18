<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuideSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_guide_id',
        'source_title',
        'publisher',
        'source_url',
        'accessed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return ['accessed_at' => 'date'];
    }

    /**
     * @return BelongsTo<EquipmentGuide, $this>
     */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(EquipmentGuide::class, 'equipment_guide_id');
    }
}
