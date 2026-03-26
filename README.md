# Simulat3D — strona firmowa

Zbudowana na **Astro** z **Decap CMS** do zarządzania treścią.

## Szybki start

```bash
npm install
npm run dev
# → http://localhost:4321/simulat3d
```

## Struktura projektu

```
simulat3d/
├── public/
│   ├── admin/
│   │   ├── index.html      ← Panel CMS (Decap)
│   │   └── config.yml      ← Konfiguracja CMS
│   ├── images/             ← Zdjęcia (wgrywane przez CMS)
│   ├── logo-icon.svg
│   └── logo-full.svg
├── src/
│   ├── pages/
│   │   └── index.astro     ← Strona główna
│   └── content/            ← Treści edytowane przez CMS
├── .github/workflows/
│   └── deploy.yml          ← Auto-deploy na GitHub Pages
└── astro.config.mjs
```

## Wdrożenie na GitHub Pages

1. Zmień `TWOJ_USERNAME` w `astro.config.mjs` i `public/admin/config.yml`
2. Utwórz repo `simulat3d` na GitHubie
3. Wrzuć kod: `git push origin main`
4. GitHub → Settings → Pages → Source: **GitHub Actions**
5. Strona dostępna pod: `https://TWOJ_USERNAME.github.io/simulat3d`

## Panel CMS

Po wdrożeniu dostępny pod: `https://TWOJ_USERNAME.github.io/simulat3d/admin`

Wymagane: zalogowanie kontem GitHub które ma dostęp do repozytorium.
