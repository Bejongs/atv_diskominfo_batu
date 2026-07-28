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
    public const AGE_RATINGS = [
        'SU' => 'Semua Umur (0+)',
        'A' => 'Anak (7-12 tahun)',
        'R' => 'Remaja (13-17 tahun)',
        'D' => 'Dewasa (18+)',
    ];

    protected $fillable = [
        'user_id', 'title', 'description', 'category', 'issue', 'age_rating', 'status',
        'air_date', 'air_time', 'video_url', 'duration_minutes', 'duration_seconds', 'file_path', 'thumbnail_path', 'original_name', 'mime_type', 'file_size',
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
        if (! $this->file_size) {
            return 'Tidak ada file';
        }

        $bytes = $this->file_size;
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') return number_format($bytes, $unit === 'B' ? 0 : 1).' '.$unit;
            $bytes /= 1024;
        }
        return $bytes.' B';
    }

    public function getFormattedDurationAttribute(): string
    {
        $totalSeconds = $this->duration_seconds ?: ($this->duration_minutes ? $this->duration_minutes * 60 : null);

        if (! $totalSeconds) {
            return 'Belum diisi';
        }

        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' jam';
        }

        if ($minutes > 0) {
            $parts[] = $minutes.' menit';
        }

        if ($seconds > 0 || $parts === []) {
            $parts[] = $seconds.' detik';
        }

        return implode(' ', $parts);
    }

    public function getAgeRatingLabelAttribute(): string
    {
        return self::AGE_RATINGS[$this->age_rating] ?? 'Belum dipilih';
    }

    public function getFormattedAirScheduleAttribute(): string
    {
        if (! $this->air_date) {
            return 'Belum ditentukan';
        }

        $schedule = $this->air_date->format('d M Y');

        if ($this->air_time) {
            $schedule .= ', '.substr((string) $this->air_time, 0, 5);
        }

        return $schedule;
    }
}
