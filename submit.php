<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pdo = new PDO("mysql:host=localhost;dbname=form_db", "root", "");

$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$birthdate = $_POST['birthdate'] ?? '';
$gender = $_POST['gender'] ?? '';
$languages = $_POST['languages'] ?? [];
$bio = $_POST['bio'] ?? '';
$contract = $_POST['contract'] ?? '';

$errors = [];

// ФИО
if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s]{1,150}$/u", $name)) {
    $errors[] = "ФИО должно содержать только буквы и пробелы (до 150 символов)";
}

// Телефон
if (!preg_match("/^\+7\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$/", $phone)) {
    $errors[] = "Телефон должен быть в формате +7(XXX)XXX-XX-XX";
}

// Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный email";
}

// Пол
if (!in_array($gender, ['male', 'female'])) {
    $errors[] = "Некорректный пол";
}

// Языки
$allowed_languages = [
    'Pascal','C','C++','JavaScript','PHP','Python',
    'Java','Haskell','Clojure','Prolog','Scala','Go'
];

if (empty($languages)) {
    $errors[] = "Выберите хотя бы один язык";
}

foreach ($languages as $lang) {
    if (!in_array($lang, $allowed_languages)) {
        $errors[] = "Некорректный язык программирования";
        break;
    }
}

// Контракт
if (!$contract) {
    $errors[] = "Необходимо согласие с контрактом";
}

// Если есть ошибки
if (!empty($errors)) {
    echo "<h2>Ошибки:</h2><ul>";
    foreach ($errors as $e) {
        echo "<li>$e</li>";
    }
    echo "</ul><a href='index.html'>Назад</a>";
    exit;
}

// Сохраняем пользователя
$stmt = $pdo->prepare("
    INSERT INTO users (name, phone, email, birthdate, gender, bio)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$name, $phone, $email, $birthdate, $gender, $bio]);

$user_id = $pdo->lastInsertId();

// Сохраняем языки
foreach ($languages as $lang) {
    $stmt = $pdo->prepare("SELECT id FROM languages WHERE name=?");
    $stmt->execute([$lang]);
    $lang_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO user_languages (user_id, language_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$user_id, $lang_id]);
}

echo "<h2>Данные успешно сохранены 🌸</h2>";
echo "<a href='index.html'>Отправить ещё</a>";

?>
