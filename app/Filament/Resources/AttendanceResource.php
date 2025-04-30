<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Widgets\AttendanceStats;
use App\Models\Attendance;
use App\Models\Kid;
use App\Models\Contact;
use App\Models\Allergy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Modal;
use Torgodly\Html2Media\Tables\Actions\Html2MediaAction;
use Illuminate\Support\Facades\Notification;
use App\Enums\Country;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;


class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Asistencias';

    protected static ?string $modelLabel = 'Asistencia';

    protected static ?string $pluralModelLabel = 'Asistencias';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('check_in', Carbon::today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['kid', 'contact'])
            ->whereNotNull('kid_id')
            ->whereNull('check_out')
            ->whereHas('kid', function ($query) {
                $query->whereNotNull('birth_date');
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('kid_id')
                    ->label('Niño')
                    ->options(function () {
                        return Kid::query()
                            ->whereNotNull('birth_date')
                            ->get()
                            ->mapWithKeys(fn ($kid) => [
                                $kid->id => $kid->first_name . ' ' . $kid->last_name
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Fecha de Nacimiento')
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'male' => 'Niño',
                                'female' => 'Niña',
                            ])
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $kid = Kid::create($data);
                        return $kid->id;
                    }),
                Select::make('contact_id')
                    ->label('Responsable')
                    ->options(function (Forms\Get $get) {
                        $kid = Kid::find($get('kid_id'));
                        if (!$kid) {
                            return [];
                        }
                        return $kid->contacts()
                            ->get()
                            ->pluck('full_name', 'id');
                    })
                    ->searchable()
                    ->required()
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
                            ->defaultCountry(Country::getDefaultCountry()->value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('international_code', Country::getDefaultCountry()->getCode());
                            }),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data, Forms\Get $get) {
                        $contact = Contact::create([
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'phone' => $data['phone'],
                            'international_code' => Country::getDefaultCountry()->getCode(),
                            'email' => $data['email'],
                        ]);

                        $kid = Kid::find($get('kid_id'));
                        if ($kid) {
                            $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);
                        }

                        return $contact->id;
                    })
                    ->createOptionAction(
                        fn (Forms\Components\Actions\Action $action) => $action
                            ->modalHeading('Crear nuevo contacto')
                            ->modalSubmitActionLabel('Crear contacto')
                    ),
                Textarea::make('observations')
                    ->label('Observaciones')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kid.full_name')
                    ->label('Niño')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->kid->allergies->isNotEmpty() 
                            ? view('filament.tables.columns.allergies-badges', [
                                'allergies' => $record->kid->allergies
                            ])
                            : null
                    )
                    ->html(),
                Tables\Columns\TextColumn::make('kid.age')
                    ->label('Edad')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->contact->phone),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Hora de entrada')
                    ->formatStateUsing(fn ($state) => $state ? $state->diffForHumans() : '')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('date')
                    ->label('Fecha')
                    ->options([
                        'today' => 'Hoy',
                        'yesterday' => 'Ayer',
                        'this_week' => 'Esta semana',
                        'last_week' => 'Semana pasada',
                        'this_month' => 'Este mes',
                        'last_month' => 'Mes pasado',
                    ])
                    ->query(function (Builder $query, $data) {
                        $value = $data['value'] ?? null;
                        if (!$value) return;

                        $now = now();
                        switch ($value) {
                            case 'today':
                                $query->whereDate('check_in', $now);
                                break;
                            case 'yesterday':
                                $query->whereDate('check_in', $now->subDay());
                                break;
                            case 'this_week':
                                $query->whereBetween('check_in', [$now->startOfWeek(), $now->endOfWeek()]);
                                break;
                            case 'last_week':
                                $query->whereBetween('check_in', [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()]);
                                break;
                            case 'this_month':
                                $query->whereMonth('check_in', $now->month);
                                break;
                            case 'last_month':
                                $query->whereMonth('check_in', $now->subMonth()->month);
                                break;
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('checkout')
                    ->label('Salida')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->modalHeading('Registrar Salida')
                    ->modalIcon('heroicon-o-arrow-right-on-rectangle')
                    ->modalIconColor('success')
                    ->modalDescription('¿Estás seguro de que deseas registrar la salida del niño?')
                    ->form([
                        Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->placeholder('Escribe alguna observación sobre la salida del niño...')
                            ->maxLength(255),
                    ])
                    ->action(function (Attendance $record, array $data) {
                        $record->update([
                            'check_out' => now(),
                            'observations' => $data['observations'] ?? null,
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Salida registrada')
                            ->body("Se ha registrado la salida de {$record->kid->first_name} a las " . now()->format('H:i'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('whatsapp')
                    ->label('Notificar')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->modalHeading(fn (Attendance $record) => "🚨 Notificar a {$record->contact->first_name}")
                    ->modalDescription(fn (Attendance $record) => "Vas a enviar una notificación al responsable de {$record->kid->first_name}")
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->form([
                        Forms\Components\Select::make('situation')
                            ->label('Tipo de Notificación')
                            ->options([
                                'fever' => 'Fiebre',
                                'accident' => 'Accidente',
                                'pickup' => 'Recoger al niño',
                                'other' => 'Otra situación',
                            ])
                            ->required()
                            ->helperText('Selecciona el tipo de notificación que deseas enviar'),
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje Adicional')
                            ->placeholder('Escribe un mensaje adicional si lo deseas...')
                            ->maxLength(255)
                            ->helperText('Este mensaje se agregará al final de la notificación'),
                        Forms\Components\TextInput::make('temperature')
                            ->label('Temperatura')
                            ->numeric()
                            ->suffix('°C')
                            ->visible(fn (Forms\Get $get) => $get('situation') === 'fever')
                            ->helperText('Ingresa la temperatura si es una notificación de fiebre'),
                    ])
                    ->action(function (Attendance $record, array $data) {
                        $contact = $record->contact;
                        $kid = $record->kid;
                        $baseMessage = "🚨 *ALERTA - La Roca Kids*\n\n";
                        $baseMessage .= "Hola {$contact->first_name},\n\n";
                        
                        switch ($data['situation']) {
                            case 'fever':
                                $temperature = $data['temperature'] ?? 'no registrada';
                                $message = "{$baseMessage}te informamos que {$kid->first_name} presenta fiebre.\n";
                                $message .= "Temperatura: {$temperature}°C\n";
                                $message .= "Por favor, ven a recogerlo lo antes posible.";
                                break;
                            case 'accident':
                                $message = "{$baseMessage}te informamos que {$kid->first_name} ha tenido un accidente.\n";
                                $message .= "Por favor, ven a recogerlo lo antes posible.";
                                break;
                            case 'pickup':
                                $message = "{$baseMessage}te recordamos que es hora de recoger a {$kid->first_name}.\n";
                                $message .= "Hora de entrada: {$record->check_in->format('H:i')}";
                                break;
                            case 'other':
                                $message = "{$baseMessage}te informamos que {$kid->first_name} necesita atención.\n";
                                $message .= "Por favor, ven a recogerlo lo antes posible.";
                                break;
                        }
                        
                        if (!empty($data['message'])) {
                            $message .= "\n\n*Mensaje adicional:*\n{$data['message']}";
                        }
                        
                        $message .= "\n\n*Datos del niño:*\n";
                        $message .= "Nombre: {$kid->first_name} {$kid->last_name}\n";
                        $message .= "Edad: {$kid->age} años";
                        
                        if ($kid->allergies->isNotEmpty()) {
                            $message .= "\nAlergias: " . $kid->allergies->pluck('name')->join(', ');
                        }
                        
                        $whatsappUrl = "https://wa.me/{$contact->phone}?text=" . urlencode($message);
                        return redirect($whatsappUrl);
                    }),
                Html2MediaAction::make('print')
                    ->label('Imprimir Sticker')
                    ->icon('heroicon-o-printer')
                    ->content(fn (Attendance $record) => view('components.sticker', [
                        'kid' => $record->kid,
                        'contact' => $record->contact,
                    ]))
                    ->print(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            AttendanceStats::class,
        ];
    }
} 