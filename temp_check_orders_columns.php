<?php
require_once 'config/database.php';
header('Content-Type: application/json');
try{
  $stmt=$pdo->query('SHOW COLUMNS FROM orders');
  $cols=$stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($cols,JSON_PRETTY_PRINT);
}catch(Throwable $e){ echo json_encode(['error'=>$e->getMessage()]); }
