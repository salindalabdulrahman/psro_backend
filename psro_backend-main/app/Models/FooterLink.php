<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterLink extends Model
{
    protected $fillable = [
        'group',
        'name',
        'url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function scopeMinistries($query)
    {
        return $query->where('group', 'ministry')->orderBy('sort_order');
    }

    public function scopeAgencies($query)
    {
        return $query->where('group', 'agency')->orderBy('sort_order');
    }
}
