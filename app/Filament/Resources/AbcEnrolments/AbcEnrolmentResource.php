<?php

namespace App\Filament\Resources\AbcEnrolments;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use App\Models\AbcEnrolment;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\AbcEnrolments\Pages\ViewAbcEnrolment;
use App\Filament\Resources\AbcEnrolments\Pages\ListAbcEnrolments;
use App\Filament\Resources\AbcEnrolments\Tables\AbcEnrolmentsTable;
use App\Filament\Resources\AbcEnrolments\Schemas\AbcEnrolmentInfolist;

/**
 * First-time student enrolments from /apostolic-bible-college/enrollment/
 * new-student. Read and delete only, national users only (AbcEnrolmentPolicy):
 * each row holds an applicant's date of birth, address, phone numbers and any
 * learning difficulties.
 */
class AbcEnrolmentResource extends Resource
{
    protected static ?string $model = AbcEnrolment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'ABC Enrolments';

    protected static ?string $modelLabel = 'ABC enrolment';

    protected static UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return AbcEnrolmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbcEnrolmentsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbcEnrolments::route('/'),
            'view' => ViewAbcEnrolment::route('/{record}'),
        ];
    }
}
