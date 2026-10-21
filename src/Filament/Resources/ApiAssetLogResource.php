<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources;

use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAssetLogResource\Pages;
use NexusPlugin\TpOpenConnect\Models\ApiAssetLog;

class ApiAssetLogResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth('nexus-web')->check() && (int) auth('nexus-web')->user()->class >= User::CLASS_SYSOP;
    }

    protected static ?string $model = ApiAssetLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 121;

    protected static ?string $navigationLabel = '交易流水';

    protected static ?string $modelLabel = '交易记录';

    protected static ?string $pluralModelLabel = '交易记录';

    protected static ?string $slug = 'trade-logs';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('trade_id')
                    ->label('交易ID')
                    ->copyable()
                    ->copyMessage('交易ID 已复制')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('out_trade_no')
                    ->label('外部订单号')
                    ->copyable()
                    ->fontFamily('mono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('app_id')
                    ->label('接入应用 ID')
                    ->searchable(),
                TextColumn::make('user_id')
                    ->label('用户ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('asset_type')
                    ->label('资产类型')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'upload' => '上传量',
                        'download' => '下载量',
                        'bonus' => '魔力值',
                        'signin_card' => '签到卡',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'upload',
                        'info' => 'download',
                        'warning' => 'bonus',
                        'primary' => 'signin_card',
                    ]),
                TextColumn::make('action')
                    ->label('操作')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'deduct' ? '扣减' : '增加')
                    ->colors([
                        'danger' => 'deduct',
                        'success' => 'increase',
                    ]),
                TextColumn::make('amount')
                    ->label('金额')
                    ->sortable(),
                TextColumn::make('before_amount')
                    ->label('变动前')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('after_amount')
                    ->label('变动后')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('remark')
                    ->label('备注')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('asset_type')
                    ->label('资产类型')
                    ->options([
                        'upload' => '上传量',
                        'download' => '下载量',
                        'bonus' => '魔力值',
                        'signin_card' => '签到卡',
                    ]),
                SelectFilter::make('action')
                    ->label('操作类型')
                    ->options([
                        'deduct' => '扣减',
                        'increase' => '增加',
                    ]),
                Filter::make('app_id')
                    ->schema([
                        TextInput::make('app_id')
                            ->label('接入应用 ID')
                            ->placeholder('输入接入应用 ID'),
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['app_id'] ?? null, fn ($query, $value) => $query->where('app_id', 'like', "%{$value}%"))
                    ),
                Filter::make('user_id')
                    ->schema([
                        TextInput::make('user_id')
                            ->label('用户ID')
                            ->placeholder('输入用户ID'),
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['user_id'] ?? null, fn ($query, $value) => $query->where('user_id', $value))
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
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
            'index' => Pages\ListApiAssetLogs::route('/'),
            'view' => Pages\ViewApiAssetLog::route('/{record}'),
        ];
    }
}
