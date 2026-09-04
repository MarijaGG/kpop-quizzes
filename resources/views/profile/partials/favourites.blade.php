<section class="profile-favourites" data-favourites>
    <div class="profile-section-heading profile-favourites-heading">
        <h2>My Top 3</h2>
        <button type="button" class="btn btn-ghost" data-favourites-toggle aria-expanded="false">Edit favourites</button>
    </div>

    <div class="favourite-tabs" role="tablist" aria-label="Favourite categories">
        <button type="button" class="favourite-tab is-active" role="tab" aria-selected="true" data-favourite-tab="group">Groups</button>
        <button type="button" class="favourite-tab" role="tab" aria-selected="false" data-favourite-tab="member">Idols</button>
        <button type="button" class="favourite-tab" role="tab" aria-selected="false" data-favourite-tab="album">Albums</button>
    </div>

    @foreach(['group' => 'Groups', 'member' => 'Idols', 'album' => 'Albums'] as $type => $label)
        <div class="favourite-panel {{ $type === 'group' ? 'is-active' : '' }}" role="tabpanel" data-favourite-panel="{{ $type }}">
            @php($items = $favorites[$type] ?? [])
            @if(!empty($items))
                <div class="favourite-ranked-grid favourite-ranked-{{ $type }}">
                    @foreach([1, 2, 3] as $position)
                        @if(isset($items[$position]))
                            @php($item = $items[$position])
                            <article class="favourite-ranked-card favourite-rank-{{ $position }}">
                                <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['name'] ?? $item['title'] }}">
                                <div class="favourite-ranked-copy">
                                    <span class="favourite-rank">#{{ $position }}</span>
                                    <h3>{{ $item['name'] ?? $item['title'] }}</h3>
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="muted favourite-empty">No favourite {{ strtolower($label) }} selected yet.</p>
            @endif
        </div>
    @endforeach

    <div class="favourites-editor" data-favourites-editor hidden>
        <form method="post" action="{{ route('profile.favourites.update') }}">
            @csrf
            @method('patch')

            <div class="favourites-editor-group">
                <h3>Top groups</h3>
                <div class="favourites-ranked-fields">
                    @foreach([1, 2, 3] as $position)
                        <div>
                            <x-input-label for="favorite_group_{{ $position }}" :value="__('Favourite group #'.$position)" />
                            <select id="favorite_group_{{ $position }}" name="favorite_groups[]" class="favourite-select favourite-group-select" @if($position === 1) data-primary-favourite-group @endif>
                                <option value="">Not selected</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group['id'] }}" @selected((int) ($favorites['group'][$position]['id'] ?? 0) === (int) $group['id'])>{{ $group['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="favourites-editor-group">
                <div class="favourites-editor-title">
                    <h3>Top idols</h3>
                </div>
                <div class="favourites-ranked-fields">
                    @foreach([1, 2, 3] as $position)
                        <div>
                            <x-input-label for="favorite_member_{{ $position }}" :value="__('Favourite idol #'.$position)" />
                            <select id="favorite_member_{{ $position }}" name="favorite_members[]" class="favourite-select favourite-filtered-select">
                                <option value="">Not selected</option>
                                @foreach($members as $member)
                                    <option value="{{ $member['id'] }}" data-group="{{ $member['group_id'] ?? '' }}" @selected((int) ($favorites['member'][$position]['id'] ?? 0) === (int) $member['id'])>{{ $member['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="favourites-editor-group">
                <h3>Top albums</h3>
                <div class="favourites-ranked-fields">
                    @foreach([1, 2, 3] as $position)
                        <div>
                            <x-input-label for="favorite_album_{{ $position }}" :value="__('Favourite album #'.$position)" />
                            <select id="favorite_album_{{ $position }}" name="favorite_albums[]" class="favourite-select favourite-filtered-select">
                                <option value="">Not selected</option>
                                @foreach($albums as $album)
                                    <option value="{{ $album['id'] }}" data-group="{{ $album['group_id'] ?? '' }}" @selected((int) ($favorites['album'][$position]['id'] ?? 0) === (int) $album['id'])>{{ $album['title'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-input-error class="mt-2" :messages="$errors->get('favorite_groups')" />
            <x-input-error class="mt-2" :messages="$errors->get('favorite_members')" />
            <x-input-error class="mt-2" :messages="$errors->get('favorite_albums')" />
            <button type="submit" class="btn btn-primary">Save top 3</button>
        </form>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const section = document.querySelector('[data-favourites]');
        if (!section) return;

        const toggle = section.querySelector('[data-favourites-toggle]');
        const editor = section.querySelector('[data-favourites-editor]');
        const primaryGroup = section.querySelector('[data-primary-favourite-group]');
        const tabs = section.querySelectorAll('[data-favourite-tab]');
        const panels = section.querySelectorAll('[data-favourite-panel]');
        const selects = section.querySelectorAll('.favourite-filtered-select');

        toggle.addEventListener('click', function () {
            editor.hidden = !editor.hidden;
            toggle.setAttribute('aria-expanded', String(!editor.hidden));
        });

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(item => {
                    const active = item === tab;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', String(active));
                });
                panels.forEach(panel => panel.classList.toggle('is-active', panel.dataset.favouritePanel === tab.dataset.favouriteTab));
            });
        });

        function filterFavouriteItems() {
            const groupId = primaryGroup.value;
            selects.forEach(function (select) {
                Array.from(select.options).forEach(function (option, index) {
                    if (index > 0) option.hidden = groupId !== '' && option.dataset.group !== groupId;
                });
            });
        }

        primaryGroup.addEventListener('change', function () {
            filterFavouriteItems();
        });
        filterFavouriteItems();
    });
</script>
