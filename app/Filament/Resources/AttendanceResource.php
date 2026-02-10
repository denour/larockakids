<?php

namespace App\Filament\Resources;

use App\Enums\Country;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Widgets\AttendanceStats;
use App\Models\Allergy;
use App\Models\Attendance;
use App\Models\Contact;
use App\Models\Kid;
use App\Services\TutorMessageService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Torgodly\Html2Media\Tables\Actions\Html2MediaAction;
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
                                $kid->id => $kid->first_name.' '.$kid->last_name,
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if (! $state) {
                            return;
                        }
                        $kid = Kid::find($state);
                        if ($kid) {
                            $firstContact = $kid->contacts()
                                ->orderBy('first_name')
                                ->orderBy('last_name')
                                ->first();
                            if ($firstContact) {
                                $set('contact_id', $firstContact->id);
                            }
                        }
                    })
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
                        Forms\Components\Select::make('allergies')
                            ->label('Alergias')
                            ->options(Allergy::query()->pluck('name', 'id'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la alergia')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Color')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $allergy = Allergy::create($data);

                                return $allergy->id;
                            }),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $kid = Kid::create([
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'birth_date' => $data['birth_date'],
                            'gender' => $data['gender'],
                        ]);

                        if (isset($data['allergies'])) {
                            $kid->allergies()->sync($data['allergies']);
                        }

                        return $kid->id;
                    }),
                Select::make('contact_id')
                    ->label('Responsable')
                    ->options(function (Forms\Get $get) {
                        $kid = Kid::find($get('kid_id'));
                        if (! $kid) {
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
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('kid', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('kid.age')
                    ->label('Edad')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Responsable')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('contact', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
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
                        if (! $value) {
                            return;
                        }

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
                Tables\Actions\Action::make('whatsapp')
                    ->label('Notificar')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->modalHeading(fn (Attendance $record) => "📱 Notificar a {$record->contact->full_name}")
                    ->modalDescription(fn (Attendance $record) => "Vas a enviar una notificación al responsable de {$record->kid->full_name}")
                    ->modalIcon('heroicon-o-chat-bubble-left-right')
                    ->modalIconColor('primary')
                    ->form([
                        Forms\Components\Select::make('situation')
                            ->label('Tipo de Notificación')
                            ->options(function () {
                                // Solo mostrar tipos de mensaje que existen en la base de datos
                                $availableMessages = \App\Models\TutorMessage::where('is_active', true)
                                    ->pluck('name', 'label')
                                    ->toArray();
                                
                                if (empty($availableMessages)) {
                                    return ['none' => 'No hay mensajes configurados'];
                                }
                                
                                return $availableMessages;
                            })
                            ->required()
                            ->disabled(fn () => \App\Models\TutorMessage::where('is_active', true)->count() === 0)
                            ->helperText(fn () => \App\Models\TutorMessage::where('is_active', true)->count() === 0 
                                ? 'Configura mensajes en el panel de administración primero' 
                                : 'Selecciona el tipo de notificación que deseas enviar'),
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje Adicional')
                            ->placeholder('Escribe un mensaje adicional si lo deseas...')
                            ->maxLength(255)
                            ->helperText('Este mensaje se agregará al final de la notificación'),
                    ])
                    ->action(function (Attendance $record, array $data) {
                        $tutorMessageService = app(TutorMessageService::class);
                        $contact = $record->contact;
                        $kid = $record->kid;

                        // Enviar mensaje según la situación
                        switch ($data['situation']) {
                            case 'bathroom':
                                $tutorMessageService->sendBathroomMessage($contact, $kid);
                                break;
                            case 'diaper':
                                $tutorMessageService->sendDiaperMessage($contact, $kid);
                                break;
                            case 'unconsolable':
                                $tutorMessageService->sendUnconsolableMessage($contact, $kid);
                                break;
                            case 'sick':
                                $tutorMessageService->sendSickMessage($contact, $kid);
                                break;
                            case 'recovered':
                                $tutorMessageService->sendRecoveredMessage($contact, $kid);
                                break;
                            case 'exit':
                                $tutorMessageService->sendExitMessage($contact, $kid);
                                break;
                        }

                        // Mostrar notificación de éxito
                        \Filament\Notifications\Notification::make()
                            ->title('Notificación enviada')
                            ->body('Se ha enviado el mensaje al tutor')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('exit')
                    ->label('Registrar Salida')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->action(function (Attendance $record) {
                        $record->update([
                            'check_out' => now(),
                        ]);

                        $tutorMessageService = app(TutorMessageService::class);
                        $tutorMessageService->sendExitMessage($record->contact, $record->kid);

                        \Filament\Notifications\Notification::make()
                            ->title('Salida registrada')
                            ->body("Se ha registrado la salida de {$record->kid->full_name} y enviado el mensaje a {$record->contact->full_name}")
                            ->success()
                            ->send();
                    }),
                Html2MediaAction::make('print')
                    ->label('Imprimir Sticker')
                    ->icon('heroicon-o-printer')
                    ->format([62, 62], 'mm')
                    ->margin([0, 0, 0, 0]) // Set custom margins

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
            'history' => Pages\AttendanceHistory::route('/history'),
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
