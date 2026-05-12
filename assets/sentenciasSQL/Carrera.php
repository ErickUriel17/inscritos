<?php
// ============================================================
// Clase Carrera — Gestión de catálogo de carreras
// Archivo: assets/sentenciasSQL/Carrera.php
// NUEVO: clase completa, no existía en el proyecto original
// ============================================================
class Carrera {

    /** Retorna todas las carreras activas. */
    public function listarActivas(): array {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->query("SELECT * FROM carreras WHERE activa = 1 ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ============================================================
// Clase Beca — Gestión de catálogo de becas
// Archivo: assets/sentenciasSQL/Carrera.php (compartido)
// NUEVO: clase completa, no existía en el proyecto original
// ============================================================
class Beca {

    /** Retorna todas las becas activas. */
    public function listarActivas(): array {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->query("SELECT * FROM becas WHERE activa = 1 ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
