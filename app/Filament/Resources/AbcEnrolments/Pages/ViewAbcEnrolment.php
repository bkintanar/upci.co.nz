<?php

namespace App\Filament\Resources\AbcEnrolments\Pages;

use App\Models\AbcEnrolment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\AbcEnrolments\AbcEnrolmentResource;

class ViewAbcEnrolment extends ViewRecord
{
    protected static string $resource = AbcEnrolmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The transcript lives on the private disk, so this action is the
            // only way to reach it, and reaching this page already requires
            // the national-only view permission.
            Action::make('downloadTranscript')
                ->label('Download transcript')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (AbcEnrolment $record) => filled($record->transcript_path)
                    && Storage::disk('local')->exists($record->transcript_path))
                ->action(fn (AbcEnrolment $record) => Storage::disk('local')->download(
                    $record->transcript_path,
                    $record->transcript_original_name ?: basename($record->transcript_path),
                )),
            DeleteAction::make(),
        ];
    }
}
