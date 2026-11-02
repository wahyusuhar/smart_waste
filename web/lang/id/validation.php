<?php

// Only the rules used in this application are translated; anything else falls back to English.
return [
    'between' => [
        'numeric' => ':Attribute harus bernilai antara :min dan :max.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'file' => ':Attribute harus berupa file.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'max' => [
        'file' => ':Attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => ':Attribute tidak boleh lebih dari :max.',
        'string' => ':Attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa file bertipe: :values.',
    'min' => [
        'string' => ':Attribute minimal :min karakter.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'required' => ':Attribute wajib diisi.',
    'required_with' => ':Attribute wajib diisi jika :values diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah terdaftar.',
    'uploaded' => ':Attribute gagal diunggah.',

    'attributes' => [
        'ai_confidence' => 'confidence AI',
        'ai_label' => 'label AI',
        'category' => 'kategori',
        'description' => 'deskripsi',
        'email' => 'email',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'name' => 'nama',
        'password' => 'password',
        'phone' => 'nomor HP',
        'photo' => 'foto',
        'status' => 'status',
    ],
];
