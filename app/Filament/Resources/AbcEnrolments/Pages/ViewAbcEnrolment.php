<?php

namespace App\Filament\Resources\AbcEnrolments\Pages;

use App\Models\AbcEnrolment;
use Filament\Actions\Action;
use App\Models\AbcPastorReference;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Notifications\AbcPastorReferenceRequest;
use Illuminate\Support\Facades\Notification as Mail;
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
            $this->emailPastorAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * Sends the pastor's questionnaire again (an email lost, or the address
     * wrong), or for the first time on an enrolment from before pastor
     * references began. Every send issues a new link, and the old one stops
     * working. Gone once the pastor has answered.
     */
    private function emailPastorAction(): Action
    {
        return Action::make('emailPastor')
            ->label(fn (AbcEnrolment $record) => $record->pastorReference ? 'Email the pastor again' : 'Ask the pastor for a reference')
            ->icon(Heroicon::OutlinedEnvelope)
            ->visible(fn (AbcEnrolment $record) => ! $record->pastorReference?->isSubmitted())
            // The resource is national-only already; said again here because
            // this action sends email in the college's name.
            ->authorize(fn (): bool => (bool) auth()->user()?->isNational())
            ->modalHeading("Email the pastor's questionnaire")
            ->modalDescription('The pastor is emailed a new link to the questionnaire. Any earlier link stops working.')
            ->modalSubmitActionLabel('Send')
            ->fillForm(fn (AbcEnrolment $record) => [
                'pastor_name' => $record->pastorReference?->pastor_name ?? $record->pastor_name,
                'pastor_email' => $record->pastorReference?->pastor_email
                    ?? AbcEnrolment::pastorEmailOnFile($record->church_name, $record->pastor_name),
            ])
            ->schema([
                TextInput::make('pastor_name')->label("Pastor's name")->required()->maxLength(255),
                TextInput::make('pastor_email')->label("Pastor's email address")->email()->required()->maxLength(255),
            ])
            ->action(function (AbcEnrolment $record, array $data): void {
                $reference = $record->pastorReference ?? new AbcPastorReference(['abc_enrolment_id' => $record->id]);

                // Keep where the address came from while it is unchanged; a
                // new one is the administrator's, unless it is the one on file.
                $source = match (true) {
                    $reference->exists && $reference->pastor_email === $data['pastor_email'] => $reference->pastor_email_source,
                    $data['pastor_email'] === AbcEnrolment::pastorEmailOnFile($record->church_name, $data['pastor_name']) => 'church_records',
                    default => 'admin',
                };

                $reference->fill([
                    'pastor_name' => $data['pastor_name'],
                    'pastor_email' => $data['pastor_email'],
                    'pastor_email_source' => $source,
                    'emailed_at' => now(),
                ]);
                $token = $reference->issueToken();
                $reference->save();
                $record->setRelation('pastorReference', $reference);

                rescue(
                    fn () => Mail::route('mail', $data['pastor_email'])
                        ->notify(new AbcPastorReferenceRequest($token, $record->full_name)),
                    report: true,
                );

                Notification::make()->success()
                    ->title("Questionnaire emailed to {$data['pastor_email']}")
                    ->send();
            });
    }
}
