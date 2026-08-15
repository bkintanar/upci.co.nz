<?php

namespace App\Filament\Resources\Attendances;

use App\Filament\Concerns\ScopesToAccessLevel;
use BackedEnum;
use App\Models\Attendance;
use Closure;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Resources\Attendances\Pages\ViewAttendance;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\Attendances\Pages\CreateAttendance;
use App\Filament\Resources\Attendances\Schemas\AttendanceForm;
use App\Filament\Resources\Attendances\Tables\AttendancesTable;
use App\Filament\Resources\Attendances\Schemas\AttendanceInfolist;

class AttendanceResource extends Resource
{
    use ScopesToAccessLevel;

    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static function localScope(): Closure
    {
        return fn (Builder $q, int $churchId) => $q->where('church_id', $churchId);
    }

    protected static function regionalScope(): Closure
    {
        return fn (Builder $q, int $regionId) => $q->whereHas(
            'church',
            fn (Builder $c) => $c->where('region_id', $regionId)
        );
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
            'create' => CreateAttendance::route('/create'),
            'view' => ViewAttendance::route('/{record}'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
    }
}
