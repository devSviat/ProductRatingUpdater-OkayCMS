# Модуль ProductRatingUpdater для OkayCMS

Модуль дозволяє оновлювати рейтинг товарів. Можна оновлювати як усі товари, так і лише ті, що ще не мають рейтингу.

## Можливості

- Оновлення рейтингу усіх товарів магазину.
- Оновлення рейтингу тільки товарів без рейтингу.
- Можливість задати діапазон рейтингу та кількість голосів, які будуть призначені рандомно кожному товару.

## Вимоги

- PHP 8.4+
- OkayCMS 4.5.3 (чиста або [форк](https://github.com/devSviat/OkayCMS-Fork))
- Перевірено на PHP 8.5 — на чистій OkayCMS 4.5.3 і на форку

## Встановлення

### Варіант 1: Скачування з GitHub

1. Скачайте останню версію модуля з [останнього релізу на GitHub](https://github.com/devSviat/ProductRatingUpdater-OkayCMS/releases/latest).  
2. Розпакуйте архів на сервері в каталог: `{OkayCMS_root}/Okay/Modules/`

### Варіант 2: Встановлення через Git

1. Виконайте команду в директорії `{OkayCMS_root}/Okay/Modules/Sviat`:
   ```bash
   git clone https://github.com/devSviat/ProductRatingUpdater-OkayCMS.git ProductRatingUpdater
   ```
### Результат встановлення

Шлях до модуля має бути таким:
   ```bash
   {OkayCMS_root}/Okay/Modules/Sviat/ProductRatingUpdater/
   ```  

## Тести

Модуль має набір модульних тестів. Як їх запустити й як дописати свій —
[tests/README.md](tests/README.md).

## Ліцензія

Модуль розповсюджується за вільною ліцензією MIT
