<?php
header('Content-Type: text/html; charset=UTF-8');

// -------------------------------------------------------------------
// GET-ЗАПРОС (показать форму)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];

    // Проверяем куку об успешном сохранении
    if (isset($_COOKIE['save'])) {
        $messages[] = '✅ Данные успешно сохранены!';
        setcookie('save', '', time() - 3600);
    }

    // Загружаем ошибки из кук
    $errors = [
        'full_name' => isset($_COOKIE['full_name_error']),
        'phone'     => isset($_COOKIE['phone_error']),
        'email'     => isset($_COOKIE['email_error']),
        'birth_date'=> isset($_COOKIE['birth_date_error']),
        'gender'    => isset($_COOKIE['gender_error']),
        'languages' => isset($_COOKIE['languages_error']),
        'agreed'    => isset($_COOKIE['agreed_error'])
    ];

    // Загружаем старые значения из кук
    $values = [
        'full_name' => $_COOKIE['full_name_value'] ?? '',
        'phone'     => $_COOKIE['phone_value'] ?? '',
        'email'     => $_COOKIE['email_value'] ?? '',
        'birth_date'=> $_COOKIE['birth_date_value'] ?? '',
        'gender'    => $_COOKIE['gender_value'] ?? '',
        'languages' => isset($_COOKIE['languages_value']) ? explode(',', $_COOKIE['languages_value']) : [],
        'biography' => $_COOKIE['biography_value'] ?? '',
        'agreed'    => isset($_COOKIE['agreed_value'])
    ];

    // Удаляем одноразовые куки ошибок и значений
    foreach (['full_name', 'phone', 'email', 'birth_date', 'gender', 'languages', 'agreed'] as $field) {
        setcookie($field . '_error', '', time() - 3600);
        setcookie($field . '_value', '', time() - 3600);
    }
    setcookie('languages_value', '', time() - 3600);
    setcookie('biography_value', '', time() - 3600);
    setcookie('agreed_value', '', time() - 3600);

    include 'form.php';
    exit();
}

// -------------------------------------------------------------------
// POST-ЗАПРОС (обработка и сохранение)
// -------------------------------------------------------------------
else {
    $errors_flag = false;

    // ----- Валидация ФИО -----
    $full_name = trim($_POST['full_name'] ?? '');
    if (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]{1,150}$/u', $full_name)) {
        setcookie('full_name_error', '1', time() + 86400);
        setcookie('full_name_value', $full_name, time() + 86400);
        $errors_flag = true;
    } else {
        setcookie('full_name_value', $full_name, time() + 86400);
    }

    // ----- Валидация телефона -----
    $phone = trim($_POST['phone'] ?? '');
    if (!preg_match('/^\+7\d{10}$/', $phone)) {
        setcookie('phone_error', '1', time() + 86400);
        setcookie('phone_value', $phone, time() + 86400);
        $errors_flag = true;
    } else {
        setcookie('phone_value', $phone, time() + 86400);
    }

    // ----- Валидация email -----
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 86400);
        setcookie('email_value', $email, time() + 86400);
        $errors_flag = true;
    } else {
        setcookie('email_value', $email, time() + 86400);
    }

    // ----- Валидация даты рождения -----
    $birth_date = $_POST['birth_date'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        setcookie('birth_date_error', '1', time() + 86400);
        setcookie('birth_date_value', $birth_date, time() + 86400);
        $errors_flag = true;
    } else {
        setcookie('birth_date_value', $birth_date, time() + 86400);
    }

    // ----- Валидация пола -----
    $gender = $_POST['gender'] ?? '';
    if (!in_array($gender, ['male', 'female', 'other'])) {
        setcookie('gender_error', '1', time() + 86400);
        setcookie('gender_value', $gender, time() + 86400);
        $errors_flag = true;
    } else {
        setcookie('gender_value', $gender, time() + 86400);
    }

    // ----- Валидация языков -----
    $allowed_languages = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskell','Clojure','Prolog','Scala','Go'];
    $languages = $_POST['languages'] ?? [];
    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 86400);
        setcookie('languages_value', implode(',', $languages), time() + 86400);
        $errors_flag = true;
    } else {
        $valid = true;
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                $valid = false;
                break;
            }
        }
        if (!$valid) {
            setcookie('languages_error', '1', time() + 86400);
            setcookie('languages_value', implode(',', $languages), time() + 86400);
            $errors_flag = true;
        } else {
            setcookie('languages_value', implode(',', $languages), time() + 86400);
        }
    }

    // ----- Биография (необязательная) -----
    $biography = trim($_POST['biography'] ?? '');
    setcookie('biography_value', $biography, time() + 86400);

    // ----- Чекбокс согласия -----
    $agreed = isset($_POST['agreed']);
    if (!$agreed) {
        setcookie('agreed_error', '1', time() + 86400);
        setcookie('agreed_value', $agreed ? '1' : '0', time() + 86400);
        $errors_flag = true;
    } else {
        setcookie('agreed_value', '1', time() + 86400);
    }

    // Если есть ошибки – перезагружаем страницу (GET)
    if ($errors_flag) {
        header('Location: index.php');
        exit();
    }

    // ----- Сохранение в базу данных -----
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=u82318;charset=utf8', 'u82318', '5918027', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, email, birth_date, gender, biography, agreed) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, (int)$agreed]);
        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
        $stmt2 = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang) {
            $stmt->execute([$lang]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $stmt2->execute([$user_id, $row['id']]);
            }
        }

        $pdo->commit();

        // Сохраняем куку об успехе
        setcookie('save', '1', time() + 86400);
        header('Location: index.php');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo 'Ошибка БД: ' . htmlspecialchars($e->getMessage());
    }
}
?>
