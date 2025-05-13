<?php

namespace App\Filament\Resources;

use App\Enums\TutorMessageType;
use App\Filament\Resources\TutorMessageResource\Pages;
use App\Models\TutorMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use FilamentTiptapEditor\TiptapEditor;
use FilamentTiptapEditor\Enums\TiptapOutput;
use Awcodes\FilamentTiptapEditor\Components\MentionItem;

class TutorMessageResource extends Resource
{
    protected static ?string $model = TutorMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Mensajes';

    protected static ?string $modelLabel = 'Mensaje';

    protected static ?string $pluralModelLabel = 'Mensajes';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('label')
                    ->label('Tipo de Mensaje')
                    ->options(TutorMessageType::getOptions())
                    ->required()
                    ->helperText('Selecciona el tipo de mensaje que quieres configurar')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Un nombre descriptivo para identificar este mensaje'),
                TiptapEditor::make('message')
                    ->mentionItems([
                        ['label' => '[tutor]', 'id' => 'Tutor'],
                        ['label' => '[nino]', 'id' => 'Nino'],
                        ['label' => '[fecha]', 'id' => 'Fecha'],
                        ['label' => '[comentario]', 'id' => 'Comentario'],
                    ])
                    ->label('Mensaje')
                    ->required()
                    ->profile('simple')
                    ->output(TiptapOutput::Html)
                    ->placeholder('Escribe el mensaje aquí...')
                    ->helperText('Escribe @ para insertar una etiqueta')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'mention-highlight',
                        'data-mention-attributes' => json_encode([
                            'class' => 'mention',
                            'data-type' => 'mention',
                            'contenteditable' => 'false'
                        ])
                    ]),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->maxLength(255)
                    ->helperText('Una breve descripción de cuándo se envía este mensaje'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Si está desactivado, este mensaje no se enviará'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => TutorMessageType::from($state)->getLabel())
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('label')
                    ->label('Tipo')
                    ->options(TutorMessageType::getOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTutorMessages::route('/'),
            'create' => Pages\CreateTutorMessage::route('/create'),
            'edit' => Pages\EditTutorMessage::route('/{record}/edit'),
        ];
    }
} 