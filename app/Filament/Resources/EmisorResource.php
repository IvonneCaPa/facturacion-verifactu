<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmisorResource\Pages;
use App\Models\Emisor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EmisorResource extends Resource
{
    protected static ?string $model = Emisor::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Mi Perfil Fiscal';
    protected static ?string $modelLabel = 'Emisor';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('user_id')
                ->default(fn () => Auth::id()),

            Forms\Components\Section::make('Identidad Fiscal y Marca')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nif')
                        ->label('NIF / CIF')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Nombre y Apellidos / Razón Social')
                        ->required(),
                    Forms\Components\TextInput::make('nombre_comercial')
                        ->label('Nombre Comercial (Opcional)'),
                    Forms\Components\FileUpload::make('logo_path')
                        ->label('Logotipo para Facturas')
                        ->image()
                        ->disk('public')
                        ->directory('logos'),
                ]),

            Forms\Components\Section::make('Dirección y Contacto')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('direccion')
                        ->label('Dirección Fiscal')
                        ->required()
                        ->columnSpan(3),
                    Forms\Components\TextInput::make('codigo_postal')
                        ->label('Código Postal')
                        ->required(),
                    Forms\Components\TextInput::make('municipio')
                        ->required(),
                    Forms\Components\TextInput::make('provincia')
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required(),
                    Forms\Components\TextInput::make('telefono')
                        ->tel(),
                    Forms\Components\TextInput::make('iban')
                        ->label('IBAN Bancario'),
                ]),

            Forms\Components\Section::make('Impuestos y Veri*Factu')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('irpf_por_defecto')
                        ->label('IRPF por defecto (%)')
                        ->numeric()
                        ->default(15.00)
                        ->suffix('%'),
                    Forms\Components\Toggle::make('verifactu_activo')
                        ->label('Modalidad Veri*Factu (Envío AEAT)')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')->disk('public')->label('Logo'),
                Tables\Columns\TextColumn::make('nif')->label('NIF')->searchable(),
                Tables\Columns\TextColumn::make('nombre_comercial')->label('Marca')->default(fn (Emisor $r) => $r->razon_social),
                Tables\Columns\TextColumn::make('irpf_por_defecto')->label('IRPF')->suffix('%'),
                Tables\Columns\IconColumn::make('verifactu_activo')->label('Veri*Factu')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEmisors::route('/'),
        ];
    }
}