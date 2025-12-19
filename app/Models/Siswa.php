<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;
    protected $table = 'tbl_siswa';
    protected $primaryKey = 'id_siswa';

    protected $fillable = [
        'nis', 'nama_siswa', 'kelas', 'jurusan', 'gender'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id_siswa');
    }
    
    public function pelanggan()
    {
        return $this->hasOne(Pelanggan::class, 'id_siswa');
    }
}
