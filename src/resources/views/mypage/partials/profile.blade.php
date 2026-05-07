<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="max-w-xl">
        @include('profile.partials.update-profile-img-form')
    </div>
</div>

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="max-w-xl">
        @include('profile.partials.update-profile-information-form')
    </div>
</div>

@if ($user->hasUsablePasswordLogin())
    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>
@endif

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="max-w-3xl">
        @include('profile.partials.social-connections')
    </div>
</div>

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="max-w-xl">
        @include('profile.partials.delete-user-form')
    </div>
</div>
