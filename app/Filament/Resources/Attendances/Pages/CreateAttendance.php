<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Models\Church;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Attendances\AttendanceResource;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['user_id'] = $user->id;

        // A local user always records for their own church, whatever the
        // request carries. Everyone else chose one in the form, which is
        // checked below rather than trusted.
        if ($user->isLocal()) {
            $data['church_id'] = $user->church_id;
        }

        // Refuses both a church outside the actor's scope and a local user
        // with no church, which used to save an orphaned row.
        $church = Church::find($data['church_id'] ?? null);
        abort_unless($church && $user->canAccessChurch($church), 403);

        return $data;
    }
}
