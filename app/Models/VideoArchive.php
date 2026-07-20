<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoArchive extends Model
{
    use HasFactory;

    public const CATEGORIES = ['News', 'Iklan Layanan Masyarakat', 'Program'];
    public const ISSUES = ['Ekonomi', 'Lingkungan', 'Sosial'];
    public const STATUSES = ['Draft', 'Review', 'Siap Tayang', 'Sudah Tayang', 'Diarsipkan'];

    protected $fillable = [
        'user_id', 'title', 'description', 'category', 'issue', 'status',
        'air_date', 'video_url', 'file_path', 'thumbnail_path', 'original_name', 'mime_type', 'file_size',
    ];

    protected function casts(): array
    {
        return ['air_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(VideoArchiveActivity::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') return number_format($bytes, $unit === 'B' ? 0 : 1).' '.$unit;
            $bytes /= 1024;
        }
        return $bytes.' B';
    }
}
