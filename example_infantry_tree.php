<?php
/**
 * Script de ejemplo: Crear árbol de investigaciones de Infantería
 * Basado en la imagen proporcionada
 */

require_once 'config/db.php';

echo "Creando investigaciones de ejemplo (Infantería)...\n\n";

try {
    // Obtener ID de categoría Terrestre
    $cat_stmt = $conn->query("SELECT id_categoria FROM categorias_investigacion WHERE nombre_categoria = 'Terrestre'");
    $id_terrestre = $cat_stmt->fetchColumn();
    
    if (!$id_terrestre) {
        die("Error: Categoría 'Terrestre' no encontrada\n");
    }
    
    // Obtener ID de subcategoría Infantería
    $subcat_stmt = $conn->prepare("SELECT id_subcategoria FROM subcategorias_investigacion WHERE nombre_subcategoria = 'Infantería' AND id_categoria = :id_cat");
    $subcat_stmt->execute([':id_cat' => $id_terrestre]);
    $id_infanteria = $subcat_stmt->fetchColumn();
    
    if (!$id_infanteria) {
        die("Error: Subcategoría 'Infantería' no encontrada\n");
    }
    
    // Nivel 1: Sin requisitos
    echo "Creando: Entrenamiento de Combate Avanzado (500 IT)...\n";
    $stmt1 = $conn->prepare("
        INSERT INTO investigaciones (id_categoria, id_subcategoria, nombre_investigacion, costo_it, descripcion, orden)
        VALUES (:c, :sc, :n, :cost, :d, :o)
    ");
    $stmt1->execute([
        ':c' => $id_terrestre,
        ':sc' => $id_infanteria,
        ':n' => 'Entrenamiento de Combate Avanzado',
        ':cost' => 500,
        ':d' => 'Tácticas de combate modernas y entrenamiento especializado para unidades de infantería',
        ':o' => 1
    ]);
    $id_nivel1 = $conn->lastInsertId();
    
    // Nivel 2: Requiere Nivel 1
    echo "Creando: Casco y Chaleco Balístico (1000 IT)...\n";
    $stmt2 = $conn->prepare("
        INSERT INTO investigaciones (id_categoria, id_subcategoria, nombre_investigacion, costo_it, descripcion, orden)
        VALUES (:c, :sc, :n, :cost, :d, :o)
    ");
    $stmt2->execute([
        ':c' => $id_terrestre,
        ':sc' => $id_infanteria,
        ':n' => 'Casco y Chaleco Balístico',
        ':cost' => 1000,
        ':d' => 'Equipo de protección personal avanzado que reduce bajas de infantería',
        ':o' => 2
    ]);
    $id_nivel2 = $conn->lastInsertId();
    
    // Agregar requisito: Nivel 2 requiere Nivel 1
    $conn->prepare("INSERT INTO investigaciones_requisitos (id_investigacion, id_investigacion_requerida) VALUES (?, ?)")
         ->execute([$id_nivel2, $id_nivel1]);
    
    // Nivel 3: Requiere Nivel 2
    echo "Creando: Equipo de Radio-Comunicación de Pelotón (1500 IT)...\n";
    $stmt3 = $conn->prepare("
        INSERT INTO investigaciones (id_categoria, id_subcategoria, nombre_investigacion, costo_it, descripcion, orden)
        VALUES (:c, :sc, :n, :cost, :d, :o)
    ");
    $stmt3->execute([
        ':c' => $id_terrestre,
        ':sc' => $id_infanteria,
        ':n' => 'Equipo de Radio-Comunicación de Pelotón',
        ':cost' => 1500,
        ':d' => 'Sistemas de comunicación encriptada para coordinación táctica en tiempo real',
        ':o' => 3
    ]);
    $id_nivel3 = $conn->lastInsertId();
    
    // Agregar requisito: Nivel 3 requiere Nivel 2
    $conn->prepare("INSERT INTO investigaciones_requisitos (id_investigacion, id_investigacion_requerida) VALUES (?, ?)")
         ->execute([$id_nivel3, $id_nivel2]);
    
    // Nivel 4: Requiere Nivel 3
    echo "Creando: Visores Térmicos y Nocturnos (2500 IT)...\n";
    $stmt4 = $conn->prepare("
        INSERT INTO investigaciones (id_categoria, id_subcategoria, nombre_investigacion, costo_it, descripcion, orden)
        VALUES (:c, :sc, :n, :cost, :d, :o)
    ");
    $stmt4->execute([
        ':c' => $id_terrestre,
        ':sc' => $id_infanteria,
        ':n' => 'Visores Térmicos y Nocturnos',
        ':cost' => 2500,
        ':d' => 'Tecnología de visión nocturna e infrarroja para operaciones en cualquier condición',
        ':o' => 4
    ]);
    $id_nivel4 = $conn->lastInsertId();
    
    // Agregar requisito: Nivel 4 requiere Nivel 3
    $conn->prepare("INSERT INTO investigaciones_requisitos (id_investigacion, id_investigacion_requerida) VALUES (?, ?)")
         ->execute([$id_nivel4, $id_nivel3]);
    
    // Nivel 5: Requiere Nivel 4
    echo "Creando: MANPAD/ATGM Individual (3500 IT)...\n";
    $stmt5 = $conn->prepare("
        INSERT INTO investigaciones (id_categoria, id_subcategoria, nombre_investigacion, costo_it, descripcion, orden)
        VALUES (:c, :sc, :n, :cost, :d, :o)
    ");
    $stmt5->execute([
        ':c' => $id_terrestre,
        ':sc' => $id_infanteria,
        ':n' => 'MANPAD/ATGM Individual',
        ':cost' => 3500,
        ':d' => 'Sistemas portátiles antiaéreos y antitanque de última generación',
        ':o' => 5
    ]);
    $id_nivel5 = $conn->lastInsertId();
    
    // Agregar requisito: Nivel 5 requiere Nivel 4
    $conn->prepare("INSERT INTO investigaciones_requisitos (id_investigacion, id_investigacion_requerida) VALUES (?, ?)")
         ->execute([$id_nivel5, $id_nivel4]);
    
    echo "\n✓ Árbol de investigaciones de Infantería creado exitosamente!\n\n";
    echo "Estructura:\n";
    echo "1. Entrenamiento de Combate Avanzado (500 IT)\n";
    echo "   └─> 2. Casco y Chaleco Balístico (1000 IT)\n";
    echo "       └─> 3. Equipo de Radio-Comunicación de Pelotón (1500 IT)\n";
    echo "           └─> 4. Visores Térmicos y Nocturnos (2500 IT)\n";
    echo "               └─> 5. MANPAD/ATGM Individual (3500 IT)\n\n";
    echo "Total IT para completar árbol: 9000 IT\n\n";
    echo "Accede a gm_research.php para ver o modificar las investigaciones\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
