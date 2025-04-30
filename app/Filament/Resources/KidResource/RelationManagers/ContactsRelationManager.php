<?php

namespace App\Filament\Resources\KidResource\RelationManagers;

use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $recordTitleAttribute = 'first_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id')
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
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('international_code')
                            ->label('Código Internacional')
                            ->required()
                            ->maxLength(5),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        $contact = Contact::create($data);
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
                        $contact = Contact::find($data['id']);
                        $this->getOwnerRecord()->contacts()->attach($contact->id, [
                            'relationship_type' => $data['relationship_type']
                        ]);
                        return $contact;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
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
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 