<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicioResource\Pages;
use App\Models\Servicio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ServicioResource extends Resource
{
    protected static ?string $model = Servicio::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Catálogo de Servicios';
    protected static ?int $navigationSort = 3;

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
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('descripcion')
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('precio_unitario')
                ->label('Precio Base (€)')
                ->numeric()
                ->prefix('€')
                ->required(),
            Forms\Components\Select::make('tipo_iva')
                ->label('IVA (%)')
                ->options([
                    '21.00' => '21% (General)',
                    '10.00' => '10% (Reducido)',
                    '4.00' => '4% (Superreducido)',
                    '0.00' => '0% (Exento / Intracomunitario)',
                ])
                ->default('21.00')
                ->required(),
            Forms\Components\Toggle::make('aplica_irpf')
                ->label('Sujeto a retención IRPF')
                ->default(true),
            Forms\Components\Toggle::make('activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable(),
                Tables\Columns\TextColumn::make('precio_unitario')->money('EUR')->label('Precio'),
                Tables\Columns\TextColumn::make('tipo_iva')->suffix('%')->label('IVA'),
                Tables\Columns\IconColumn::make('aplica_irpf')->boolean()->label('IRPF'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicios::route('/'),
            'create' => Pages\CreateServicio::route('/create'),
            'edit' => Pages\EditServicio::route('/{record}/edit'),
        ];
    }
}