# K-pop Quizzes

A Laravel web app for K-pop fans to take personality and knowledge quizzes, play short music and image guessing games, and build a profile around their favourite groups and members.

## Features

- Account registration, login, and email verification
- Quizzes with saved results, quiz history, and collectible titles
- **Guess the Idol**, an image-based member guessing game
- **Guess the Song**, a song clip guessing game
- Member profiles with favourite groups, members, albums, and title showcases
- Admin area for managing groups, members, albums, quizzes, questions, and game media
- Authenticated JSON API for groups, members, albums, quizzes, questions, and answers

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel
- Composer
- Node.js and npm
- A configured MySQL database

## Local setup

```sh
git clone https://github.com/MarijaGG/kpop-quizzes.git
cd kpop-quizzes
composer install
cp .env.example .env
php artisan key:generate
npm ci
```

Configure the MySQL connection in `.env`. The example environment expects a database named `kpopquiz` on `127.0.0.1:3306`; create the database and adjust the credentials for your local MySQL setup.

To create an administrator during seeding, set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`. The seeder does not create a default admin account if these values are absent.

Then migrate, load the sample data, link public media, and build the frontend:

```sh
php artisan migrate --seed
php artisan storage:link
npm run build
```

Run the app locally with:

```sh
composer run dev
```

This starts the Laravel server, queue listener, log viewer, and Vite development server. By default, the site is available at `http://localhost:8000`.

The seeders load quiz and catalogue data from `resources/data/api.json` and game entries from `resources/data/game-demo.json`. Guessing-game entries are only imported when their media files are present on the public storage disk. If files are missing, seeding continues and reports how many entries were skipped.

## Tests

The test suite uses an in-memory mySQL database. Run it with:

```sh
composer test
```
