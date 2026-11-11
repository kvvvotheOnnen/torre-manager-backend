<?php

namespace Features\Asistente\Data\Repositories;

use App\Models\Documento as DocumentoModel;
use App\Models\User;
use Features\Asistente\Domain\Entities\Documento;
use Features\Asistente\Domain\Repositories\DocumentoRepositoryInterface;
use Illuminate\Support\Facades\Storage;

final class DocumentoRepository implements DocumentoRepositoryInterface
{
    public function obtenerPorProyecto(string $proyectoId): ?Documento
    {
        $model = DocumentoModel::query()
            ->where('proyecto_id', $proyectoId)
            ->first();

        return $model === null ? null : $this->toEntity($model);
    }

    public function crear(
        string $proyectoId,
        ?string $subidoPor,
        string $nombreOriginal,
        string $ruta,
        int $paginas,
        string $texto,
        int $tamanoTokens,
    ): Documento {
        $model = DocumentoModel::query()->create([
            'proyecto_id' => $proyectoId,
            'subido_por' => $subidoPor,
            'nombre_original' => $nombreOriginal,
            'ruta' => $ruta,
            'paginas' => $paginas,
            'texto' => $texto,
            'tamano_tokens' => $tamanoTokens,
        ]);

        return $this->toEntity($model);
    }

    public function eliminar(Documento $documento): void
    {
        DocumentoModel::query()->whereKey($documento->id())->delete();

        if ($documento->ruta() !== '' && Storage::disk('local')->exists($documento->ruta())) {
            Storage::disk('local')->delete($documento->ruta());
        }
    }

    public function administradorTieneProyecto(string $usuarioId, string $proyectoId): bool
    {
        $usuario = User::query()->find($usuarioId);

        if ($usuario === null) {
            return false;
        }

        if ((string) $usuario->proyecto_id === $proyectoId) {
            return true;
        }

        return $usuario->proyectos()->whereKey($proyectoId)->exists();
    }

    public function primerProyectoDelAdministrador(string $usuarioId): ?string
    {
        $usuario = User::query()->find($usuarioId);

        if ($usuario === null) {
            return null;
        }

        $proyecto = $usuario->proyectos()->orderBy('nombre')->first();

        if ($proyecto !== null) {
            return (string) $proyecto->getKey();
        }

        return $usuario->proyecto_id === null ? null : (string) $usuario->proyecto_id;
    }

    private function toEntity(DocumentoModel $model): Documento
    {
        return new Documento(
            id: (string) $model->getKey(),
            proyectoId: (string) $model->proyecto_id,
            nombreOriginal: (string) $model->nombre_original,
            paginas: (int) $model->paginas,
            tamanoTokens: (int) $model->tamano_tokens,
            ruta: (string) $model->ruta,
            texto: (string) $model->texto,
        );
    }
}
