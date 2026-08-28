<?php

namespace ProjectMoon\FilamentDomainManager\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use ProjectMoon\FilamentDomainManager\Models\Domain;
use ProjectMoon\FilamentDomainManager\Resources\DomainResource\Pages;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Domains';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Domain Information')
                    ->description('Specify custom domain and multi-tenancy parameters.')
                    ->schema([
                        Forms\Components\TextInput::make('domain')
                            ->label('Domain Hostname')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g. shop.tenant-brand.com')
                            ->helperText('Enter the full apex or subdomain for this tenant.')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('tenant_id')
                            ->label('Tenant ID / Identifier')
                            ->placeholder('e.g. tenant-1002')
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Connection Mode & DNS Automation')
                    ->description('Select automated 1-click provisioning via provider API, or manual smart DNS.')
                    ->schema([
                        Forms\Components\Radio::make('connection_mode')
                            ->label('Connection Strategy')
                            ->options([
                                'auto' => 'Automated (Provider API / Direct Connect)',
                                'manual' => 'Manual (Smart CNAME & A Record setup)',
                            ])
                            ->default('manual')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('provider')
                            ->label('DNS Provider')
                            ->options([
                                'cloudflare' => 'Cloudflare (API Token)',
                                'godaddy' => 'GoDaddy (Key & Secret)',
                                'mock' => 'Test / Mock Provider',
                            ])
                            ->visible(fn (Forms\Get $get) => $get('connection_mode') === 'auto')
                            ->required(fn (Forms\Get $get) => $get('connection_mode') === 'auto'),

                        Forms\Components\KeyValue::make('provider_credentials')
                            ->label('Provider API Credentials')
                            ->keyLabel('Parameter (e.g. api_token / key / secret)')
                            ->valueLabel('Secret Value')
                            ->visible(fn (Forms\Get $get) => $get('connection_mode') === 'auto')
                            ->helperText('Stored securely using AES-256 encryption. If left empty, global environment tokens are used.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('connection_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'auto' ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('dns_status')
                    ->label('DNS Health')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('ssl_status')
                    ->label('SSL Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'valid' => 'success',
                        'expiring_soon' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('dns_last_checked_at')
                    ->label('Last Checked')
                    ->dateTime()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dns_status')
                    ->options([
                        'verified' => 'Verified',
                        'pending' => 'Pending Propagation',
                        'failed' => 'Failed / Mismatch',
                    ]),

                Tables\Filters\SelectFilter::make('connection_mode')
                    ->options([
                        'auto' => 'Automated (Provider API)',
                        'manual' => 'Manual CNAME',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('verify_dns')
                    ->label('Verify DNS')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (Domain $record) {
                        $result = $record->verifyDns();

                        if ($result->isVerified()) {
                            Notification::make()
                                ->title('DNS Verified Successfully!')
                                ->body("Domain {$record->domain} is fully propagated (100%).")
                                ->success()
                                ->send();
                        } elseif ($result->isPending()) {
                            Notification::make()
                                ->title('DNS Propagation in Progress')
                                ->body("Domain {$record->domain} is partially propagated ({$result->propagation->percentage}%).")
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('DNS Verification Failed')
                                ->body("Records for {$record->domain} did not match target expectations.")
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('dns_instructions')
                    ->label('DNS Setup')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->modalHeading(fn (Domain $record) => "DNS Setup Instructions for {$record->domain}")
                    ->modalDescription('Add the following record(s) at your domain DNS provider:')
                    ->modalContent(fn (Domain $record) => view('filament-domain-manager::dns-instructions-modal', [
                        'domain' => $record,
                        'expected' => $record->getExpectedRecords(),
                        'ingressTarget' => config('domain-manager.ingress_target', 'ingress.example.com'),
                        'ingressIp' => config('domain-manager.ingress_ip', '192.0.2.1'),
                    ])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Domain $record) {
                        if ($record->connection_mode === 'auto') {
                            $record->removeWithProvider();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomains::route('/'),
            'create' => Pages\CreateDomain::route('/create'),
            'edit' => Pages\EditDomain::route('/{record}/edit'),
        ];
    }
}
