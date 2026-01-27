<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Image') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Update your profile photo to personalize your account.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.image.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div
            class="grid gap-6 sm:grid-cols-[80px_minmax(0,1fr)] sm:items-start sm:gap-x-16"
            x-data="{
                fileName: '',
                defaultUrl: @js($user->profile_image_url ?? asset('images/avatar-default.svg')),
                previewUrl: @js($user->profile_image_url ?? asset('images/avatar-default.svg')),
                resetImage() {
                    if (this.previewUrl && this.previewUrl !== this.defaultUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                    }
                    this.previewUrl = this.defaultUrl;
                    this.fileName = '';
                    if (this.$refs.profileImageInput) {
                        this.$refs.profileImageInput.value = '';
                    }
                }
            }"
        >
            <div class="flex justify-start">
                <img
                    :src="previewUrl"
                    alt="{{ __('Profile image') }}"
                    class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-300"
                />
            </div>

            <div class="flex-1 sm:pl-4">
                <x-input-label for="profile_image" :value="__('Profile image')" />
                <input
                    id="profile_image"
                    name="profile_image"
                    type="file"
                    accept="image/*"
                    x-ref="profileImageInput"
                    @change="
                        if (previewUrl && previewUrl !== defaultUrl) URL.revokeObjectURL(previewUrl);
                        const file = $event.target.files[0];
                        fileName = file ? file.name : '';
                        previewUrl = file ? URL.createObjectURL(file) : defaultUrl;
                    "
                    class="sr-only"
                />
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <x-secondary-button type="button" @click="$refs.profileImageInput.click()">
                        {{ __('Choose Image') }}
                    </x-secondary-button>
                    <x-secondary-button type="button" @click="resetImage()" x-show="fileName">
                        {{ __('Reset') }}
                    </x-secondary-button>
                    <span class="text-sm text-gray-600" x-text="fileName ? fileName : @js(__('No file selected'))"></span>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    {{ __('PNG, JPG, or WEBP up to 2MB.') }}
                </p>
                <x-input-error :messages="$errors->get('profile_image')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if ($user->profile_image_path)
                <form method="post" action="{{ route('profile.image.destroy') }}">
                    @csrf
                    @method('delete')
                    <x-danger-button type="submit">{{ __('Remove') }}</x-danger-button>
                </form>
            @endif

            @if (session('status') === 'profile-image-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @elseif (session('status') === 'profile-image-removed')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Removed.') }}</p>
            @endif
        </div>
    </form>
</section>
