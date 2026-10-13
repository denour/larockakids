<?php

namespace App\Filament\Resources\KidResource\Pages;

use App\Filament\Resources\KidResource;
use App\Models\Kid;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Kid $record
 */
class EditKid extends EditRecord
{
    protected static string $resource = KidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->addSiblingAction(),
            $this->linkSiblingAction(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Registra un hermano nuevo que ya nace con los mismos papás.
     */
    protected function addSiblingAction(): Actions\Action
    {
        return Actions\Action::make('addSibling')
            ->label('Agregar hermano')
            ->icon('heroicon-o-user-plus')
            ->color('gray')
            ->visible(fn (): bool => $this->record->contacts()->exists())
            ->modalHeading(fn (): string => "Agregar hermano de {$this->record->full_name}")
            ->modalDescription('Se registrará con los mismos contactos y parentescos.')
            ->form([
                Forms\Components\TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->label('Apellidos')
                    ->default(fn (): string => $this->record->last_name)
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
                    ->required(),
            ])
            ->action(function (array $data): void {
                $sibling = Kid::create($data);

                $this->record->shareContactsWith($sibling);

                Notification::make()
                    ->success()
                    ->title('Hermano registrado')
                    ->body("{$sibling->full_name} quedó con los mismos contactos que {$this->record->full_name}.")
                    ->actions([
                        NotificationAction::make('view')
                            ->label('Ver ficha')
                            ->url(KidResource::getUrl('edit', ['record' => $sibling])),
                    ])
                    ->send();
            });
    }

    /**
     * Une a dos niños ya registrados como hermanos: cada uno recibe los
     * contactos que le faltan del otro.
     */
    protected function linkSiblingAction(): Actions\Action
    {
        return Actions\Action::make('linkSibling')
            ->label('Vincular hermano')
            ->icon('heroicon-o-link')
            ->color('gray')
            ->modalHeading(fn (): string => "Vincular un hermano de {$this->record->full_name}")
            ->modalDescription('Ambos niños quedarán con los contactos de los dos.')
            ->form([
                Forms\Components\Select::make('sibling_id')
                    ->label('Hermano')
                    ->helperText('Busca por nombre o apellido.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Kid::query()
                        ->whereKeyNot($this->record->getKey())
                        ->where(fn ($query) => $query
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"))
                        ->orderBy('first_name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Kid $kid): array => [$kid->id => "{$kid->full_name} ({$kid->age} años)"])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => Kid::find($value)?->full_name)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $sibling = Kid::findOrFail($data['sibling_id']);

                $this->record->shareContactsWith($sibling);
                $sibling->shareContactsWith($this->record);

                Notification::make()
                    ->success()
                    ->title('Hermanos vinculados')
                    ->body("{$this->record->full_name} y {$sibling->full_name} ahora comparten contactos.")
                    ->send();

                // La tabla de contactos es otro componente y no se entera del cambio.
                $this->redirect(KidResource::getUrl('edit', ['record' => $this->record]));
            });
    }
}
