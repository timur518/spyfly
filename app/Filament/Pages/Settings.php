<?php

namespace App\Filament\Pages;

use App\Models\IntegrationSetting;
use App\Models\MonitoringSetting;
use App\Models\ProfitabilitySetting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use BackedEnum;
use UnitEnum;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected string $view = 'filament.pages.settings';

    protected static ?string $title = 'Настройки';
    protected static ?string $navigationLabel = 'Настройки';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static UnitEnum|string|null $navigationGroup = 'Администрирование';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(array_merge(
            IntegrationSetting::current()->attributesToArray(),
            ProfitabilitySetting::current()->attributesToArray(),
            MonitoringSetting::current()->attributesToArray(),
        ));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Настройки')
                    ->tabs([
                        Tab::make('Настройки выгодности')
                            ->schema([
                                Section::make('Фиксация сигнала')
                                    ->schema([
                                        TextInput::make('signal_threshold_percent')
                                            ->label('От скольки процентов выгоды считать рейс сигналом')
                                            ->helperText('Например, 40 означает: если билет дешевле средней цены на 40% и больше, сигнал попадёт в записи. Рейсы дешевле 3000 ₽ тоже попадут в сигналы.')
                                            ->numeric()
                                            ->required()
                                            ->suffix('%')
                                            ->minValue(0)
                                            ->maxValue(100),
                                    ])
                                    ->columns(1),
                                Repeater::make('rules')
                                    ->label('Правила оценки. Задайте, сколько баллов давать за диапазон выгоды в процентах.')
                                    ->addActionLabel('Добавить правило')
                                    ->schema([
                                        TextInput::make('from_percent')
                                            ->label('От %')
                                            ->numeric()
                                            ->required(),
                                        TextInput::make('to_percent')
                                            ->label('До %')
                                            ->numeric(),
                                        TextInput::make('points')
                                            ->label('Баллы')
                                            ->numeric()
                                            ->required(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Интеграции')
                            ->schema([
                                Section::make('Интеграции')
                                    ->schema([
                                        TextInput::make('travelpayouts_api_base')
                                            ->label('Travelpayouts API URL')
                                            ->url()
                                            ->required(),
                                        TextInput::make('travelpayouts_api_token')
                                            ->label('Travelpayouts API Токен')
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                        TextInput::make('travelpayouts_partner_id')
                                            ->label('Travelpayouts partner ID')
                                            ->required(),
                                        TextInput::make('travelpayouts_tp_trs')
                                            ->label('Travelpayouts tp_trs')
                                            ->required(),
                                        TextInput::make('travelpayouts_tp_p')
                                            ->label('Travelpayouts tp_p')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('Мониторинг подписок')
                            ->schema([
                                Section::make('Период проверки')
                                    ->description('Фоновая команда будет запускаться по этому интервалу и обновлять matched_flights у активных подписок.')
                                    ->schema([
                                        TextInput::make('subscription_scan_interval_minutes')
                                            ->label('Проверять подписки каждые N минут')
                                            ->helperText('Например, 60 = один запуск в час.')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->maxValue(1440),
                                    ])
                                    ->columns(1),
                            ]),
                        Tab::make('Авторизация')
                            ->schema([
                                Section::make('OAuth-провайдеры')
                                    ->description('Укажи client ID и client secret из кабинетов сервисов. Callback URL: /auth/yandex/callback, /auth/vk/callback, /auth/ok/callback.')
                                    ->schema([
                                        TextInput::make('yandex_client_id')
                                            ->label('Yandex client ID')
                                            ->required(),
                                        TextInput::make('yandex_client_secret')
                                            ->label('Yandex client secret')
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                        TextInput::make('vkontakte_client_id')
                                            ->label('VK client ID')
                                            ->required(),
                                        TextInput::make('vkontakte_client_secret')
                                            ->label('VK client secret')
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                        TextInput::make('odnoklassniki_client_id')
                                            ->label('OK client ID')
                                            ->required(),
                                        TextInput::make('odnoklassniki_client_secret')
                                            ->label('OK client secret')
                                            ->password()
                                            ->revealable()
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ProfitabilitySetting::current()->update([
            'signal_threshold_percent' => $data['signal_threshold_percent'] ?? 40,
            'rules' => $data['rules'] ?? [],
        ]);

        IntegrationSetting::current()->update(Arr::only($data, [
            'travelpayouts_api_base',
            'travelpayouts_api_token',
            'travelpayouts_partner_id',
            'travelpayouts_tp_trs',
            'travelpayouts_tp_p',
            'yandex_client_id',
            'yandex_client_secret',
            'vkontakte_client_id',
            'vkontakte_client_secret',
            'odnoklassniki_client_id',
            'odnoklassniki_client_secret',
        ]));

        MonitoringSetting::current()->update([
            'subscription_scan_interval_minutes' => $data['subscription_scan_interval_minutes'] ?? 60,
        ]);

        Notification::make()
            ->success()
            ->title('Настройки сохранены')
            ->body('Параметры выгодности, интеграций, мониторинга и авторизации обновлены.')
            ->send();
    }
}
