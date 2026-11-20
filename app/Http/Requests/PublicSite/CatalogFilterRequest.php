<?php

namespace App\Http\Requests\PublicSite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi filter katalog publik (sewa & beli).
 *
 * Semua nilai di-whitelist agar tidak ada query string liar yang masuk ke
 * builder Eloquent. Mode dibatasi ke `sewa`/`beli`; nilai lain dipaksa `sewa`.
 */
class CatalogFilterRequest extends FormRequest
{
    /** Urutan yang benar-benar didukung data (bukan rating/popularitas palsu). */
    public const SORTS = ['unggulan', 'terbaru', 'nama', 'harga_terendah', 'harga_tertinggi'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mode = (string) $this->query('mode', 'sewa');

        $this->merge([
            'mode' => in_array($mode, ['sewa', 'beli'], true) ? $mode : 'sewa',
            'q' => is_string($this->query('q')) ? trim($this->query('q')) : null,
            'kategori' => is_string($this->query('kategori')) ? trim($this->query('kategori')) : null,
            'sort' => in_array($this->query('sort'), self::SORTS, true) ? $this->query('sort') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'kategori' => ['nullable', 'string', 'max:160'],
            'mode' => ['required', Rule::in(['sewa', 'beli'])],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'min_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'stock' => ['nullable', Rule::in(['tersedia'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function mode(): string
    {
        return (string) ($this->validated('mode') ?? 'sewa');
    }

    public function isRetail(): bool
    {
        return $this->mode() === 'beli';
    }
}
