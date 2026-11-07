# Блог europartswest.com.ua

Статті — Markdown-файли в `blog/posts/<slug>.md`. HTML генерується скриптом:

```
pip install markdown
python3 tools/build_site.py .
```

Скрипт перебудовує підсторінки, `404.html`, `blog/index.html`, сторінки статей, `sitemap.xml`, `robots.txt`.
Стаття з датою в майбутньому не публікується, доки не настане її дата. `index.html` правиться вручну.

## Формат статті

```
---
title: Заголовок (до ~70 символів)
date: 2026-11-10
description: 1–2 речення для Google і картки статті (до ~160 символів)
cover: назва-файлу.jpg        # лежить у images/blog/, 1600px по ширині, JPEG
cover_alt: що на фото
tags: Manitou, JCB
links: to, fuel                # ключі CATS у tools/build_site.py — кнопки в каталог/сервіс
---
Текст статті. `## Підзаголовки`, списки, `> **Порада:** …` — блок-порада.
```

## Правила (контент-план: https://claude.ai/artifact/JatumK6JzcS3oGNrqTrAr6)

- 600–1000 слів, українською, без вигаданих цифр і без цін.
- Обкладинка — реальні фото техніки з `images/about-us/` (обрізані до 1600px), без чужих логотипів.
- Посилання на потрібну категорію каталогу; у кінці — заклик залишити заявку або зателефонувати.
- Після змін JS/CSS — підняти `V` у `tools/build_site.py` і `?v=` в `index.html`.
