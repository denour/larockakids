<?php

namespace App\Filament\Resources\KidResource\RelationManagers;

use App\Enums\Country;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $recordTitleAttribute = 'first_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->defaultCountry(Country::getDefaultCountry()->value),
                Forms\Components\TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Select::make('relationship_type')
                    ->label('Parentesco')
                    ->options(Contact::RELATIONSHIP_TYPES)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->recordTitle(fn (Contact $record) => "{$record->full_name} ({$record->phone})")
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellidos'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('pivot.relationship_type')
                    ->label('Parentesco')
                    ->formatStateUsing(fn ($state) => Contact::RELATIONSHIP_TYPES[$state] ?? $state),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Para el papá o la mamá que ya está registrado con un hermano:
                // se vincula el mismo contacto en vez de capturarlo otra vez.
                Tables\Actions\AttachAction::make()
                    ->label('Asignar existente')
                    ->modalHeading('Asignar un contacto ya registrado')
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'phone'])
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Contacto')
                            ->helperText('Busca por nombre, apellido o teléfono.'),
                        Forms\Components\Select::make('relationship_type')
                            ->label('Parentesco')
                            ->options(Contact::RELATIONSHIP_TYPES)
                            ->default('parent')
                            ->required(),
                    ]),
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): Model {
                        $contact = Contact::create([
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'phone' => $data['phone'],
                            'international_code' => Country::getDefaultCountry()->getCode(),
                            'email' => $data['email'],
                        ]);

                        $this->getOwnerRecord()->contacts()->attach($contact->id, [
                            'relationship_type' => $data['relationship_type'],
                        ]);

                        return $contact;
                    }),
            ])
            // Quitar, no eliminar: el contacto puede ser también el papá de un
            // hermano, y borrarlo lo sacaría de todos sus niños.
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->label('Quitar')
                    ->modalHeading('Quitar contacto de este niño')
                    ->modalDescription('El contacto seguirá registrado con sus otros niños.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Quitar seleccionados'),
                ]),
            ]);
    }
}
