<?php
// ============================================================
// Clase Aspirante — CRUD completo para la tabla aspirantes
// Archivo: assets/sentenciasSQL/Aspirante.php
// NUEVO: clase completa, no existía en el proyecto original
// ============================================================
class Aspirante {

    /** Crea un nuevo aspirante. Retorna el ID insertado o false. */
    public function crear(array $datos): int|false {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->prepare(
            "INSERT INTO aspirantes (nombre, email, telefono, id_carrera, etapa, id_beca, descuento_aplicado, notas)
             VALUES (:nombre, :email, :telefono, :id_carrera, :etapa, :id_beca, :descuento, :notas)"
        );
        $ok = $stmt->execute([
            ':nombre'     => $datos['nombre'],
            ':email'      => $datos['email'],
            ':telefono'   => $datos['telefono']   ?? null,
            ':id_carrera' => $datos['id_carrera']  ?? null,
            ':etapa'      => $datos['etapa']       ?? 'Contacto',
            ':id_beca'    => $datos['id_beca']     ?? null,
            ':descuento'  => $datos['descuento']   ?? 0,
            ':notas'      => $datos['notas']       ?? null,
        ]);
        return $ok ? (int)$pdo->lastInsertId() : false;
    }

    /** Obtiene un aspirante por ID. Retorna array o false. */
    public function obtenerPorId(int $id): array|false {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->prepare(
            "SELECT a.*, c.nombre AS carrera, b.nombre AS beca
             FROM aspirantes a
             LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
             LEFT JOIN becas    b ON a.id_beca    = b.id_beca
             WHERE a.id_aspirante = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Lista todos los aspirantes con filtros opcionales. */
    public function listar(array $filtros = []): array {
        include __DIR__ . "/Conexion.php";

        $sql  = "SELECT a.*, c.nombre AS carrera, b.nombre AS beca
                 FROM aspirantes a
                 LEFT JOIN carreras c ON a.id_carrera = c.id_carrera
                 LEFT JOIN becas    b ON a.id_beca    = b.id_beca
                 WHERE 1=1";
        $params = [];

        if (!empty($filtros['etapa'])) {
            $sql .= " AND a.etapa = :etapa";
            $params[':etapa'] = $filtros['etapa'];
        }
        if (!empty($filtros['id_carrera'])) {
            $sql .= " AND a.id_carrera = :id_carrera";
            $params[':id_carrera'] = $filtros['id_carrera'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (a.nombre LIKE :b OR a.email LIKE :b2)";
            $like = '%' . $filtros['busqueda'] . '%';
            $params[':b']  = $like;
            $params[':b2'] = $like;
        }

        $sql .= " ORDER BY a.creado_en DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Actualiza un aspirante. Retorna bool. */
    public function actualizar(int $id, array $datos): bool {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->prepare(
            "UPDATE aspirantes SET
                nombre     = :nombre,
                email      = :email,
                telefono   = :telefono,
                id_carrera = :id_carrera,
                etapa      = :etapa,
                id_beca    = :id_beca,
                descuento_aplicado = :descuento,
                notas      = :notas
             WHERE id_aspirante = :id"
        );
        return $stmt->execute([
            ':nombre'     => $datos['nombre'],
            ':email'      => $datos['email'],
            ':telefono'   => $datos['telefono']   ?? null,
            ':id_carrera' => $datos['id_carrera']  ?? null,
            ':etapa'      => $datos['etapa']       ?? 'Contacto',
            ':id_beca'    => $datos['id_beca']     ?? null,
            ':descuento'  => $datos['descuento']   ?? 0,
            ':notas'      => $datos['notas']       ?? null,
            ':id'         => $id,
        ]);
    }

    /** Elimina un aspirante por ID. Retorna bool. */
    public function eliminar(int $id): bool {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->prepare("DELETE FROM aspirantes WHERE id_aspirante = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Cuenta aspirantes por etapa para los medidores del pipeline. */
    public function contarPorEtapa(): array {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->query(
            "SELECT etapa, COUNT(*) AS total FROM aspirantes GROUP BY etapa"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = ['Contacto' => 0, 'Interesado' => 0, 'Inscrito' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $resultado[$row['etapa']] = (int)$row['total'];
            $resultado['total'] += (int)$row['total'];
        }
        return $resultado;
    }

    /** Cuenta aspirantes nuevos en la última semana por etapa. */
    public function contarNuevosEstaSemana(): array {
        include __DIR__ . "/Conexion.php";
        $stmt = $pdo->query(
            "SELECT etapa, COUNT(*) AS total FROM aspirantes
             WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY etapa"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = ['Contacto' => 0, 'Interesado' => 0, 'Inscrito' => 0];
        foreach ($rows as $row) {
            $resultado[$row['etapa']] = (int)$row['total'];
        }
        return $resultado;
    }
}
