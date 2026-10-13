<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KidResource\Pages;
use App\Filament\Resources\KidResource\RelationManagers\AllergiesRelationManager;
use App\Filament\Resources\KidResource\RelationManagers\ContactsRelationManager;
use App\Models\Allergy;
use App\Models\Kid;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class KidResource extends Resource
{
    protected static ?string $model = Kid::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Niños';

    protected static ?string $modelLabel = 'Niño';

    protected static ?string $pluralModelLabel = 'Niños';

    public static function form(Form $form): Form
    {
        $schema = [
            Forms\Components\TextInput::make('first_name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('last_name')
                ->label('Apellidos')
                ->required()
                ->maxLength(255),
            Forms\Components\DatePicker::make('birth_date')
                ->label('Fecha de Nacimiento')
                ->required()
                ->displayFormat('d/m/Y')
                ->native(false),
            Forms\Components\Select::make('gender')
                ->label('Género')
                ->options([
                    'male' => 'Masculino',
                    'female' => 'Femenino',
                ])
                ->default('male')
                ->required()
                ->afterStateHydrated(function ($component, $state) {
                    if (empty($state)) {
                        $component->state('male');
                    }
                }),
        ];

        if ($form->getOperation() === 'create') {
            $schema[] = Forms\Components\Repeater::make('allergies')
                ->label('Alergias')
                ->schema([
                    Forms\Components\Select::make('allergy_id')
                        ->label('Alergia')
                        ->options(Allergy::query()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('color')
                                ->label('Color')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->required(),
                ])
                ->defaultItems(0)
                ->collapsible()
                ->columnSpanFull();
        }

        if ($form->getOperation() === 'edit') {
            $schema[] = Forms\Components\Section::make('Hermanos')
                ->description('Niños que comparten papá, mamá o tutor con este niño.')
                ->schema([
                    Forms\Components\Placeholder::make('siblings')
                        ->hiddenLabel()
                        ->content(fn (?Kid $record): HtmlString => static::siblingsList($record)),
                ])
                ->collapsible();
        }

        return $form->schema($schema);
    }

    /**
     * Lista de hermanos con liga a su ficha.
     */
    public static function siblingsList(?Kid $kid): HtmlString
    {
        $siblings = $kid?->siblings() ?? collect();

        if ($siblings->isEmpty()) {
            return new HtmlString('Sin hermanos registrados.');
        }

        $items = $siblings->map(fn (Kid $sibling): string => sprintf(
            '<li><a href="%s" class="text-primary-600 hover:underline">%s</a> · %d años</li>',
            e(static::getUrl('edit', ['record' => $sibling])),
            e($sibling->full_name),
            $sibling->age,
        ));

        return new HtmlString('<ul class="space-y-1">'.$items->implode('').'</ul>');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha de Nacimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(function ($state) {
                        return $state === 'male' ? 'Masculino' : 'Femenino';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('age')
                    ->label('Edad')
                    ->formatStateUsing(fn (Kid $record) => $record->age.' años')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contacts.first_name')
                    ->label('Contactos')
                    ->listWithLineBreaks()
                    ->limitList(2),
                Tables\Columns\TextColumn::make('allergies.name')
                    ->label('Alergias')
                    ->listWithLineBreaks()
                    ->limitList(2),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                    // ExportBulkAction eliminado para evitar error
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ContactsRelationManager::class,
            AllergiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKids::route('/'),
            'create' => Pages\CreateKid::route('/create'),
            'edit' => Pages\EditKid::route('/{record}/edit'),
        ];
    }
}
