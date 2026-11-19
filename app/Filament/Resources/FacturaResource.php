<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacturaResource\Pages;
use App\Models\Factura;
use App\Models\Servicio;
use App\Services\InvoiceIssuerService;
use App\Services\PdfInvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FacturaResource extends Resource
{
    protected static ?string $model = Factura::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Facturas';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('emisor_id', Auth::user()?->emisor?->id)
            ->orderByDesc('id');
    }

    public static function form(Form $form): Form
    {
        $emisorId = Auth::user()?->emisor?->id;

        return $form->schema([
            Forms\Components\Hidden::make('emisor_id')
                ->default($emisorId),
            Forms\Components\Hidden::make('numero_completo')
                ->default(fn () => 'BORRADOR-' . now()->format('YmdHis')),
            Forms\Components\Hidden::make('estado')
                ->default('borrador'),

            Forms\Components\Section::make('Cabecera de Factura')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('serie_id')
                        ->label('Serie')
                        ->relationship(
                            name: 'serie',
                            titleAttribute: 'prefijo',
                            modifyQueryUsing: fn (Builder $query) => $query->where('emisor_id', $emisorId)->where('activa', true)
                        )
                        ->required(),

                    Forms\Components\Select::make('cliente_id')
                        ->label('Cliente')
                        ->relationship(
                            name: 'cliente',
                            titleAttribute: 'nombre_razon_social',
                            modifyQueryUsing: fn (Builder $query) => $query->where('emisor_id', $emisorId)
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_expedicion')
                        ->label('Fecha Expedición')
                        ->default(now())
                        ->required(),
                ]),

            Forms\Components\Section::make('Líneas de Servicio / Conceptos')
                ->schema([
                    Forms\Components\Repeater::make('lineas')
                        ->relationship()
                        ->minItems(1)
                        ->columns(6)
                        ->schema([
                            Forms\Components\Select::make('servicio_id')
                                ->label('Servicio del Catálogo (Opcional)')
                                ->options(fn () => Servicio::where('emisor_id', $emisorId)->where('activo', true)->pluck('nombre', 'id'))
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    if (! $state) {
                                        return;
                                    }
                                    $servicio = Servicio::find($state);
                                    if ($servicio) {
                                        $set('concepto', $servicio->nombre);
                                        $set('descripcion', $servicio->descripcion);
                                        $set('precio_unitario', $servicio->precio_unitario);
                                        $set('tipo_iva', number_format((float) $servicio->tipo_iva, 2, '.', ''));
                                        $set('aplica_irpf', $servicio->aplica_irpf);
                                        $set('subtotal_base', $servicio->precio_unitario);
                                    }
                                })
                                ->columnSpan(6),

                            Forms\Components\TextInput::make('concepto')
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('cantidad')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateLine($get, $set))
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('precio_unitario')
                                ->label('Precio (€)')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateLine($get, $set))
                                ->columnSpan(1),

                            Forms\Components\Select::make('tipo_iva')
                                ->label('IVA %')
                                ->options([
                                    '21.00' => '21%',
                                    '10.00' => '10%',
                                    '4.00' => '4%',
                                    '0.00' => '0%',
                                ])
                                ->default('21.00')
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\Textarea::make('descripcion')
                                ->rows(1)
                                ->columnSpan(4),

                            Forms\Components\Toggle::make('aplica_irpf')
                                ->label('Lleva IRPF')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('subtotal_base')
                                ->label('Subtotal')
                                ->numeric()
                                ->readOnly()
                                ->default(0)
                                ->columnSpan(1),
                        ]),
                ]),

            Forms\Components\Textarea::make('notas')
                ->label('Observaciones en factura')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    private static function recalculateLine(Get $get, Set $set): void
    {
        $cantidad = (float) ($get('cantidad') ?? 0);
        $precio = (float) ($get('precio_unitario') ?? 0);
        $descuento = (float) ($get('descuento_porcentaje') ?? 0);
        $subtotal = round(($cantidad * $precio) * (1 - $descuento / 100), 2);
        $set('subtotal_base', $subtotal);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_completo')
                    ->label('Número')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('fecha_expedicion')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('cliente.nombre_razon_social')
                    ->label('Cliente')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_factura')
                    ->label('Total')
                    ->money('EUR'),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'borrador' => 'warning',
                        'emitida' => 'success',
                        'rectificada' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                // 1. Editar solo si es borrador
                Tables\Actions\EditAction::make()
                    ->visible(fn (Factura $record): bool => $record->estado === 'borrador'),

                // 2. Acción oficial de Emisión Veri*Factu
                Tables\Actions\Action::make('emitir')
                    ->label('Emitir Factura')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Emitir factura bajo normativa Veri*Factu')
                    ->modalDescription('Una vez emitida, se asignará su número definitivo, se calculará la huella SHA-256 encadenada y NO podrá editarse ni eliminarse. ¿Deseas continuar?')
                    ->visible(fn (Factura $record): bool => $record->estado === 'borrador')
                    ->action(function (Factura $record, InvoiceIssuerService $issuer): void {
                        $emitida = $issuer->issue($record, entornoPruebas: true);

                        Notification::make()
                            ->title("Factura {$emitida->numero_completo} emitida correctamente")
                            ->body("Huella SHA-256 generada y bloqueada.")
                            ->success()
                            ->send();
                    }),

                // 3. Ver / Descargar PDF oficial con QR AEAT en pestaña nueva
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (Factura $record): bool => $record->estado !== 'borrador')
                    ->url(fn (Factura $record): string => route('facturas.pdf', $record))
                    ->openUrlInNewTab(),

                // 4. Eliminar solo si es borrador
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Factura $record): bool => $record->estado === 'borrador'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacturas::route('/'),
            'create' => Pages\CreateFactura::route('/create'),
            'edit' => Pages\EditFactura::route('/{record}/edit'),
        ];
    }
}