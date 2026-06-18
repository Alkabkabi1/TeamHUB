<?php

namespace App\Models;

use App\Enums\CertificateField;
use Database\Factories\CertificatePlaceholderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificatePlaceholder extends Model
{
    /** @use HasFactory<CertificatePlaceholderFactory> */
    use HasFactory;

    protected $fillable = [
        'certificate_template_id',
        'binding',
        'static_text',
        'x',
        'y',
        'width',
        'font_size',
        'font_family',
        'font_weight',
        'color',
        'align',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'binding' => CertificateField::class,
            'x' => 'float',
            'y' => 'float',
            'width' => 'float',
            'font_size' => 'float',
            'sort' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }
}
