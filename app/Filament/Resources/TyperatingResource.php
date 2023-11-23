<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TypeRatingResource\Pages;
use App\Filament\Resources\TypeRatingResource\RelationManagers;
use App\Models\Typerating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TyperatingResource extends Resource
{
    protected static ?string $navigationGroup = 'Config';
    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'Type Ratings';

    protected static ?string $modelLabel = 'Type Ratings';

    protected static ?string $model = Typerating::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Type Rating Informations')->schema([
                    Forms\Components\TextInput::make('name')->required()->label('Name'),
                    Forms\Components\TextInput::make('type')->required()->label('Type'),
                    Forms\Components\TextInput::make('description')->label('Description'),
                    Forms\Components\TextInput::make('image_url')->label('Image URL'),
                    Forms\Components\Toggle::make('active')->offIcon('heroicon-m-x-circle')->offColor('danger')->onIcon('heroicon-m-check-circle')->onColor('success')->default(true),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('image_url'),
                Tables\Columns\IconColumn::make('active')->label('Active')->color(fn ($state) => $state ? 'success' : 'danger')->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
            ])
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
                Tables\Actions\CreateAction::make()->label('Add Type Rating')->icon('heroicon-o-plus-circle'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubfleetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTypeRatings::route('/'),
            'create' => Pages\CreateTypeRating::route('/create'),
            'edit'   => Pages\EditTypeRating::route('/{record}/edit'),
        ];
    }
}