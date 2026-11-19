<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SerieFacturacionResource\Pages;
use App\Models\SerieFacturacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SerieFacturacionResource extends Resource
{
    protected static ?string $model = SerieFacturacion::class;
    protected static ?string $navigationIcon = 'heroicon-o-hashtag';
    protected static ?string $navigationLabel = 'Series de Facturación';
    protected static ?int $navigationSort = 2;

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
            Forms\Components\TextInput::make('prefijo')
                ->label('Prefijo (ej. F2026-)')
                ->required()
                ->maxLength(20),
            Forms\Components\Select::make('tipo')
                ->options([
                    'ordinaria' => 'Ordinaria (F1)',
                    'rectificativa' => 'Rectificativa (R1)',
                    'simplificada' => 'Simplificada (F2)',
                ])
                ->default('ordinaria')
                ->required(),
            Forms\Components\TextInput::make('ultimo_numero')
                ->label('Último número emitido')
                ->numeric()
                ->default(0)
                ->disabled(fn (?SerieFacturacion $record) => $record !== null && $record->ultimo_numero > 0),
            Forms\Components\Toggle::make('activa')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prefijo')->label('Serie')->searchable(),
                Tables\Columns\TextColumn::make('tipo')->badge(),
                Tables\Columns\TextColumn::make('ultimo_numero')->label('Último Nº'),
                Tables\Columns\IconColumn::make('activa')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSerieFacturacions::route('/'),
        ];
    }
}