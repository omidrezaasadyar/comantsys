<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getAvatarFormComponent(),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function getAvatarFormComponent(): Component
    {
        return FileUpload::make('avatar_path')
            ->label(__('profile.avatar'))
            ->image()
            ->disk('local')            // private disk: storage/app/private
            ->directory('avatars')
            ->maxSize(2048)            // 2 MB
            ->imageEditor();
    }

    /**
     * Delete the previous avatar file when it is replaced or cleared.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldPath = $record->avatar_path;
        $newPath = $data['avatar_path'] ?? null;

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
