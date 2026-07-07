<?php

namespace App\Notifications;

use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengaduanBaruNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Pengaduan $pengaduan,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Pengaduan Baru: :judul', ['judul' => $this->pengaduan->judul]))
            ->greeting(__('Ada pengaduan baru dari masyarakat.'))
            ->line(__('Pelapor: :nama', ['nama' => $this->pengaduan->user?->name ?? __('Masyarakat')]))
            ->line(__('Judul: :judul', ['judul' => $this->pengaduan->judul]))
            ->line(__('Status: :status', ['status' => $this->pengaduan->status]))
            ->line(__('Visibilitas: :visibilitas', ['visibilitas' => $this->pengaduan->visibilitas]))
            ->line(str($this->pengaduan->isi_pengaduan)->limit(180)->toString())
            ->action(__('Lihat Pengaduan'), route('pengaduan.show', $this->pengaduan))
            ->line(__('Silakan cek dan tindak lanjuti pengaduan ini melalui sistem.'));
    }
}
