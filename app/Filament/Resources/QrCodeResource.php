<?php

namespace App\Filament\Resources;

use App\Enums\QrCodeStatus;
use App\Filament\Resources\QrCodeResource\Pages;
use App\Models\Kid;
use App\Models\QrCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class QrCodeResource extends Resource
{
    protected static ?string $model = QrCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Códigos QR';

    protected static ?string $modelLabel = 'Código QR';

    protected static ?string $pluralModelLabel = 'Códigos QR';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('kid_id')
                    ->label('Niño Asignado')
                    ->relationship('kid', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Kid $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options(collect(QrCodeStatus::cases())->mapWithKeys(
                        fn (QrCodeStatus $status) => [$status->value => $status->getLabel()]
                    ))
                    ->default(QrCodeStatus::Available->value)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\ImageColumn::make('qr_image_url')
                    ->label('QR')
                    ->width(50)
                    ->height(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (QrCodeStatus $state): string => $state->getLabel())
                    ->color(fn (QrCodeStatus $state): string => $state->getColor())
                    ->sortable(),
                Tables\Columns\TextColumn::make('kid.full_name')
                    ->label('Niño Asignado')
                    ->placeholder('Disponible')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Fecha Asignación')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(QrCodeStatus::cases())->mapWithKeys(
                        fn (QrCodeStatus $status) => [$status->value => $status->getLabel()]
                    )),
                Tables\Filters\SelectFilter::make('kid_id')
                    ->label('Niño')
                    ->relationship('kid', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Kid $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('Asignar')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (QrCode $record) => $record->isAvailable())
                    ->form([
                        Forms\Components\Select::make('kid_id')
                            ->label('Seleccionar Niño')
                            ->options(fn () => Kid::query()
                                ->whereDoesntHave('qrCode')
                                ->get()
                                ->mapWithKeys(fn (Kid $kid) => [$kid->id => "{$kid->first_name} {$kid->last_name}"]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (QrCode $record, array $data): void {
                        $kid = Kid::find($data['kid_id']);
                        $record->assignToKid($kid);
                        $record->refresh();

                        Notification::make()
                            ->success()
                            ->title('QR Asignado')
                            ->body("El código {$record->code} ha sido asignado a {$kid->full_name}")
                            ->send();
                    }),
                Tables\Actions\Action::make('markLost')
                    ->label('Marcar Perdido')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (QrCode $record) => $record->isAssigned())
                    ->requiresConfirmation()
                    ->modalHeading('Marcar como Perdido')
                    ->modalDescription('¿Estás seguro de marcar este código QR como perdido? Se desasignará del niño actual.')
                    ->modalSubmitActionLabel('Sí, marcar como perdido')
                    ->action(function (QrCode $record): void {
                        $kidName = $record->kid?->full_name;
                        $record->markAsLost();
                        $record->refresh();

                        Notification::make()
                            ->warning()
                            ->title('QR Marcado como Perdido')
                            ->body("El código {$record->code} ha sido marcado como perdido".($kidName ? " y desasignado de {$kidName}" : ''))
                            ->send();
                    }),
                Tables\Actions\Action::make('unassign')
                    ->label('Desasignar')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->visible(fn (QrCode $record) => $record->isAssigned())
                    ->requiresConfirmation()
                    ->modalHeading('Desasignar QR')
                    ->modalDescription('¿Estás seguro de desasignar este código QR? El código volverá a estar disponible.')
                    ->modalSubmitActionLabel('Sí, desasignar')
                    ->action(function (QrCode $record): void {
                        $kidName = $record->kid?->full_name;
                        $record->unassign();
                        $record->refresh();

                        Notification::make()
                            ->success()
                            ->title('QR Desasignado')
                            ->body("El código {$record->code} ha sido desasignado de {$kidName} y está disponible nuevamente")
                            ->send();
                    }),
                Tables\Actions\Action::make('print')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (QrCode $record) => route('qr-codes.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelected')
                        ->label('Imprimir Seleccionados')
                        ->icon('heroicon-o-printer')
                        ->action(function (Collection $records, Tables\Actions\BulkAction $action) {
                            $ids = $records->pluck('id')->join(',');
                            $url = route('qr-codes.print-batch', ['ids' => $ids]);

                            $action->redirect($url, shouldOpenInNewTab: true);
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQrCodes::route('/'),
            'create' => Pages\CreateQrCode::route('/create'),
            'edit' => Pages\EditQrCode::route('/{record}/edit'),
        ];
    }
}
