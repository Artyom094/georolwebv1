<?php
session_start();

// Solo GM y Admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['id_rol'], [1, 2])) {
    header("Location: /index.php");
    exit;
}

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_pais'])) {
    $id_pais = intval($_POST['id_pais']);
    $selected = isset($_POST['research']) ? $_POST['research'] : [];
    
    try {
        // Get all available research IDs
        $all_stmt = $conn->query("SELECT id_investigacion FROM investigaciones WHERE activo = 1");
        $all_ids = $all_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete all current research for this country
        $delete = $conn->prepare("DELETE FROM paises_investigaciones WHERE id_pais = :id");
        $delete->execute([':id' => $id_pais]);
        
        // Insert selected research
        if (!empty($selected)) {
            $insert = $conn->prepare("INSERT INTO paises_investigaciones (id_pais, id_investigacion) VALUES (:p, :i)");
            foreach ($selected as $id_inv) {
                if (in_array($id_inv, $all_ids)) {
                    try {
                        $insert->execute([':p' => $id_pais, ':i' => $id_inv]);
                    } catch (PDOException $e) {
                        // Skip duplicates
                    }
                }
            }
        }
        
        header("Location: /view_country.php?id=$id_pais&msg=research_updated");
        exit;
        
    } catch (Exception $e) {
        header("Location: /view_country.php?id=$id_pais&error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: /index.php");
    exit;
}
