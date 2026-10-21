<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource\Pages;
use NexusPlugin\TpOpenConnect\Models\ApiApp;
use Illuminate\Support\Str;

class ApiAppResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth('nexus-web')->check() && (int) auth('nexus-web')->user()->class >= User::CLASS_SYSOP;
    }

    protected static ?string $model = ApiApp::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 120;

    protected static ?string $navigationLabel = 'TP Open Connect';

    protected static ?string $modelLabel = '第三方接入应用';

    protected static ?string $pluralModelLabel = '第三方接入应用';

    protected static ?string $slug = 'tp-open-connect';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('app_name')
                                    ->label('接入应用名称')
                                    ->required()
                                    ->placeholder('例如：移动端应用'),
                                Forms\Components\Select::make('status')
                                    ->label('状态')
                                    ->options([
                                        1 => '启用',
                                        0 => '禁用',
                                    ])
                                    ->default(1)
                                    ->required(),
                            ]),
                        Forms\Components\Toggle::make('sso_verify')
                            ->label('启用 SSO 验证')
                            ->helperText('允许第三方接入应用进行 SSO 用户验证')
                            ->default(true)
                            ->onColor('success')
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, $state, $record) {
                                if ($record) {
                                    $rules = $record->asset_rules ?? [];
                                    $component->state($rules['sso_verify'] ?? false);
                                }
                            }),
                        Forms\Components\Toggle::make('verify_origin')
                            ->label('校验请求来源')
                            ->helperText('启用后 API 请求将验证 Origin/Referer 是否在白名单中（生产环境建议开启，开发测试建议关闭）')
                            ->default(false) // 默认关闭，方便开发测试
                            ->onColor('success')
                            ->offColor('gray')
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, $state, $record) {
                                if ($record) {
                                    $component->state($record->verify_origin ?? false);
                                }
                            }),
                        Forms\Components\TagsInput::make('redirect_uris')
                            ->label('回调域名白名单')
                            ->helperText('每个域名一行，例如：https://site.example.com（启用"校验请求来源"后生效）')
                            ->placeholder('输入域名后按回车添加')
                            ->afterStateHydrated(function (Forms\Components\TagsInput $component, $state, $record) {
                                if ($record) {
                                    $uris = $record->redirect_uris ?? [];
                                    $component->state($uris);
                                }
                            }),
                    ]),

                Forms\Components\Section::make('资产权限配置')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('asset_type_header')
                                    ->label('资产类型')
                                    ->content(''),
                                Forms\Components\Placeholder::make('query_header')
                                    ->label('查询'),
                                Forms\Components\Placeholder::make('deduct_header')
                                    ->label('扣减'),
                                Forms\Components\Placeholder::make('increase_header')
                                    ->label('增加'),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('upload_label')
                                    ->label('上传量')
                                    ->content(''),
                                Forms\Components\Checkbox::make('upload_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['upload']['query'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('upload_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['upload']['deduct'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('upload_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['upload']['increase'] ?? false);
                                        }
                                    }),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('download_label')
                                    ->label('下载量')
                                    ->content(''),
                                Forms\Components\Checkbox::make('download_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['download']['query'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('download_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['download']['deduct'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('download_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['download']['increase'] ?? false);
                                        }
                                    }),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('bonus_label')
                                    ->label('魔力值')
                                    ->content(''),
                                Forms\Components\Checkbox::make('bonus_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['bonus']['query'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('bonus_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['bonus']['deduct'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('bonus_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['bonus']['increase'] ?? false);
                                        }
                                    }),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('signin_card_label')
                                    ->label('签到卡')
                                    ->content(''),
                                Forms\Components\Checkbox::make('signin_card_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['signin_card']['query'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('signin_card_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['signin_card']['deduct'] ?? false);
                                        }
                                    }),
                                Forms\Components\Checkbox::make('signin_card_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Forms\Components\Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['signin_card']['increase'] ?? false);
                                        }
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('app_id')
                                    ->label('接入应用 ID')
                    ->copyable()
                    ->copyMessage('App ID 已复制')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_name')
                    ->label('名称')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn (int $state): string => $state ? '启用' : '禁用')
                    ->colors([
                        'success' => 1,
                        'danger' => 0,
                    ]),
                Tables\Columns\BadgeColumn::make('sso_verify')
                    ->label('SSO')
                    ->formatStateUsing(fn ($record): string => ($record->asset_rules['sso_verify'] ?? false) ? '是' : '否')
                    ->colors([
                        'info' => true,
                        'gray' => false,
                    ]),
                Tables\Columns\BadgeColumn::make('verify_origin')
                    ->label('来源校验')
                    ->formatStateUsing(fn (bool $state): string => $state ? '启用' : '关闭')
                    ->colors([
                        'warning' => true,
                        'gray' => false,
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        1 => '启用',
                        0 => '禁用',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('regenerate_secret')
                    ->label('进入详情重置 Secret')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->url(fn (ApiApp $record): string => static::getUrl('view', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function regenerateSecret(ApiApp $app): void
    {
        $app->update([
            'app_secret' => 'sec_' . Str::random(32),
            'updated_at' => now(),
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
            'index' => Pages\ListApiApps::route('/'),
            'create' => Pages\CreateApiApp::route('/create'),
            'view' => Pages\ViewApiApp::route('/{record}'),
            'edit' => Pages\EditApiApp::route('/{record}/edit'),
        ];
    }
}
