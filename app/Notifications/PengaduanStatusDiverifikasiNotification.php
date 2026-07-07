<?php

namespace App\Notifications;

use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengaduanStatusDiverifikasiNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Pengaduan $pengaduan,
        private readonly string $statusSebelumnya,
        private readonly ?User $verifikator,
        private readonly ?string $alasanPenolakan = null,
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
        $mail = (new MailMessage)
            ->subject(__('Status Pengaduan Diperbarui: :judul', ['judul' => $this->pengaduan->judul]))
            ->greeting(__('Status pengaduan Anda sudah diperbarui.'))
            ->line(__('Judul: :judul', ['judul' => $this->pengaduan->judul]))
            ->line(__('Status sebelumnya: :status', ['status' => $this->statusSebelumnya]))
            ->line(__('Status sekarang: :status', ['status' => $this->pengaduan->status]))
            ->line(__('Diverifikasi oleh: :nama', ['nama' => $this->verifikator?->name ?? __('Petugas')]));

        if ($this->alasanPenolakan) {
            $mail->line(__('Alasan penolakan: :alasan', ['alasan' => $this->alasanPenolakan]));
        }

        return $mail
            ->action(__('Lihat Pengaduan'), route('pengaduan.show', $this->pengaduan))
            ->line(__('Terima kasih sudah menggunakan layanan pengaduan masyarakat.'));
    }
}
