<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Showcase titles</h2>
        <p class="mt-1 text-sm text-gray-600">Choose up to three unlocked titles to display below your name.</p>
    </header>

    <form method="post" action="{{ route('profile.showcase-titles.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        @php($availableShowcaseTitles = array_diff_key($unlockedTitles, [$user->selected_title => true]))
        @if(empty($availableShowcaseTitles))
            <p class="text-sm text-gray-600">Unlock more titles to add them to your showcase.</p>
        @else
            <div class="showcase-title-options">
                @foreach($availableShowcaseTitles as $key => $title)
                    <label class="showcase-title-option">
                        <input type="checkbox" name="showcase_titles[]" value="{{ $key }}" @checked(in_array($key, old('showcase_titles', $showcaseTitleKeys), true))>
                        <span>{{ $title['label'] }}</span>
                    </label>
                @endforeach
            </div>
        @endif

        <x-input-error class="mt-2" :messages="$errors->get('showcase_titles')" />
        <x-input-error class="mt-2" :messages="$errors->get('showcase_titles.*')" />
        <x-primary-button>{{ __('Save showcased titles') }}</x-primary-button>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.showcase-title-option input[type="checkbox"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const selected = Array.from(checkboxes).filter(item => item.checked);
                if (selected.length > 3) checkbox.checked = false;
            });
        });
    });
</script>