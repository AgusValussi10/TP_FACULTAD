<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['docente', 'alumno']);

require_once __DIR__ . '/../database/db_config.php';

$rol = $_SESSION['rol'];

if ($rol === 'docente') {
    $materia_id = (int) ($_GET['materia_id'] ?? 0);
    if ($materia_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Falta materia_id.']);
        exit;
    }

    $docente_id = (int) $_SESSION['usuario_id'];
    $materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
    if (!$materia) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
        exit;
    }

    // total_registros = 0 => todavía no se cargó ninguna asistencia (sin_datos).
    $stmt = $conn->prepare(
        "SELECT u.id AS alumno_id, u.nombre,
                COUNT(a.id) AS total_registros,
                SUM(a.estado = 'ausente') AS faltas
         FROM alumno_curso ac
         JOIN usuarios u ON u.id = ac.alumno_id
         LEFT JOIN asistencias a ON a.alumno_id = u.id AND a.materia_id = ?
         WHERE ac.curso_id = ?
         GROUP BY u.id, u.nombre
         ORDER BY u.nombre"
    );
    $stmt->bind_param('ii', $materia_id, $materia['curso_id']);
    $stmt->execute();
    $res = $stmt->get_result();

    $alumnos = [];
    while ($row = $res->fetch_assoc()) {
        $faltas = ((int) $row['total_registros'] > 0) ? (int) $row['faltas'] : null;
        $alumnos[] = ['alumno_id' => (int) $row['alumno_id'], 'nombre' => $row['nombre']] + calcular_semaforo($faltas);
    }
    $stmt->close();

    echo json_encode(['success' => true, 'materia' => $materia, 'alumnos' => $alumnos]);
} else {
    $alumno_id = (int) $_SESSION['usuario_id'];

    $stmt = $conn->prepare(
        "SELECT m.id AS materia_id, m.nombre,
                COUNT(a.id) AS total_registros,
                SUM(a.estado = 'ausente') AS faltas
         FROM alumno_curso ac
         JOIN materias m ON m.curso_id = ac.curso_id
         LEFT JOIN asistencias a ON a.alumno_id = ac.alumno_id AND a.materia_id = m.id
         WHERE ac.alumno_id = ?
         GROUP BY m.id, m.nombre
         ORDER BY m.nombre"
    );
    $stmt->bind_param('i', $alumno_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $materias = [];
    while ($row = $res->fetch_assoc()) {
        $faltas = ((int) $row['total_registros'] > 0) ? (int) $row['faltas'] : null;
        $materias[] = ['materia_id' => (int) $row['materia_id'], 'nombre' => $row['nombre']] + calcular_semaforo($faltas);
    }
    $stmt->close();

    echo json_encode(['success' => true, 'materias' => $materias]);
}

$conn->close();
