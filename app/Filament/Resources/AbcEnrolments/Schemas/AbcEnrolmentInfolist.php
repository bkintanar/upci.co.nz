<?php

namespace App\Filament\Resources\AbcEnrolments\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

/**
 * Laid out in the enrolment form's own sections, so staff read an
 * application in the order the applicant filled it in.
 *
 * Escaped by default. Do not add ->html() or swap any entry for a
 * markdown/rich renderer -- the content comes from an unauthenticated public
 * endpoint. Long answers keep their line breaks via whitespace-pre-line
 * instead.
 */
class AbcEnrolmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('full_name')->label('Full name'),
                            TextEntry::make('date_of_birth')->label('Date of birth')->date('j M Y'),
                            TextEntry::make('gender')->formatStateUsing(fn ($state) => ucfirst($state)),
                            TextEntry::make('citizenship'),
                            TextEntry::make('email')->copyable(),
                            TextEntry::make('created_at')->label('Received')->dateTime('j M Y, g:ia'),
                            TextEntry::make('home_phone')->label('Home phone'),
                            TextEntry::make('mobile_phone')->label('Mobile phone')->copyable(),
                            self::yesNo('resides_in_nz', 'Residing in New Zealand'),
                            TextEntry::make('residence_country')
                                ->label('Country of residence')
                                ->visible(fn ($record) => ! $record->resides_in_nz),
                        ]),
                        self::longText('address', 'Address'),
                        self::longText('reason_for_attending', 'Why they want to attend'),
                    ])
                    ->columnSpanFull(),

                Section::make('Previous Bible school attendance')
                    ->schema([
                        Grid::make(2)->schema([
                            self::yesNo('attended_upci_bible_school', 'Attended a UPCI Bible school before'),
                            TextEntry::make('transcript_original_name')
                                ->label('Transcript')
                                ->placeholder('None uploaded'),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Learning challenges')
                    ->schema([
                        Grid::make(2)->schema([
                            self::yesNo('has_learning_difficulties', 'Learning difficulties'),
                            TextEntry::make('learning_difficulty_display')
                                ->label('Which')
                                ->visible(fn ($record) => $record->has_learning_difficulties),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Spiritual information')
                    ->schema([
                        Grid::make(2)->schema([
                            self::yesNo('wants_ministerial_licence', 'Desires to become a licensed minister'),
                            self::yesNo('read_articles_of_faith', 'Read and understood the Articles of Faith'),
                            self::yesNo('baptised_in_jesus_name', 'Baptised in the name of Jesus Christ'),
                            self::yesNo('received_holy_ghost', 'Received the Holy Ghost'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('church_display')->label('Church'),
                            TextEntry::make('pastor_name')->label('Pastor'),
                            TextEntry::make('attendance_duration')->label('Attended for'),
                        ]),
                        self::longText('ministry_involvement', 'Ministry involvement'),
                    ])
                    ->columnSpanFull(),

                Section::make('Agreement')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('agreement_name')->label('Printed name'),
                            TextEntry::make('agreement_date')->label('Date')->date('j M Y'),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function yesNo(string $field, string $label): TextEntry
    {
        return TextEntry::make($field)
            ->label($label)
            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
            ->badge()
            ->color(fn ($state) => $state ? 'success' : 'gray');
    }

    private static function longText(string $field, string $label): TextEntry
    {
        return TextEntry::make($field)
            ->label($label)
            ->extraAttributes(['class' => 'whitespace-pre-line'])
            ->columnSpanFull();
    }
}
