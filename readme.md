# Telegram Night Mode Bot
![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![GitHub release](https://img.shields.io/github/v/release/Targitai84/nightmode-bot)
![License](https://img.shields.io/badge/License-MIT-green)

Автоматический бот для Telegram-групп, который включает и выключает «ночной режим» по расписанию.  
Бот умеет запрещать отправку сообщений ночью и автоматически включать чат утром.  
Поддерживает несколько языков (RU/EN), управление через команды и cron-автоматизацию.

---

## ✨ Возможности

- Автоматическое включение/выключение ночного режима по расписанию.
- Поддержка часовых поясов группы (от -12 до +14).
- Изменение настроек прямо из Telegram:
  - `/set 23:00-07:00` — установить ночной период
  - `/tz +3` — установить timezone
  - `/lang RU|EN` — сменить язык бота
  - `/bot on|off` — включить/выключить работу бота в группе
- Уведомления в чат при включении/выключении ночного режима.
- Логирование в файлы.
- Отдельные JSON-файлы настроек по каждой группе.
- Простая установка — обычный PHP без фреймворков.

---

## 📂 Структура проекта
```bash
/
├── index.php          # основной обработчик Telegram webhook
├── cron_job.php       # автоматизация для CRON
├── groups/            # конфиги групп
│   └── <group_id>.json
├── settings/
│   ├── settings.json  # глобальные настройки бота
│   └── lang/
│       ├── ru.json
│       └── en.json
├── logs/
│   └── YYYY-MM-DD.log
├── telegram.php       # sendMessage, setChatPermissions, utils
├── .gitignore
└── README.md
```

## ⚙️ Установка

### 1. Клонируем проект

```bash
git clone https://github.com/Targitai84/night-mode-bot.git
cd nightmode-bot
```

###  2. Настраиваем settings.json
```bash
{
  "bot_token": "YOUR_TELEGRAM_BOT_TOKEN",
  "log_level": "info",
  "default_group_settings": {
    "lang": "EN",
    "isadmin": false,
    "enabled": false,
    "bot_enabled": false,
    "lock_from": "23:00",
    "lock_to": "07:00",
    "timezone": "+0",
    "locked": false,
    "last_action": "none"
  },
  "permissions_default": {
    "can_send_messages": true,
    "can_send_media_messages": true,
    "can_send_polls": true,
    "can_send_other_messages": true,
    "can_add_web_page_previews": true,
    "can_change_info": true,
    "can_invite_users": true,
    "can_pin_messages": true,
    "can_manage_topics": true
  },
  "permissions_night": {
    "can_send_messages": false,
    "can_send_media_messages": false,
    "can_send_polls": false,
    "can_send_other_messages": false,
    "can_add_web_page_previews": false,
    "can_change_info": false,
    "can_invite_users": false,
    "can_pin_messages": false,
    "can_manage_topics": false
  }
}
```
###  3. Настройка Webhook
curl -F "url=https://yourdomain.com/bot.php" \
     https://api.telegram.org/botYOUR_TOKEN/setWebhook

###  4. Настройка CRON
```bash
crontab -e
*/5 * * * * /usr/bin/php /path/to/cron_job.php
```
#### 5. Команды
| Команда                 | Описание                                    | 
| ----------------------- | --------------------------------------------| 
| `/info`                 | Показать текущие настройки                  |
| `/set HH:MM-HH:MM`      | Установить время ночного режима             |
| `/tz +3`                | Установить часовой пояс                     | 
| `/lang RU` / `/lang EN` | Сменить язык сообщений                      | 
| `/bot on off`           | Включить/выключить ночной режим в группе    |
| `/enable`               | Принудительно включить ночной режим         |
| `/disable`              | Принудительно отключить                     |


### 6.  Права

Боту нужно быть администратором группы
с правами: управление сообщениями и участниками.

### 7. Требования

PHP 7.4+

Webhook-сервер (Apache/Nginx)

Cron в системе

HTTPS (обязательно для Telegram)
