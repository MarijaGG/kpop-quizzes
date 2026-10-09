<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyRelation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const TITLES = [
        'niki-number-one-fan' => [
            'label' => "Ni-ki's #1 Fan",
            'member_id' => 7,
        ],
        'hyunjin-number-one-fan' => [
            'label' => "Hyunjin's #1 Fan",
            'member_id' => 16,
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'bio',
        'selected_title',
        'showcase_titles',
        'email',
        'avatar_member_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'showcase_titles' => 'array',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function quizResults(): HasMany
    {
        return $this->hasMany(QuizResult::class);
    }

    public function titleAwards(): HasMany
    {
        return $this->hasMany(UserTitle::class);
    }

    public function favorites(): HasManyRelation
    {
        return $this->hasMany(UserFavorite::class)->orderBy('position');
    }

    public function avatarMember(): ?array
    {
        if (! $this->avatar_member_id) {
            return null;
        }

        return Member::find($this->avatar_member_id)?->toArray();
    }

    public function isAdmin(): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', 'admin');
        }

        return $this->roles()->where('name', 'admin')->exists();
    }

    public function unlockedTitles(): array
    {
        $awardedTitles = $this->titleAwards()->with('title')->get()->mapWithKeys(function (UserTitle $award) {
            $label = $award->title?->title_label ?? self::TITLES[$award->title?->title_key]['label'] ?? null;

            return $label ? [$award->title->title_key => ['label' => $label]] : [];
        })->all();
        $completedMemberQuizIds = $this->quizResults()
            ->where('result_type', 'knowledge')
            ->where('total_questions', '>', 0)
            ->whereColumn('correct_answers', 'total_questions')
            ->whereHas('quiz', fn ($query) => $query->whereIn('member_id', collect(self::TITLES)->pluck('member_id')))
            ->with('quiz')
            ->get()
            ->map(fn (QuizResult $result) => $result->quiz?->member_id)
            ->filter()
            ->map(fn ($memberId) => (int) $memberId)
            ->unique()
            ->all();

        $legacyTitles = array_filter(
            self::TITLES,
            fn (array $title) => in_array($title['member_id'], $completedMemberQuizIds, true),
        );

        return $awardedTitles + $legacyTitles;
    }

    public function awardTitleForQuizResult(Quiz $quiz, string $resultType, object $result, ?int $correctAnswers = null, ?int $totalQuestions = null): ?UserTitle
    {
        if ($resultType === 'percent') {
            if (! $quiz->member_id || ! $totalQuestions || $correctAnswers !== $totalQuestions) {
                return null;
            }

            $titleKey = collect(self::TITLES)
                ->filter(fn (array $title) => (int) $title['member_id'] === (int) $quiz->member_id)
                ->keys()
                ->first() ?? 'member-'.$quiz->member_id.'-number-one-fan';
            $titleLabel = "{$result->name}'s #1 Fan";
        } elseif ($resultType === 'member') {
            $titleKey = 'member-'.$result->id.'-twin';
            $titleLabel = "{$result->name}'s Twin";
        } elseif ($resultType === 'album') {
            $titleKey = 'album-'.$result->id.'-enjoyer';
            $titleLabel = "{$result->title} Enjoyer";
        } elseif ($resultType === 'group') {
            $titleKey = 'group-'.$result->id.'-loyalist';
            $titleLabel = "{$result->name} Loyalist";
        } else {
            return null;
        }

        return $this->awardTitle($titleKey, $titleLabel);
    }

    public function awardTitle(string $titleKey, string $titleLabel): ?UserTitle
    {
        $title = Title::firstOrCreate(
            ['title_key' => $titleKey],
            ['title_label' => $titleLabel],
        );

        $award = $this->titleAwards()->firstOrCreate(
            ['title_id' => $title->id],
            ['awarded_at' => now()],
        );

        return $award->wasRecentlyCreated ? $award->load('title') : null;
    }

    public function selectedTitleLabel(): ?string
    {
        return $this->titleAwards()
            ->whereHas('title', fn ($query) => $query->where('title_key', $this->selected_title))
            ->with('title')
            ->first()?->title?->title_label
            ?? self::TITLES[$this->selected_title]['label']
            ?? null;
    }

    public function selectedTitleHue(): int
    {
        return (int) (crc32((string) $this->selected_title) % 360);
    }
}
