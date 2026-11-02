<?php

namespace App\Modules\Blog\Models;

use App\Models\User;
use Database\Factories\Blog\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Blog post (SEO & blog module). Published posts are public; drafts are
 * admin-only.
 *
 * @property int $id
 * @property int|null $author_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body
 * @property string $status
 * @property string|null $cover_path
 * @property Carbon|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property-read User|null $author
 */
#[Fillable(['author_id', 'title', 'slug', 'excerpt', 'body', 'status', 'cover_path', 'published_at', 'meta_title', 'meta_description'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * Module factories are not auto-discoverable by the default path
     * guesser — resolve explicitly.
     */
    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null && ! $this->published_at->isFuture();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function fillFromAdmin(array $data, ?int $authorId): void
    {
        $this->fill($data);

        if ($this->author_id === null) {
            $this->author_id = $authorId;
        }

        if (($data['status'] ?? null) === 'published' && $this->published_at === null) {
            $this->published_at = now();
        }

        if (($data['status'] ?? null) !== 'published') {
            $this->published_at = null;
        }

        $this->slug = Str::slug($this->slug !== null ? $this->slug : $this->title);
        if ($this->slug === '') {
            $this->slug = 'post';
        }
    }
}
