<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VideoArchive;

class VideoArchivePolicy
{
    public function delete(User $user, VideoArchive $archive): bool
    {
        return $user->canDeleteArchives();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canDeleteArchives();
    }
}
