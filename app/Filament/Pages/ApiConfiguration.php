<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;

class ApiConfiguration extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'API Configuration';

    protected static string|\UnitEnum|null $navigationGroup  = 'Management';

    protected string $view = 'filament.pages.api-configuration';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'facebook_app_id' => Setting::get('facebook_app_id'),
            'facebook_app_secret' => Setting::get('facebook_app_secret'),
            'facebook_graph_version' => Setting::get('facebook_graph_version', 'v22.0'),
            'facebook_callback_url' => Setting::get('facebook_callback_url'),
            'instagram_callback_url' => Setting::get('instagram_callback_url'),
            'meta_verify_token' => Setting::get('meta_verify_token'),
        ]);
    }

    public function form($form)
    {
        return $form
            ->statePath('data')
                    ->schema([

                        TextInput::make('facebook_app_id')
                            ->label('Facebook App ID')
                            ->required(),

                        TextInput::make('facebook_app_secret')
                            ->label('Facebook App Secret')
                            ->password()
                            ->revealable()
                            ->required(),

                        TextInput::make('facebook_graph_version')
                            ->label('Graph Version')
                            ->default('v22.0')
                            ->required(),

                        TextInput::make('facebook_callback_url')
                            ->label('Facebook Callback URL')
                            ->required(),

                        TextInput::make('instagram_callback_url')
                            ->label('Instagram Callback URL')
                            ->required(),

                        TextInput::make('meta_verify_token')
                            ->label('Meta Verify Token')
                            ->required(),

                    ])
                    ->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Configuration')
                ->action(function () {

                    $data = $this->form->getState();

                    foreach ($data as $key => $value) {

                        Setting::set($key, $value);
                    }

                    Notification::make()
                        ->title('Configuration saved')
                        ->success()
                        ->send();
                }),
        ];
    }
}