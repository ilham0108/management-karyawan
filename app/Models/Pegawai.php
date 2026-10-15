<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Pegawai extends Model
{
    protected $table = 'pegawais';

    protected $fillable = [
        'nama',
        'jabatan_id',
        'npp',
        'status',
        'profile_photo',
        'qr_code',
    ];

    public function getRouteKey(): string
    {
        $encryptedId = Crypt::encryptString((string) parent::getRouteKey());

        return rtrim(strtr(base64_encode($encryptedId), '+/', '-_'), '=');
    }

    /**
     * Resolve encrypted route keys while preserving normal 404 behavior for invalid keys.
     *
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if (! is_string($value) || preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
            return null;
        }

        $encryptedId = base64_decode(strtr($value, '-_', '+/'), true);

        if ($encryptedId === false) {
            return null;
        }

        try {
            $id = Crypt::decryptString($encryptedId);
        } catch (DecryptException) {
            return null;
        }

        if (! ctype_digit($id)) {
            return null;
        }

        return $this->resolveRouteBindingQuery($this, $id, $field)->first();
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }
}
