<?php

namespace NexusPlugin\TpOpenConnect\Filament\Resources;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use NexusPlugin\TpOpenConnect\Filament\Resources\ApiAppResource\Pages;
use NexusPlugin\TpOpenConnect\Models\ApiApp;

class ApiAppResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth('nexus-web')->check() && (int) auth('nexus-web')->user()->class >= User::CLASS_SYSOP;
    }

    protected static ?string $model = ApiApp::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 120;

    protected static ?string $navigationLabel = 'TP Open Connect';

    protected static ?string $modelLabel = '第三方接入应用';

    protected static ?string $pluralModelLabel = '第三方接入应用';

    protected static ?string $slug = 'tp-open-connect';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('基本信息')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('app_name')
                                    ->label('接入应用名称')
                                    ->required()
                                    ->placeholder('例如：移动端应用'),
                                Select::make('status')
                                    ->label('状态')
                                    ->options([
                                        1 => '启用',
                                        0 => '禁用',
                                    ])
                                    ->default(1)
                                    ->required(),
                            ]),
                        Toggle::make('sso_verify')
                            ->label('启用 SSO 验证')
                            ->helperText('允许第三方接入应用进行 SSO 用户验证')
                            ->default(true)
                            ->onColor('success')
                            ->afterStateHydrated(function (Toggle $component, $state, $record) {
                                if ($record) {
                                    $rules = $record->asset_rules ?? [];
                                    $component->state($rules['sso_verify'] ?? false);
                                }
                            }),
                        Toggle::make('verify_origin')
                            ->label('校验请求来源')
                            ->helperText('启用后 API 请求将验证 Origin/Referer 是否在白名单中（生产环境建议开启，开发测试建议关闭）')
                            ->default(false) // 默认关闭，方便开发测试
                            ->onColor('success')
                            ->offColor('gray')
                            ->afterStateHydrated(function (Toggle $component, $state, $record) {
                                if ($record) {
                                    $component->state($record->verify_origin ?? false);
                                }
                            }),
                        TagsInput::make('redirect_uris')
                            ->label('回调域名白名单')
                            ->helperText('每个域名一行，例如：https://site.example.com（启用"校验请求来源"后生效）')
                            ->placeholder('输入域名后按回车添加')
                            ->afterStateHydrated(function (TagsInput $component, $state, $record) {
                                if ($record) {
                                    $uris = $record->redirect_uris ?? [];
                                    $component->state($uris);
                                }
                            }),
                    ]),

                Section::make('资产权限配置')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('asset_type_header')
                                    ->label('资产类型')
                                    ->content(''),
                                Placeholder::make('query_header')
                                    ->label('查询'),
                                Placeholder::make('deduct_header')
                                    ->label('扣减'),
                                Placeholder::make('increase_header')
                                    ->label('增加'),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('upload_label')
                                    ->label('上传量')
                                    ->content(''),
                                Checkbox::make('upload_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['upload']['query'] ?? false);
                                        }
                                    }),
                                Checkbox::make('upload_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['upload']['deduct'] ?? false);
                                        }
                                    }),
                                Checkbox::make('upload_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['upload']['increase'] ?? false);
                                        }
                                    }),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('download_label')
                                    ->label('下载量')
                                    ->content(''),
                                Checkbox::make('download_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['download']['query'] ?? false);
                                        }
                                    }),
                                Checkbox::make('download_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['download']['deduct'] ?? false);
                                        }
                                    }),
                                Checkbox::make('download_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['download']['increase'] ?? false);
                                        }
                                    }),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('bonus_label')
                                    ->label('魔力值')
                                    ->content(''),
                                Checkbox::make('bonus_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['bonus']['query'] ?? false);
                                        }
                                    }),
                                Checkbox::make('bonus_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['bonus']['deduct'] ?? false);
                                        }
                                    }),
                                Checkbox::make('bonus_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['bonus']['increase'] ?? false);
                                        }
                                    }),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('signin_card_label')
                                    ->label('签到卡')
                                    ->content(''),
                                Checkbox::make('signin_card_query')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['signin_card']['query'] ?? false);
                                        }
                                    }),
                                Checkbox::make('signin_card_deduct')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
                                        if ($record) {
                                            $rules = $record->asset_rules ?? [];
                                            $component->state($rules['asset_rules']['signin_card']['deduct'] ?? false);
                                        }
                                    }),
                                Checkbox::make('signin_card_increase')
                                    ->label('')
                                    ->afterStateHydrated(function (Checkbox $component, $state, $record) {
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('app_id')
                    ->label('接入应用 ID')
                    ->copyable()
                    ->copyMessage('App ID 已复制')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('app_name')
                    ->label('名称')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? '启用' : '禁用')
                    ->colors([
                        'success' => 1,
                        'danger' => 0,
                    ]),
                TextColumn::make('sso_verify')
                    ->label('SSO')
                    ->badge()
                    ->formatStateUsing(fn (ApiApp $record): string => ($record->asset_rules['sso_verify'] ?? false) ? '是' : '否')
                    ->colors([
                        'info' => true,
                        'gray' => false,
                    ]),
                TextColumn::make('verify_origin')
                    ->label('来源校验')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? '启用' : '关闭')
                    ->colors([
                        'warning' => true,
                        'gray' => false,
                    ]),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        1 => '启用',
                        0 => '禁用',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('regenerate_secret')
                    ->label('进入详情重置 Secret')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->url(fn (ApiApp $record): string => static::getUrl('view', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
