<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\Widgets\UserStats;
use App\Models\Enums\UserState;
use App\Models\User;
use App\Repositories\AirlineRepository;
use App\Repositories\AirportRepository;
use App\Repositories\RankRepository;
use App\Repositories\RoleRepository;
use App\Support\Timezonelist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use League\ISO3166\ISO3166;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'operations';
    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'users';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return User::where('state', UserState::PENDING)->count() > 0
            ? User::where('state', UserState::PENDING)->count()
            : null;
    }

    public static function form(Form $form): Form
    {
        $airportRepo = app(AirportRepository::class);
        $airlineRepo = app(AirlineRepository::class);
        $rankRepo = app(RankRepository::class);
        $roleRepo = app(RoleRepository::class);
        return $form
            ->schema([
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->required()
                            ->numeric()
                            ->label('Pilot ID'),

                        Forms\Components\TextInput::make('callsign'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->string(),

                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email(),

                        Forms\Components\TextInput::make('password')
                        ->password()
                        ->autocomplete('new-password')
                        ->columnSpanFull(),
                    ])
                    ->columns(2),
                    Forms\Components\Section::make('Location Information')
                    ->schema([
                        Forms\Components\Select::make('country')
                            ->required()
                            ->options(collect((new ISO3166())->all())->mapWithKeys(fn ($item, $key) => [strtolower($item['alpha2']) => str_replace('&bnsp;', ' ', $item['name'])]))
                            ->searchable()
                            ->native(false),

                        Forms\Components\Select::make('timezone')
                            ->options(Timezonelist::toArray())
                            ->searchable()
                            ->native(false),

                        Forms\Components\Select::make('home_airport_id')
                            ->label('Home Airport')
                            ->options($airportRepo->all()->mapWithKeys(fn ($item) => [$item->id => $item->icao.' - '.$item->name]))
                            ->searchable()
                            ->native(false),

                        Forms\Components\Select::make('current_airport_id')
                            ->label('Current Airport')
                            ->options($airportRepo->all()->mapWithKeys(fn ($item) => [$item->id => $item->icao.' - '.$item->name]))
                            ->searchable()
                            ->native(false),
                    ])
                    ->columns(2),
                ])->columnSpan(['lg' => 2]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('User Information')
                        ->schema([
                            Forms\Components\Select::make('state')
                                ->options(UserState::labels())
                                ->searchable()
                                ->native(false),

                            Forms\Components\Select::make('airline_id')
                                ->label('Airline')
                                ->options($airlineRepo->all()->pluck('name', 'id'))
                                ->searchable()
                                ->native(false),

                            Forms\Components\Select::make('rank_id')
                                ->label('Rank')
                                ->options($rankRepo->all()->pluck('name', 'id'))
                                ->searchable()
                                ->native(false),

                            Forms\Components\TextInput::make('transfer_time')
                                ->label('Transferred Hours')
                                ->numeric(),

                            Forms\Components\Select::make('roles')
                            ->label('Roles')
                            //->options($roleRepo->all()->pluck('name', 'id'))
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->native(false)
                            ->multiple(),

                            Forms\Components\Textarea::make('notes')
                                ->label('Management Notes')
                                ->columnSpan('full'),
                        ])
                        ->columnSpan(['lg' => 1]),
                    ]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ident')->label('ID')->searchable(query: function (Builder $query, int $search): Builder {
                    return $query
                        ->where('pilot_id', "{$search}");
                }),
                TextColumn::make('callsign')->label('Callsign')->searchable(),
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('created_at')->label('Registered On')->dateTime('d-m-Y'),
                TextColumn::make('state')->badge()->color(fn (int $state): string => match ($state) {
                    UserState::PENDING => 'warning',
                    UserState::ACTIVE  => 'success',
                    default            => 'info',
                })->formatStateUsing(fn (int $state): string => UserState::label($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\FieldsRelationManager::class,
            RelationManagers\AwardsRelationManager::class,
            RelationManagers\TypeRatingsRelationManager::class,
            RelationManagers\PirepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit'  => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            UserStats::class,
        ];
    }
}