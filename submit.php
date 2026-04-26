<?php
$pdo = new PDO("mysql:host=localhost;dbname=form_db", "u82318", "5918027");

// Получение данных
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$birthdate = $_POST['birthdate'] ?? '';
$gender = $_POST['gender'] ?? '';
$languages = $_POST['languages'] ?? [];
$bio = $_POST['bio'] ?? '';
$contract = $_POST['contract'] ?? '';

$errors = [];

// Валидация
if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s]{1,150}$/u", $name)) {
    $errors[] = "Некорректное ФИО";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный email";
}

if (!in_array($gender, ['male', 'female'])) {
    $errors[] = "Некорректный пол";
}

$allowed_languages = [
    'Pascal','C','C++','JavaScript','PHP','Python',
    'Java','Haskell','Clojure','Prolog','Scala','Go'
];

foreach ($languages as $lang) {
    if (!in_array($lang, $allowed_languages)) {
        $errors[] = "Некорректный язык программирования";
        break;
    }
}

if (!$contract) {
    $errors[] = "Необходимо согласие с контрактом";
}

// Если есть ошибки
if (!empty($errors)) {
    echo "<h3>Ошибки:</h3><ul>";
    foreach ($errors as $e) {
        echo "<li>$e</li>";
    }
    echo "</ul>";
    exit;
}

// Сохранение пользователя
$stmt = $pdo->prepare("
    INSERT INTO users (name, phone, email, birthdate, gender, bio)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$name, $phone, $email, $birthdate, $gender, $bio]);

$user_id = $pdo->lastInsertId();

// Сохранение языков
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
?>
