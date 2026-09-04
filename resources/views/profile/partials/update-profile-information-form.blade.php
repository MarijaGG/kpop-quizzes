<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Choose your profile picture and update your fan profile.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="bio" :value="__('Bio')" />
            <textarea id="bio" name="bio" maxlength="160" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('bio', $user->bio) }}</textarea>
            <p class="mt-1 text-sm text-gray-600">Up to 160 characters.</p>
            <x-input-error class="mt-2" :messages="$errors->get('bio')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="avatar_member_id" :value="__('Profile picture')" />
            @php($selectedGroup = $avatarMember['group_id'] ?? '')
            <div class="current-avatar-preview mt-2">
                @if($avatarMember && !empty($avatarMember['image']))
                    <img src="{{ asset('storage/'.$avatarMember['image']) }}" alt="{{ $avatarMember['name'] }}">
                    <span>{{ $avatarMember['name'] }}</span>
                @else
                    <span class="current-avatar-empty">No profile picture selected</span>
                @endif
            </div>
            <div class="avatar-filter-row mt-4">
                <x-input-label for="group_filter" :value="__('Choose a group')" />
                <select id="group_filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="" @selected($selectedGroup === '')>Choose a group...</option>
                    @foreach($groups as $group)
                        <option value="{{ $group['id'] }}" @selected((string) $selectedGroup === (string) $group['id'])>{{ $group['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="avatar-grid mt-3" id="avatar-grid" @if($selectedGroup === '') hidden @endif>
                <label class="avatar-option" data-group="any">
                    <input type="radio" name="avatar_member_id" value=""
                           @checked(old('avatar_member_id', $user->avatar_member_id) === null)>
                    <span class="no-avatar-image">None</span>
                    <span>No profile picture</span>
                </label>
                @foreach($members as $member)
                    <label class="avatar-option" data-group="{{ $member['group_id'] ?? '' }}">
                        <input type="radio" name="avatar_member_id" value="{{ $member['id'] }}"
                               @checked((int) old('avatar_member_id', $user->avatar_member_id) === (int) $member['id'])>
                        <img src="{{ asset('storage/'.$member['image']) }}" alt="{{ $member['name'] }}">
                        <span>{{ $member['name'] }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar_member_id')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save changes') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filter = document.getElementById('group_filter');
        const grid = document.getElementById('avatar-grid');
        const options = document.querySelectorAll('#avatar-grid .avatar-option');

        const updateAvatarOptions = function () {
            const hasSelection = filter.value !== '';
            grid.hidden = !hasSelection;

            options.forEach(function (option) {
                const visible = hasSelection && (option.dataset.group === filter.value || option.dataset.group === 'any');
                option.hidden = !visible;
            });
        };

        filter.addEventListener('change', updateAvatarOptions);
        updateAvatarOptions();
    });
</script>
