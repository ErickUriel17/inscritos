<?php
// ============================================================
// Clase Admin — Gestión de usuarios administradores
// Archivo: assets/sentenciasSQL/admin.php
// CAMBIOS: se agregó campo 'nombre' en el SELECT de leerAdmin
//          para mostrarlo en el dashboard; se corrigió el campo
//          'idAdmin' → 'id_usuario' en actualizarAdmin para
//          consistencia con el esquema real de la tabla.
// ============================================================
class Admin {

    /** Valida credenciales y retorna los datos del admin o false. */
    public function leerAdmin(string $usuario, string $contrasena): array|false {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->prepare(
            "SELECT id_usuario, usuario, nombre, contrasena
             FROM usuarios
             WHERE usuario = :usuario AND contrasena = :contrasena
             LIMIT 1"
        );
        $stmt->execute([
            ':usuario'    => $usuario,
            ':contrasena' => $contrasena,
        ]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin ?: false;
    }

    /** Actualiza usuario y contraseña de un admin. */
    public function actualizarAdmin(int $id, string $usuario, string $contrasena): bool {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->prepare(
            "UPDATE usuarios
             SET usuario = :usuario, contrasena = :contrasena
             WHERE id_usuario = :id"
        );
        return $stmt->execute([
            ':usuario'    => $usuario,
            ':contrasena' => $contrasena,
            ':id'         => $id,
        ]);
    }

    /** Obtiene los datos de un admin por ID. */
    public function obtenerAdminPorId(int $id): array|false {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->prepare(
            "SELECT id_usuario, usuario, nombre FROM usuarios WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
