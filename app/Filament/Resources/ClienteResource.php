<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('emisor_id', Auth::user()?->emisor?->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('emisor_id')
                ->default(fn () => Auth::user()?->emisor?->id),
            Forms\Components\TextInput::make('nif')
                ->label('NIF / CIF / VAT')
                ->required(),
            Forms\Components\TextInput::make('nombre_razon_social')
                ->label('Nombre o Razón Social')
                ->required(),
            Forms\Components\TextInput::make('direccion')
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('codigo_postal')->required(),
            Forms\Components\TextInput::make('municipio')->required(),
            Forms\Components\TextInput::make('provincia'),
            Forms\Components\TextInput::make('pais')->default('ES')->maxLength(2),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\Toggle::make('es_profesional_o_empresa')
                ->label('Es empresa o autónomo (aplica retención IRPF)')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nif')->searchable(),
                Tables\Columns\TextColumn::make('nombre_razon_social')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('municipio'),
                Tables\Columns\IconColumn::make('es_profesional_o_empresa')->boolean()->label('Empresa/Autónomo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}