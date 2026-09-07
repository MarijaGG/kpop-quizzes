@php($editing = $image !== null)
<form method="POST" action="{{ $editing ? route('admin.guess-idol-images.update', $image) : route('admin.guess-idol-images.store') }}" enctype="multipart/form-data">
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label class="form-label">Group</label>
        <select name="group_id" id="guess-group" class="form-control" required>
            <option value="">Select group</option>
            @foreach($groups as $group)
                <option value="{{ $group['id'] }}" @selected((string) old('group_id', $image->group_id ?? '') === (string) $group['id'])>{{ $group['name'] }}</option>
            @endforeach
        </select>
        @error('group_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Member</label>
        <select name="member_id" id="guess-member" class="form-control" required>
            <option value="">Select member</option>
            @foreach($members as $member)
                <option value="{{ $member['id'] }}" data-group="{{ $member['group_id'] ?? '' }}" @selected((string) old('member_id', $image->member_id ?? '') === (string) $member['id'])>{{ $member['name'] }}</option>
            @endforeach
        </select>
        @error('member_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Difficulty</label>
        <select name="difficulty" class="form-control" required>
            @foreach(['easy', 'medium', 'hard'] as $level)
                <option value="{{ $level }}" @selected(old('difficulty', $image->difficulty ?? '') === $level)>{{ ucfirst($level) }}</option>
            @endforeach
        </select>
        @error('difficulty')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Image</label>
        @if($editing)
            <img src="{{ asset('storage/'.$image->image) }}" alt="Current image" style="width:10rem;aspect-ratio:1;object-fit:cover;margin-bottom:.75rem;">
        @endif
        <input type="file" name="image" class="form-control" accept="image/*" {{ $editing ? '' : 'required' }}>
        @error('image')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Save' }}</button>
</form>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const group = document.getElementById('guess-group');
        const member = document.getElementById('guess-member');
        function filterMembers() {
            Array.from(member.options).forEach(function (option, index) {
                if (index > 0) option.hidden = group.value !== '' && option.dataset.group !== group.value;
            });
            if (member.selectedOptions[0] && member.selectedOptions[0].hidden) member.value = '';
        }
        group.addEventListener('change', filterMembers);
        filterMembers();
    });
</script>
