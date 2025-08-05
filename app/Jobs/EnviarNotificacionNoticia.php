<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Noticia;
use App\Notifications\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class EnviarNotificacionNoticia implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $rolId,
        protected Noticia $noticia
    ) {}

    public function handle(): void
    {
        $usuarios = User::where('Rol', $this->rolId)->get();

        $usuarios->chunk(100)->each(function ($chunk) {
            Notification::send($chunk, new Notificacion(
                $this->noticia->Titulo,
            ));

            sleep(1);
        });
    }
}
