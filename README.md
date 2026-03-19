# MineBet — Казино Мины & Больше/Меньше

Полноценный PHP-проект с честной игрой, реферальной системой и оплатой через YooKassa.

## Требования
- PHP 8.0+
- Расширения: `pdo_sqlite`, `curl`, `openssl`
- Apache / Nginx + веб-сервер

## Установка

### 1. Загрузите файлы на сервер
```
minebet/
├── index.php
├── webhook.php
├── .htaccess
├── includes/
│   ├── config.php     ← ВАШ КОНФИГ
│   ├── db.php
│   ├── auth.php
│   ├── game.php
│   ├── payment.php
│   ├── helpers.php
│   ├── layout_head.php
│   └── layout_foot.php
├── pages/
│   ├── home.php
│   ├── login.php
│   ├── register.php
│   ├── mines.php
│   ├── hilo.php
│   ├── profile.php
│   ├── wallet.php
│   ├── top.php
│   ├── fair.php
│   └── referral.php
├── api/
│   └── game.php
├── assets/
│   ├── css/main.css
│   └── js/app.js
└── data/              ← создастся автоматически
```

### 2. Настройте config.php
```php
define('APP_URL',          'https://yourdomain.com');
define('YOOKASSA_SHOP_ID', 'ваш_shop_id');
define('YOOKASSA_SECRET',  'ваш_секретный_ключ');
```

### 3. Права доступа
```bash
chmod 755 data/
chmod 644 data/minebet.db  # после первого запуска
```

### 4. Nginx конфиг (пример)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/minebet;
    index index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~* \.(css|js|png|jpg|gif|ico)$ {
        expires 7d;
        add_header Cache-Control "public";
    }

    # Block sensitive dirs
    location ~ /data/ { deny all; }
    location ~ /includes/ { deny all; }
}
```

### 5. Webhook YooKassa
В личном кабинете YooKassa укажите URL вебхука:
```
https://yourdomain.com/webhook.php
```

## YooKassa

1. Зарегистрируйтесь на https://yookassa.ru
2. Создайте магазин
3. В настройках магазина найдите `Shop ID` и `Секретный ключ`
4. Вставьте в `includes/config.php`
5. Настройте вебхук на `payment.succeeded`

## Функции

| Функция              | Описание                                        |
|---------------------|-------------------------------------------------|
| Регистрация/Вход    | С CSRF-защитой и хэшем bcrypt                   |
| Мины 5×5            | 1–24 мины, провабли-фэйр, cashout в любой момент |
| Больше/Меньше       | Числа 1–100, динамический множитель              |
| Профиль             | Статистика, аватар, история транзакций          |
| Кошелёк             | Пополнение YooKassa, вывод на карту             |
| Рефералы            | +100₽ за каждого приглашённого                  |
| Топ игроков         | По прибыли, по играм (мины/хайло)               |
| Честная игра        | HMAC-SHA256, страница верификации               |

## Безопасность
- CSRF-токены на всех формах
- PDO prepared statements (защита от SQL-инъекций)
- bcrypt для паролей
- Блокировка доступа к `/data/` и `/includes/` через .htaccess
- Проверка подписи YooKassa вебхука через API

## Поддержка
Настройте под свои нужды: комиссия казино `HOUSE_EDGE`, 
реферальные бонусы `REF_BONUS_*`, лимиты ставок `MIN_BET/MAX_BET`.
