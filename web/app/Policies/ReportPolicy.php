<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        return $user->isAdmin() || $report->user_id === $user->id;
    }

    // Owners may change their report only until an admin starts handling it.
    public function update(User $user, Report $report): bool
    {
        return $user->isAdmin() || $this->isPendingOwner($user, $report);
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->isAdmin() || $this->isPendingOwner($user, $report);
    }

    private function isPendingOwner(User $user, Report $report): bool
    {
        return $report->user_id === $user->id && $report->status === ReportStatus::Pending;
    }
}
