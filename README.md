# Zernio Chat

Веб-приложение для управления чатами поддержки через Zernio (Instagram и WhatsApp).

## Стек

* Laravel 13
* PHP 8.3
* MySQL 8.0
* Laravel Reverb
* Vite
* Docker

## Запуск

После клонирования проекта:

```bash
docker compose up -d
```

Docker автоматически запускает MySQL, выполняет миграции, запускает Laravel и Reverb.

Приложение:

```text
http://127.0.0.1:8000
```

Для получения webhook от Zernio:

```bash
ngrok http 8000
```

## Настройка

Создайте `.env` на основе `.env.example` и укажите:

```env
ZERNIO_API_KEY=
ZERNIO_WEBHOOK_SECRET=
```

## Полезные команды

```bash
docker compose ps
docker compose logs app
docker compose logs reverb
docker compose down
```
