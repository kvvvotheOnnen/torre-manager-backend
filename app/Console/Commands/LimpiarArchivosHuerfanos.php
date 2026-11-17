<?php

namespace App\Console\Commands;

use App\Models\Paquete;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class LimpiarArchivosHuerfanos extends Command
{
    protected $signature = 'archivos:limpiar-huerfanos
                            {--dry-run : Lista los archivos que se eliminarían sin borrarlos}';

    protected $description = 'Elimina archivos almacenados que ningún registro referencia';

    public function handle(): int
    {
        $disco = Storage::disk((string) config('archivos.disco', 'public'));
        $referenciadas = $this->rutasReferenciadas();
        $huerfanos = [];

        foreach ((array) config('archivos.carpetas', []) as $carpeta) {
            foreach ($disco->allFiles((string) $carpeta) as $ruta) {
                if (! in_array($ruta, $referenciadas, true)) {
                    $huerfanos[] = $ruta;
                }
            }
        }

        if ($huerfanos === []) {
            $this->info('No hay archivos huérfanos.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(['Archivo huérfano'], array_map(fn (string $ruta): array => [$ruta], $huerfanos));
            $this->info(count($huerfanos).' archivo(s) se eliminarían.');

            return self::SUCCESS;
        }

        foreach ($huerfanos as $ruta) {
            $disco->delete($ruta);
        }

        $this->info(count($huerfanos).' archivo(s) eliminados.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function rutasReferenciadas(): array
    {
        $rutas = Paquete::query()
            ->whereNotNull('foto_path')
            ->pluck('foto_path')
            ->all();

        if (Schema::hasTable('trabajadores_servicio')) {
            $rutas = array_merge($rutas, DB::table('trabajadores_servicio')
                ->whereNotNull('foto_path')
                ->pluck('foto_path')
                ->all());
        }

        $rutas = array_map(static fn (mixed $ruta): string => (string) $ruta, $rutas);

        return array_values(array_unique(array_filter($rutas, static fn (string $ruta): bool => trim($ruta) !== '')));
    }
}
