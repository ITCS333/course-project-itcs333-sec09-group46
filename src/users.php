<?php
require_once __DIR__ . '/config.php';

function get_students($pdo) {
    return $pdo->query("SELECT * FROM users WHERE role='student'")->fetchAll();
}

function get_user($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function create_student($pdo, $student_id, $name, $email, $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (student_id,name,email,password,role)
                           VALUES (?,?,?,?, 'student')");
    return $stmt->execute([$student_id,$name,$email,$hash]);
}

function update_student($pdo, $id, $student_id, $name, $email) {
    $stmt = $pdo->prepare("UPDATE users SET student_id=?,name=?,email=? WHERE id=?");
    return $stmt->execute([$student_id,$name,$email,$id]);
}

function delete_student($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    return $stmt->execute([$id]);
}

function change_admin_password($pdo, $id, $newpass) {
    $hash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    return $stmt->execute([$hash,$id]);
}
