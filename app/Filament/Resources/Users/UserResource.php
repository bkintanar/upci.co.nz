<?php

namespace App\Filament\Resources\Users;

use Closure;
use BackedEnum;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\ScopesToAccessLevel;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\Schemas\UserInfolist;

class UserResource extends Resource
{
    use ScopesToAccessLevel;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    // Both scopes always include the actor themselves. UserPolicy lets anyone
    // edit their own profile, but without this a user with no church (every
    // regional presbyter, typically) got a 404 on their own edit page.
    protected static function localScope(): Closure
    {
        return fn (Builder $q, int $churchId) => $q->where(fn (Builder $w) => $w
            ->whereKey(auth()->id())
            ->orWhere('church_id', $churchId));
    }

    protected static function regionalScope(): Closure
    {
        // Deliberately not orWhere('region_id', ...): that would list regional
        // peers, whom UserPolicy::update refuses anyway.
        return fn (Builder $q, int $regionId) => $q->where(fn (Builder $w) => $w
            ->whereKey(auth()->id())
            ->orWhereHas('church', fn (Builder $c) => $c->where('region_id', $regionId)));
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
