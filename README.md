
# Hermes

Hermes is a Discord bot built with Laracord for logging reactions, and tracking user statistics.

## Features

- Reaction tracking and statistics
- User and guild stats

## Getting Started

1. Install dependencies:
	```bash
	composer install
	```
2. Configure environment:
	- Copy `.env.example` to `.env`
	- Set at minimum:
	  - `DISCORD_TOKEN=your_bot_token_here`
	  - `APP_NAME=Hermes`
	  - `APP_ENV=development`
3. Run migrations:
	```bash
	php laracord migrate
	```
4. Start the bot:
	```bash
	php laracord bot:boot
	```

## Dev

Run CI checks locally:
```bash
composer ci
```
Format with Pint:
```bash
vendor/bin/pint
```

## License

This project is licensed under the MIT License. See LICENSE.md.
