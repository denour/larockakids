<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiTokenResource\Pages;
use App\Models\ApiToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiTokenResource extends Resource
{
    protected static ?string $model = ApiToken::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'API Tokens';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->helperText('Nombre descriptivo (ej: "Denour Bot")'),
            
            Forms\Components\CheckboxList::make('abilities')
                ->options([
                    '*' => 'Full Access',
                    'kids:read' => 'Read Kids',
                    'attendance:read' => 'Read Attendance',
                    'export' => 'Export Data',
                ])
                ->default(['*'])
                ->columns(2),

            Forms\Components\DateTimePicker::make('expires_at')
                ->label('Expires At')
                ->helperText('Leave empty for no expiration'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('abilities')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->dateTime()
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiTokens::route('/'),
            'create' => Pages\CreateApiToken::route('/create'),
        ];
    }
}
