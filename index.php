<?php
// Sistema de Inventario - Distribuidora El Roble
// Stack clásico: PHP + Apache + MySQL. Las credenciales llegan por variables de entorno.
$empresa = getenv('EMPRESA') ?: 'Cliente';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_NAME'));
try {
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        categoria VARCHAR(60),
        existencia INT NOT NULL DEFAULT 0,
        precio DECIMAL(10,2) NOT NULL DEFAULT 0,
        creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
} catch (PDOException $e) {
    http_response_code(503);
    die('<h2>Base de datos no disponible</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$accion = $_POST['accion'] ?? '';
if ($accion === 'crear') {
    $st = $pdo->prepare('INSERT INTO productos (nombre, categoria, existencia, precio) VALUES (?, ?, ?, ?)');
    $st->execute([$_POST['nombre'], $_POST['categoria'], (int)$_POST['existencia'], (float)$_POST['precio']]);
    header('Location: /'); exit;
}
if ($accion === 'actualizar') {
    $st = $pdo->prepare('UPDATE productos SET nombre = ?, categoria = ?, existencia = ?, precio = ? WHERE id = ?');
    $st->execute([$_POST['nombre'], $_POST['categoria'], (int)$_POST['existencia'], (float)$_POST['precio'], (int)$_POST['id']]);
    header('Location: /'); exit;
}
if ($accion === 'eliminar') {
    $st = $pdo->prepare('DELETE FROM productos WHERE id = ?');
    $st->execute([(int)$_POST['id']]);
    header('Location: /'); exit;
}
$editar = null;
if (isset($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
$productos = $pdo->query('SELECT * FROM productos ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$versionBD = $pdo->query('SELECT VERSION()')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Inventario - <?= e($empresa) ?></title>
<style>
 body { font-family: Arial, sans-serif; margin: 30px; background: #f4f1ea; color: #333; }
 h1 { color: #5a3e1b; } table { border-collapse: collapse; width: 100%; background: #fff; }
 th, td { border: 1px solid #ccc; padding: 8px; text-align: left; } th { background: #8b5e34; color: #fff; }
 form.inline { display: inline; } .caja { background: #fff; padding: 15px; margin-bottom: 20px; border: 1px solid #ccc; }
 input { margin: 4px; padding: 5px; } footer { margin-top: 25px; font-size: 12px; color: #777; }
</style>
</head>
<body>
<h1><?= e($empresa) ?> - Sistema de Inventariov2</h1>
<div class="caja">
 <h3><?= $editar ? 'Editar producto #' . e($editar['id']) : 'Nuevo producto' ?></h3>
 <form method="post">
  <input type="hidden" name="accion" value="<?= $editar ? 'actualizar' : 'crear' ?>">
  <?php if ($editar): ?><input type="hidden" name="id" value="<?= e($editar['id']) ?>"><?php endif; ?>
  <input name="nombre" placeholder="Nombre" required value="<?= e($editar['nombre'] ?? '') ?>">
  <input name="categoria" placeholder="Categoría" value="<?= e($editar['categoria'] ?? '') ?>">
  <input name="existencia" type="number" placeholder="Existencia" required value="<?= e($editar['existencia'] ?? '') ?>">
  <input name="precio" type="number" step="0.01" placeholder="Precio" required value="<?= e($editar['precio'] ?? '') ?>">
  <button type="submit"><?= $editar ? 'Guardar cambios' : 'Agregar' ?></button>
  <?php if ($editar): ?><a href="/">Cancelar</a><?php endif; ?>
 </form>
</div>
<table>
 <tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Existencia</th><th>Precio</th><th>Acciones</th></tr>
 <?php foreach ($productos as $p): ?>
 <tr>
  <td><?= e($p['id']) ?></td><td><?= e($p['nombre']) ?></td><td><?= e($p['categoria']) ?></td>
  <td><?= e($p['existencia']) ?></td><td>$<?= e(number_format((float)$p['precio'], 2)) ?></td>
  <td>
   <a href="/?editar=<?= e($p['id']) ?>">Editar</a>
   <form class="inline" method="post" onsubmit="return confirm('¿Eliminar producto?')">
    <input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= e($p['id']) ?>">
    <button type="submit">Eliminar</button>
   </form>
  </td>
 </tr>
 <?php endforeach; ?>
</table>
<footer>Servido por el pod <?= e(gethostname()) ?> | PHP <?= e(phpversion()) ?> | MySQL <?= e($versionBD) ?></footer>
</body>
</html>
