<div class="showcase-title-settings">
        <h4 class="text-sm font-medium text-gray-900">Showcase titles</h4>
        <input type="hidden" name="_showcase_titles_present" value="1">
        @php
            $mainTitle = old('selected_title', $user->selected_title);
            $availableShowcaseTitles = array_diff_key($unlockedTitles, [$mainTitle => true]);
            $initialShowcaseTitles = old('showcase_titles', $showcaseTitleKeys);
        @endphp
        @if(empty($availableShowcaseTitles))
            <p class="text-sm text-gray-600">Unlock more titles to add them to your showcase.</p>
        @else
            <div class="showcase-title-picker">
                <div class="showcase-title-picker-row">
                    <select id="showcase-title-picker" class="form-control">
                        <option value="">Select an unlocked title</option>
                        @foreach($availableShowcaseTitles as $key => $title)
                            <option value="{{ $key }}">{{ $title['label'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="add-showcase-title" class="btn btn-ghost" disabled>Add title</button>
                </div>
                <div id="showcase-title-list" class="showcase-title-list" aria-live="polite"></div>
                <p id="showcase-title-limit" class="muted" hidden>You can showcase up to three titles.</p>
                <script type="application/json" id="initial-showcase-titles">@json(array_values($initialShowcaseTitles))</script>
            </div>
        @endif

        <x-input-error class="mt-2" :messages="$errors->get('showcase_titles')" />
        <x-input-error class="mt-2" :messages="$errors->get('showcase_titles.*')" />
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const picker = document.getElementById('showcase-title-picker');
        if (!picker) return;

        const addButton = document.getElementById('add-showcase-title');
        const list = document.getElementById('showcase-title-list');
        const limitMessage = document.getElementById('showcase-title-limit');
        const mainTitle = document.getElementById('selected_title');
        const labels = new Map(Array.from(picker.options)
            .filter(option => option.value)
            .map(option => [option.value, option.textContent.trim()]));
        const initialTitles = JSON.parse(document.getElementById('initial-showcase-titles').textContent);
        let selectedTitles = initialTitles.filter(key => labels.has(key)).slice(0, 3);

        function renderTitles() {
            list.replaceChildren();
            selectedTitles.forEach(function (key) {
                const row = document.createElement('div');
                row.className = 'showcase-title-row';

                const label = document.createElement('span');
                label.textContent = labels.get(key);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'showcase_titles[]';
                hidden.value = key;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-ghost';
                removeButton.textContent = 'Remove';
                removeButton.setAttribute('aria-label', `Remove ${labels.get(key)}`);
                removeButton.addEventListener('click', function () {
                    selectedTitles = selectedTitles.filter(item => item !== key);
                    renderTitles();
                });

                row.append(label, hidden, removeButton);
                list.appendChild(row);
            });

            picker.querySelectorAll('option').forEach(function (option) {
                option.disabled = selectedTitles.includes(option.value);
            });
            limitMessage.hidden = selectedTitles.length < 3;
            addButton.disabled = selectedTitles.length >= 3 || !picker.value;
        }

        picker.addEventListener('change', renderTitles);
        mainTitle.addEventListener('change', function () {
            selectedTitles = selectedTitles.filter(key => key !== mainTitle.value);
            renderTitles();
        });
        addButton.addEventListener('click', function () {
            if (picker.value && !selectedTitles.includes(picker.value) && selectedTitles.length < 3) {
                selectedTitles.push(picker.value);
                picker.value = '';
                renderTitles();
            }
        });

        renderTitles();
    });
</script>