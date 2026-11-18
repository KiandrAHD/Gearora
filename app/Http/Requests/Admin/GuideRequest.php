<?php

namespace App\Http\Requests\Admin;

use App\Enums\GuideStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $existing = $this->route('guide');

        // Slug tetap stabil setelah dibuat: mengubah judul TIDAK mengubah URL
        // yang sudah dipublikasikan kecuali Admin mengisi slug baru secara eksplisit.
        $slug = $this->filled('slug')
            ? Str::slug((string) $this->slug)
            : ($existing?->slug ?? Str::slug((string) $this->title));

        $this->merge(['slug' => $slug]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $guideId = $this->route('guide')?->getKey();
        $coverMimes = implode(',', (array) config('gearora.guides.cover_mimes', ['image/jpeg', 'image/png', 'image/webp']));
        $maxContent = (int) config('gearora.guides.max_content_kb', 200) * 1024;

        return [
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'slug' => ['required', 'string', 'max:220', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('equipment_guides', 'slug')->ignore($guideId)],
            'guide_category_id' => ['nullable', 'integer', 'exists:guide_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:'.$maxContent],
            'cover' => ['nullable', 'image', 'mimetypes:'.$coverMimes, 'max:'.(int) config('gearora.guides.cover_max_kb', 4096)],
            'status' => ['required', 'string', Rule::in([GuideStatus::Draft->value, GuideStatus::Published->value])],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'sources' => ['nullable', 'array', 'max:20'],
            'sources.*.source_title' => ['required_with:sources', 'string', 'max:240'],
            'sources.*.publisher' => ['nullable', 'string', 'max:160'],
            'sources.*.source_url' => ['nullable', 'url', 'max:500'],
            'sources.*.accessed_at' => ['nullable', 'date'],
            'sources.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cover.image' => 'Sampul harus berupa gambar yang valid.',
            'cover.mimetypes' => 'Format sampul harus JPG, PNG, atau WEBP.',
            'content.max' => 'Konten terlalu panjang.',
            'sources.*.source_url.url' => 'URL sumber tidak valid.',
        ];
    }
}
