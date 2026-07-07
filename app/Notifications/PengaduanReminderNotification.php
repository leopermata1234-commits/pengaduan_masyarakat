<?php

namespace App\Notifications;

use App\Models\Pengaduan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengaduanReminderNotification extends Notification implements ShouldQueue
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
            ->subject(__('Pengingat Pengaduan: :judul', ['judul' => $this->pengaduan->judul]))
            ->greeting(__('Masyarakat mengirim pengingat pengaduan.'))
            ->line(__('Pelapor: :nama', ['nama' => $this->pengaduan->user?->name ?? __('Masyarakat')]))
            ->line(__('Judul: :judul', ['judul' => $this->pengaduan->judul]))
            ->line(__('Status saat ini: :status', ['status' => $this->pengaduan->status]))
            ->line(__('Pengaduan ini masih menunggu tindak lanjut.'))
            ->line(str($this->pengaduan->isi_pengaduan)->limit(180)->toString())
            ->action(__('Lihat Pengaduan'), route('pengaduan.show', $this->pengaduan))
            ->line(__('Silakan cek kembali pengaduan ini melalui sistem.'));
    }
}
