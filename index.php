<?php

declare(strict_types=1);

set_time_limit(10);
ini_set('default_socket_timeout', '5');

const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'u82295';
const DB_USER = 'u82295';
const DB_PASSWORD = '7819341';
const FLASH_ERRORS_COOKIE = 'task4_flash_errors';
const FLASH_VALUES_COOKIE = 'task4_flash_values';
const FLASH_SUCCESS_COOKIE = 'task4_flash_success';
const FLASH_DB_ERROR_COOKIE = 'task4_flash_db_error';
const PERSISTENT_VALUES_COOKIE = 'task4_persistent_values';

$availableLanguages = [
    'Pascal',
    'C',
    'C++',
    'JavaScript',
    'PHP',
    'Python',
    'Java',
    'Haskell',
    'Clojure',
    'Prolog',
    'Scala',
    'Go',
];

$genderOptions = [
    'male' => 'Мужской',
    'female' => 'Женский',
];

$emptyValues = [
    'full_name' => '',
    'phone' => '',
    'email' => '',
    'birth_date' => '',
    'gender' => '',
    'languages' => [],
    'biography' => '',
    'contract_accepted' => false,
];

$errors = [];
$successMessage = null;
$dbError = null;

function stringLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }

    return strlen($value);
}

function setCookieValue(string $name, string $value, int $expires): void
{
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function setCookieArray(string $name, array $value, int $expires): void
{
    setCookieValue($name, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $expires);
}

function getCookieArray(string $name): ?array
{
    if (!isset($_COOKIE[$name])) {
        return null;
    }

    $decoded = json_decode((string) $_COOKIE[$name], true);
    return is_array($decoded) ? $decoded : null;
}

function clearCookie(string $name): void
{
    setCookieValue($name, '', time() - 3600);
    unset($_COOKIE[$name]);
}

function redirectToForm(): never
{
    header('Location: ' . strtok((string) $_SERVER['REQUEST_URI'], '?'));
    exit();
}

$values = $emptyValues;
$persistentValues = getCookieArray(PERSISTENT_VALUES_COOKIE);
if (is_array($persistentValues)) {
    $values = array_merge($values, $persistentValues);
    $values['languages'] = is_array($persistentValues['languages'] ?? null) ? array_values(array_map('strval', $persistentValues['languages'])) : [];
    $values['contract_accepted'] = !empty($persistentValues['contract_accepted']);
}

$flashValues = getCookieArray(FLASH_VALUES_COOKIE);
if (is_array($flashValues)) {
    $values = array_merge($values, $flashValues);
    $values['languages'] = is_array($flashValues['languages'] ?? null) ? array_values(array_map('strval', $flashValues['languages'])) : $values['languages'];
    $values['contract_accepted'] = !empty($flashValues['contract_accepted']);
    clearCookie(FLASH_VALUES_COOKIE);
}

$flashErrors = getCookieArray(FLASH_ERRORS_COOKIE);
if (is_array($flashErrors)) {
    $errors = $flashErrors;
    clearCookie(FLASH_ERRORS_COOKIE);
}

if (isset($_COOKIE[FLASH_SUCCESS_COOKIE])) {
    $successMessage = (string) $_COOKIE[FLASH_SUCCESS_COOKIE];
    clearCookie(FLASH_SUCCESS_COOKIE);
}

if (isset($_COOKIE[FLASH_DB_ERROR_COOKIE])) {
    $dbError = (string) $_COOKIE[FLASH_DB_ERROR_COOKIE];
    clearCookie(FLASH_DB_ERROR_COOKIE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $values['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $values['email'] = trim((string) ($_POST['email'] ?? ''));
    $values['birth_date'] = trim((string) ($_POST['birth_date'] ?? ''));
    $values['gender'] = trim((string) ($_POST['gender'] ?? ''));
    $values['languages'] = array_values(array_unique(array_map('strval', $_POST['languages'] ?? [])));
    $values['biography'] = trim((string) ($_POST['biography'] ?? ''));
    $values['contract_accepted'] = isset($_POST['contract_accepted']) && preg_match('/^1$/', (string) $_POST['contract_accepted']) === 1;
    $genderPattern = '/^(male|female)$/';
    $languagePattern = '/^(Pascal|C|C\+\+|JavaScript|PHP|Python|Java|Haskell|Clojure|Prolog|Scala|Go)$/';

    if ($values['full_name'] === '') {
        $errors['full_name'] = 'Укажите ФИО.';
    } elseif (stringLength($values['full_name']) > 150) {
        $errors['full_name'] = 'ФИО не должно превышать 150 символов.';
    } elseif (!preg_match('/^[\p{L}\s-]+$/u', $values['full_name'])) {
        $errors['full_name'] = 'ФИО должно содержать только буквы, пробелы и дефис.';
    }

    if ($values['phone'] === '') {
        $errors['phone'] = 'Укажите телефон.';
    } elseif (!preg_match('/^\+?[0-9\s\-()]{7,20}$/', $values['phone'])) {
        $errors['phone'] = 'Телефон должен содержать только цифры, пробелы, круглые скобки, дефис и необязательный плюс в начале.';
    }

    if ($values['email'] === '') {
        $errors['email'] = 'Укажите e-mail.';
    } elseif (!preg_match('/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', $values['email'])) {
        $errors['email'] = 'E-mail может содержать латинские буквы, цифры и символы . _ % + - до знака @.';
    } elseif (stringLength($values['email']) > 255) {
        $errors['email'] = 'E-mail не должен превышать 255 символов.';
    }

    if ($values['birth_date'] === '') {
        $errors['birth_date'] = 'Укажите дату рождения.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['birth_date'])) {
        $errors['birth_date'] = 'Дата рождения должна быть в формате ГГГГ-ММ-ДД.';
    } else {
        $birthDate = DateTimeImmutable::createFromFormat('Y-m-d', $values['birth_date']);
        $birthDateErrors = DateTimeImmutable::getLastErrors();
        if ($birthDateErrors === false) {
            $birthDateErrors = [
                'warning_count' => 0,
                'error_count' => 0,
            ];
        }
        $isBirthDateValid = $birthDate instanceof DateTimeImmutable
            && $birthDate->format('Y-m-d') === $values['birth_date']
            && $birthDateErrors['warning_count'] === 0
            && $birthDateErrors['error_count'] === 0;

        if (!$isBirthDateValid) {
            $errors['birth_date'] = 'Введите корректную дату рождения.';
        } elseif ($birthDate > new DateTimeImmutable('today')) {
            $errors['birth_date'] = 'Дата рождения не может быть в будущем.';
        }
    }

    if (preg_match($genderPattern, $values['gender']) !== 1 || !array_key_exists($values['gender'], $genderOptions)) {
        $errors['gender'] = 'Выберите допустимый пол.';
    }

    if ($values['languages'] === []) {
        $errors['languages'] = 'Выберите хотя бы один любимый язык программирования.';
    } else {
        foreach ($values['languages'] as $language) {
            if (preg_match($languagePattern, $language) !== 1 || !in_array($language, $availableLanguages, true)) {
                $errors['languages'] = 'Можно выбирать только языки из предложенного списка.';
                break;
            }
        }
    }

    if ($values['biography'] === '') {
        $errors['biography'] = 'Напишите биографию.';
    } elseif (stringLength($values['biography']) > 2000) {
        $errors['biography'] = 'Биография не должна превышать 2000 символов.';
    } elseif (!preg_match('/^[\p{L}\p{N}\s.,!?;:()"«»\'\-\/]+$/u', $values['biography'])) {
        $errors['biography'] = 'Биография может содержать буквы, цифры, пробелы и знаки препинания . , ! ? ; : ( ) " « » - /';
    }

    if (!$values['contract_accepted']) {
        $errors['contract_accepted'] = 'Необходимо ознакомиться с контрактом.';
    }

    if ($errors !== []) {
        setCookieArray(FLASH_ERRORS_COOKIE, $errors, 0);
        setCookieArray(FLASH_VALUES_COOKIE, $values, 0);
        redirectToForm();
    }

    if ($errors === []) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
                DB_USER,
                DB_PASSWORD,
                [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $pdo->beginTransaction();

            $submissionStatement = $pdo->prepare(
                'INSERT INTO submissions (full_name, phone, email, birth_date, gender, biography, contract_accepted) VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)'
            );

            $submissionStatement->execute([
                ':full_name' => $values['full_name'],
                ':phone' => $values['phone'],
                ':email' => $values['email'],
                ':birth_date' => $values['birth_date'],
                ':gender' => $values['gender'],
                ':biography' => $values['biography'],
                ':contract_accepted' => 1,
            ]);

            $submissionId = (int) $pdo->lastInsertId();

            $languageSelectStatement = $pdo->prepare('SELECT id FROM programming_languages WHERE name = :name');
            $submissionLanguageStatement = $pdo->prepare(
                'INSERT INTO submission_languages (submission_id, language_id) VALUES (:submission_id, :language_id)'
            );

            foreach ($values['languages'] as $language) {
                $languageSelectStatement->execute([':name' => $language]);
                $languageId = $languageSelectStatement->fetchColumn();

                if ($languageId === false) {
                    throw new RuntimeException('Не найден язык программирования в справочнике: ' . $language);
                }

                $submissionLanguageStatement->execute([
                    ':submission_id' => $submissionId,
                    ':language_id' => (int) $languageId,
                ]);
            }

            $pdo->commit();

            setCookieArray(PERSISTENT_VALUES_COOKIE, $values, time() + 31536000);
            setCookieValue(FLASH_SUCCESS_COOKIE, 'Данные успешно сохранены.', 0);
            clearCookie(FLASH_ERRORS_COOKIE);
            clearCookie(FLASH_VALUES_COOKIE);
            clearCookie(FLASH_DB_ERROR_COOKIE);
            redirectToForm();
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            setCookieArray(FLASH_VALUES_COOKIE, $values, 0);
            setCookieValue(FLASH_DB_ERROR_COOKIE, 'Не удалось сохранить данные: ' . $exception->getMessage(), 0);
            redirectToForm();
        }
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание 4 - Форма анкеты</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card__header">
            <p class="eyebrow">Web / Backend</p>
            <h1>Анкета пользователя</h1>
            <p class="subtitle">Форма с валидацией на бэкенде, Cookies и сохранением данных в MySQL / MariaDB.</p>
        </div>

        <?php if ($successMessage !== null): ?>
            <div class="alert alert--success"><?php echo escape($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($dbError !== null): ?>
            <div class="alert alert--error"><?php echo escape($dbError); ?></div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert--error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo escape((string) $error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" novalidate>
            <div class="form-grid">
                <div class="field field--full">
                    <label for="full_name">ФИО</label>
                    <input
                        id="full_name"
                        name="full_name"
                        type="text"
                        maxlength="150"
                        value="<?php echo escape($values['full_name']); ?>"
                        class="<?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                        placeholder="Например, Иванов Иван Иванович"
                    >
                    <?php if (isset($errors['full_name'])): ?>
                        <span class="error-text"><?php echo escape($errors['full_name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="phone">Телефон</label>
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        value="<?php echo escape($values['phone']); ?>"
                        class="<?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                        placeholder="+7 (900) 123-45-67"
                    >
                    <?php if (isset($errors['phone'])): ?>
                        <span class="error-text"><?php echo escape($errors['phone']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?php echo escape($values['email']); ?>"
                        class="<?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        placeholder="example@mail.com"
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-text"><?php echo escape($errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="birth_date">Дата рождения</label>
                    <input
                        id="birth_date"
                        name="birth_date"
                        type="date"
                        value="<?php echo escape($values['birth_date']); ?>"
                        class="<?php echo isset($errors['birth_date']) ? 'is-invalid' : ''; ?>"
                    >
                    <?php if (isset($errors['birth_date'])): ?>
                        <span class="error-text"><?php echo escape($errors['birth_date']); ?></span>
                    <?php endif; ?>
                </div>

                <fieldset class="field field--full fieldset <?php echo isset($errors['gender']) ? 'fieldset--invalid' : ''; ?>">
                    <legend>Пол</legend>
                    <div class="radio-group">
                        <?php foreach ($genderOptions as $genderValue => $genderLabel): ?>
                            <label class="radio-option">
                                <input
                                    type="radio"
                                    name="gender"
                                    value="<?php echo escape($genderValue); ?>"
                                    <?php echo $values['gender'] === $genderValue ? 'checked' : ''; ?>
                                >
                                <span><?php echo escape($genderLabel); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errors['gender'])): ?>
                        <span class="error-text"><?php echo escape($errors['gender']); ?></span>
                    <?php endif; ?>
                </fieldset>

                <div class="field field--full">
                    <label for="languages">Любимые языки программирования</label>
                    <select
                        id="languages"
                        name="languages[]"
                        multiple
                        size="8"
                        class="<?php echo isset($errors['languages']) ? 'is-invalid' : ''; ?>"
                    >
                        <?php foreach ($availableLanguages as $language): ?>
                            <option value="<?php echo escape($language); ?>" <?php echo in_array($language, $values['languages'], true) ? 'selected' : ''; ?>>
                                <?php echo escape($language); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="hint">Зажмите Ctrl или Cmd для выбора нескольких вариантов.</small>
                    <?php if (isset($errors['languages'])): ?>
                        <span class="error-text"><?php echo escape($errors['languages']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="field field--full">
                    <label for="biography">Биография</label>
                    <textarea
                        id="biography"
                        name="biography"
                        rows="6"
                        class="<?php echo isset($errors['biography']) ? 'is-invalid' : ''; ?>"
                        placeholder="Кратко расскажите о себе"
                    ><?php echo escape($values['biography']); ?></textarea>
                    <?php if (isset($errors['biography'])): ?>
                        <span class="error-text"><?php echo escape($errors['biography']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="field field--full">
                    <label class="checkbox-option <?php echo isset($errors['contract_accepted']) ? 'checkbox-option--invalid' : ''; ?>">
                        <input
                            type="checkbox"
                            name="contract_accepted"
                            value="1"
                            <?php echo $values['contract_accepted'] ? 'checked' : ''; ?>
                        >
                        <span>С контрактом ознакомлен(а)</span>
                    </label>
                    <?php if (isset($errors['contract_accepted'])): ?>
                        <span class="error-text"><?php echo escape($errors['contract_accepted']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <button class="button" type="submit">Сохранить</button>
        </form>
    </div>
</div>
</body>
</html>
