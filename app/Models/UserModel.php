<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'nama',
        'email',
        'username',
        'no_telepon',
        'password_hash',
        'role',
        'is_active',
    ];

    protected $useTimestamps = true;
}
