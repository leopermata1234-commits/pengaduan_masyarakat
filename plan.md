# Project Plan - Sistem Informasi Layanan Masyarakat Banjar Puluk-Puluk

## Deskripsi Project

Membangun Sistem Informasi Layanan Masyarakat Banjar Puluk-Puluk berbasis website menggunakan **Laravel 13**, **Laravel Livewire**, **Tailwind CSS**, dan **Spatie Laravel Permission**.

Sistem digunakan untuk membantu pengelolaan layanan masyarakat, pengaduan, informasi kegiatan, dan dokumentasi kegiatan.

---

# Tech Stack

- Laravel 13
- PHP 8.3+
- Livewire 4
- Tailwind CSS
- Alpine.js
- MySQL
- Laravel Spatie Permission
- Laravel Storage
- Vite

---

# Package

## Wajib

- livewire/livewire
- spatie/laravel-permission

---

# Authentication

Gunakan authentication bawaan Laravel.

User harus login untuk mengakses dashboard.

---

# Role

Gunakan Spatie Permission.

Role yang digunakan:

- Super Admin
- Admin
- Bendesa Adat
- Masyarakat

---

# Permission

Permission dibuat per halaman, bukan hanya CRUD.

Contoh:

Dashboard

- dashboard.view

Users

- users.view
- users.create
- users.edit
- users.delete

Pengaduan

- pengaduan.view
- pengaduan.create
- pengaduan.edit
- pengaduan.delete
- pengaduan.respond
- pengaduan.verify

Informasi Kegiatan

- program.view
- program.create
- program.edit
- program.delete

Dokumentasi

- dokumentasi.view
- dokumentasi.create
- dokumentasi.edit
- dokumentasi.delete

Role

- role.view
- role.create
- role.edit
- role.delete

Permission

- permission.view
- permission.create
- permission.edit
- permission.delete

---

# Hak Akses

## Super Admin

Full Access.

## Admin

- Kelola Pengaduan
- Kelola Informasi Kegiatan
- Kelola Dokumentasi

Tidak dapat mengelola Role dan Permission.

## Bendesa Adat

- Dashboard
- Melihat seluruh data
- Verifikasi Informasi Kegiatan
- Verifikasi Dokumentasi
- Monitoring Pengaduan

Tidak dapat menghapus data.

## Masyarakat

- Dashboard
- Membuat Pengaduan
- Melihat Status Pengaduan
- Melihat Program
- Melihat Dokumentasi

---

# Database

## users

- id
- name
- email
- password
- phone
- created_at
- updated_at

Menggunakan tabel bawaan Spatie:

- roles
- permissions
- model_has_roles
- model_has_permissions
- role_has_permissions

---

## pengaduan

- id
- user_id
- judul
- isi_pengaduan
- foto
- status
- created_at
- updated_at

Status:

- Pending
- Diproses
- Ditolak
- Selesai

---

## tanggapan_pengaduan

- id
- pengaduan_id
- admin_id
- isi_tanggapan
- created_at
- updated_at

---

## program_banjar

- id
- user_id
- judul
- deskripsi
- tanggal
- gambar
- status

Status

- Draft
- Published

---

## dokumentasi_kegiatan

- id
- user_id
- judul
- deskripsi
- tanggal
- foto

---

# Relationship

User

- hasMany Pengaduan
- hasMany Program
- hasMany Dokumentasi

Pengaduan

- belongsTo User
- hasMany Tanggapan

Tanggapan

- belongsTo Pengaduan
- belongsTo User(Admin)

Program

- belongsTo User

Dokumentasi

- belongsTo User

---

# Folder Structure

Gunakan struktur Livewire.

Contoh:

app/
    Livewire/
        Dashboard/

        Users/
            Index.php
            Create.php
            Edit.php

        Roles/
            Index.php
            Create.php
            Edit.php

        Permissions/
            Index.php
            Create.php
            Edit.php

        Pengaduan/
            Index.php
            Create.php
            Edit.php
            Show.php

        Program/
            Index.php
            Create.php
            Edit.php

        Dokumentasi/
            Index.php
            Create.php
            Edit.php

resources/
    views/
        livewire/
            dashboard/

            users/
                index.blade.php
                create.blade.php
                edit.blade.php

            roles/
                index.blade.php
                create.blade.php
                edit.blade.php

            permissions/
                index.blade.php
                create.blade.php
                edit.blade.php

            pengaduan/
                index.blade.php
                create.blade.php
                edit.blade.php
                show.blade.php

            program/
                index.blade.php
                create.blade.php
                edit.blade.php

            dokumentasi/
                index.blade.php
                create.blade.php
                edit.blade.php

---

# Routing

Gunakan route Livewire.

Contoh:

/dashboard

/users
/users/create
/users/{id}/edit

/roles
/roles/create
/roles/{id}/edit

/permissions
/permissions/create
/permissions/{id}/edit

/pengaduan
/pengaduan/create
/pengaduan/{id}
/pengaduan/{id}/edit

/program
/program/create
/program/{id}/edit

/dokumentasi
/dokumentasi/create
/dokumentasi/{id}/edit

---

# UI Rules

Semua halaman menggunakan layout yang sama.

Setiap menu memiliki halaman sendiri.

WAJIB dipisahkan menjadi:

- Index
- Create
- Edit

JANGAN menggunakan modal CRUD.

JANGAN menggabungkan form create/edit ke halaman index.

Setiap halaman menggunakan:

- Card
- Breadcrumb
- Page Title
- Action Button

Gunakan pagination.

Gunakan search.

Gunakan filter bila diperlukan.

---

# Validasi

Semua form wajib menggunakan validasi Laravel.

Gunakan Form Request atau Livewire Validation.

---

# Upload File

Gunakan Storage Laravel.

Folder:

storage/app/public

Subfolder:

pengaduan/

program/

dokumentasi/

---

# Dashboard

Dashboard menampilkan statistik:

- Total User
- Total Pengaduan
- Pengaduan Pending
- Pengaduan Diproses
- Pengaduan Selesai
- Total Informasi Kegiatan
- Total Dokumentasi

---

# Pengaduan Flow

Masyarakat

↓

Create Pengaduan

↓

Status Pending

↓

Admin melihat daftar

↓

Admin memberi tanggapan

↓

Status Diproses

↓

Admin menyelesaikan

↓

Status Selesai

---

# Informasi Kegiatan Flow

Admin

↓

Create Informasi Kegiatan

↓

Bendesa melihat

↓

Published

↓

Masyarakat melihat

---

# Dokumentasi Flow

Admin

↓

Pilih Informasi Kegiatan dengan status Published

↓

Upload satu atau lebih foto bukti kegiatan

↓

Status Informasi Kegiatan menjadi Selesai

↓

Dokumentasi tersimpan

↓

Masyarakat melihat

---

# Coding Rules

- Gunakan Eloquent Relationship.
- Hindari Query Builder jika tidak diperlukan.
- Gunakan Policy dan Permission Spatie untuk otorisasi.
- Semua aksi harus melalui Gate atau middleware `permission`.
- Gunakan route name.
- Gunakan eager loading (`with()`).
- Gunakan soft delete bila diperlukan.
- Ikuti standar PSR-12.
- Berikan komentar hanya pada logika yang kompleks.

---

# Target

Aplikasi harus memiliki:

- Authentication
- Dashboard
- User Management
- Role Management
- Permission Management
- Pengaduan Management
- Informasi Kegiatan Management
- Dokumentasi Management
- Responsive UI
- Livewire CRUD
- Permission berbasis halaman menggunakan Spatie
- Struktur kode yang bersih, modular, dan mudah dikembangkan.
