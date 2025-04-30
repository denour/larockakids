<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KidResource\Pages;
use App\Filament\Resources\KidResource\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\KidResource\RelationManagers\AllergiesRelationManager;
use App\Models\Contact;
use App\Models\Allergy;
use App\Models\Kid;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use App\Enums\Country;

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
            $schema[] = Forms\Components\Repeater::make('contacts')
                ->label('Contactos')
                ->schema([
                    Forms\Components\Select::make('contact_id')
                        ->label('Contacto')
                        ->options(Contact::query()->pluck('first_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('first_name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('last_name')
                                ->label('Apellidos')
                                ->required()
                                ->maxLength(255),
                            PhoneInput::make('phone')
                                ->label('Teléfono')
                                ->required()
                                ->defaultCountry(Country::getDefaultCountry())
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    $set('international_code', $state);
                                }),
                            Forms\Components\TextInput::make('email')
                                ->label('Correo Electrónico')
                                ->email()
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $contact = Contact::create([
                                'first_name' => $data['first_name'],
                                'last_name' => $data['last_name'],
                                'phone' => $data['phone'],
                                'international_code' => $data['phone'],
                                'email' => $data['email'],
                            ]);

                            return $contact->id;
                        })
                        ->required(),
                    Forms\Components\Select::make('relationship_type')
                        ->label('Parentesco')
                        ->options([
                            'parent' => 'Padre/Madre',
                            'family' => 'Familiar',
                            'friend of parent' => 'Amigo de los Padres',
                            'guardian' => 'Tutor',
                            'other' => 'Otro',
                        ])
                        ->required(),
                ])
                ->defaultItems(1)
                ->minItems(1)
                ->maxItems(5)
                ->collapsible()
                ->columnSpanFull();

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

        return $form->schema($schema);
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
                    ->formatStateUsing(fn (Kid $record) => $record->age . ' años')
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
