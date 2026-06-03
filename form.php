<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Анкета (Задание 4)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Анкета (Задание 4)</h1>
    <p>Проверка корректного заполнения с использованием Cookies</p>

    <?php if (!empty($messages)): ?>
        <div class="success"><?= implode('<br>', array_map('htmlspecialchars', $messages)) ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <label>ФИО:</label>
        <input type="text" name="full_name" class="<?= $errors['full_name'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['full_name']) ?>" placeholder="Иванова Анна Сергеевна">
        <?php if ($errors['full_name']): ?><div class="error-text">Только буквы, пробелы, дефис, 1-150 симв.</div><?php endif; ?>

        <label>Телефон (+7XXXXXXXXXX):</label>
        <input type="text" name="phone" class="<?= $errors['phone'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['phone']) ?>" placeholder="+71234567890">
        <?php if ($errors['phone']): ?><div class="error-text">Формат: +7XXXXXXXXXX (10 цифр после +7)</div><?php endif; ?>

        <label>Email:</label>
        <input type="text" name="email" class="<?= $errors['email'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['email']) ?>" placeholder="anna@example.com">
        <?php if ($errors['email']): ?><div class="error-text">Некорректный email</div><?php endif; ?>

        <label>Дата рождения:</label>
        <input type="date" name="birth_date" class="<?= $errors['birth_date'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['birth_date']) ?>">
        <?php if ($errors['birth_date']): ?><div class="error-text">Выберите дату</div><?php endif; ?>

        <label>Пол:</label>
        <div class="radio-group">
            <label><input type="radio" name="gender" value="male" <?= $values['gender'] == 'male' ? 'checked' : '' ?>> Мужской</label>
            <label><input type="radio" name="gender" value="female" <?= $values['gender'] == 'female' ? 'checked' : '' ?>> Женский</label>
            <label><input type="radio" name="gender" value="other" <?= $values['gender'] == 'other' ? 'checked' : '' ?>> Другой</label>
        </div>
        <?php if ($errors['gender']): ?><div class="error-text">Выберите пол</div><?php endif; ?>

        <label>Любимые языки программирования:</label>
        <select name="languages[]" multiple class="<?= $errors['languages'] ? 'error' : '' ?>">
            <option value="Pascal" <?= in_array('Pascal', $values['languages']) ? 'selected' : '' ?>>Pascal</option>
            <option value="C" <?= in_array('C', $values['languages']) ? 'selected' : '' ?>>C</option>
            <option value="C++" <?= in_array('C++', $values['languages']) ? 'selected' : '' ?>>C++</option>
            <option value="JavaScript" <?= in_array('JavaScript', $values['languages']) ? 'selected' : '' ?>>JavaScript</option>
            <option value="PHP" <?= in_array('PHP', $values['languages']) ? 'selected' : '' ?>>PHP</option>
            <option value="Python" <?= in_array('Python', $values['languages']) ? 'selected' : '' ?>>Python</option>
            <option value="Java" <?= in_array('Java', $values['languages']) ? 'selected' : '' ?>>Java</option>
            <option value="Haskell" <?= in_array('Haskell', $values['languages']) ? 'selected' : '' ?>>Haskell</option>
            <option value="Clojure" <?= in_array('Clojure', $values['languages']) ? 'selected' : '' ?>>Clojure</option>
            <option value="Prolog" <?= in_array('Prolog', $values['languages']) ? 'selected' : '' ?>>Prolog</option>
            <option value="Scala" <?= in_array('Scala', $values['languages']) ? 'selected' : '' ?>>Scala</option>
            <option value="Go" <?= in_array('Go', $values['languages']) ? 'selected' : '' ?>>Go</option>
        </select>
        <?php if ($errors['languages']): ?><div class="error-text">Выберите хотя бы один язык из списка</div><?php endif; ?>

        <label>Биография:</label>
        <textarea name="biography" rows="4" placeholder="Немного о себе..."><?= htmlspecialchars($values['biography']) ?></textarea>

        <label class="checkbox-label">
            <input type="checkbox" name="agreed" <?= $values['agreed'] ? 'checked' : '' ?>> С контрактом ознакомлен(а)
        </label>
        <?php if ($errors['agreed']): ?><div class="error-text">Необходимо согласие с контрактом</div><?php endif; ?>

        <button type="submit">💾 Сохранить</button>
    </form>
</div>
</body>
</html>
