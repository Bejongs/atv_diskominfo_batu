<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoArchiveActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_archive_id',
        'user_id',
        'action',
        'title_snapshot',
        'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function archive(): BelongsTo
    {
        return $this->belongsTo(VideoArchive::class, 'video_archive_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
