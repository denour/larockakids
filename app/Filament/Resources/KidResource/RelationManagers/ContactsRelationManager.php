<?php

namespace App\Filament\Resources\KidResource\RelationManagers;

use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use App\Enums\Country;

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
                    ->defaultCountry(Country::getDefaultCountry()->value)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('international_code', Country::getDefaultCountry()->getCode());
                    }),
                Forms\Components\TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->maxLength(255),
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellidos'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('pivot.relationship_type')
                    ->label('Parentesco')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'parent' => 'Padre/Madre',
                            'family' => 'Familiar',
                            'friend of parent' => 'Amigo de los Padres',
                            'guardian' => 'Tutor',
                            'other' => 'Otro',
                            default => $state,
                        };
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
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
                            'relationship_type' => $data['relationship_type']
                        ]);
                        return $contact;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 