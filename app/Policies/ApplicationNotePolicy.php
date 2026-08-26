<?php

namespace App\Policies;

use App\Models\ApplicationNote;
use App\Models\User;

class ApplicationNotePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ApplicationNote $applicationNote): bool
    {
        return $applicationNote->user_id === $user->id
            && $applicationNote->jobApplication->user_id === $user->id;
    }

    public function delete(User $user, ApplicationNote $applicationNote): bool
    {
        return $this->update($user, $applicationNote);
    }
}
