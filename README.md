# golem-ai/messenger-kit

## golem-ai:messenger-kit:simulator

The ["Finding the correct values for Symfony Messenger failure strategy" article][retry_strategy_article]
is a great resource to understand how the retry strategy configuration is used.

This command simulates consumer failures and prints a timeline of the events.
It lets you check whether your retry strategy configuration does what you expect it to.

![Simulator Command][command_screenshot_url]

The command has a few options:
- Use `--fail-for '3 minutes'` to stop the failures after some time.
- Use `--consumer-duration '6 minutes'` to make the "Consuming message" step take longer.

## Installation

Require the package:

```
composer require golem-ai/messenger-kit
```

Enable the bundle in `config/bundles.php`:
```php
GolemAi\MessengerKit\Bundle\GolemAiMessengerKitBundle::class => ['all' => true],
```

## Golem.ai - Hiring

We are looking for a [Staff Backend Engineer][job_staff_backend] to empower our unique symbolic NLU AI.

[retry_strategy_article]: https://developer.happyr.com/finding-messenger-failture-strategy
[command_screenshot_url]: https://user-images.githubusercontent.com/611271/155994843-f99dbd7e-d261-4187-bbf8-76f2656da9a8.png
[job_staff_backend]: https://www.welcometothejungle.com/en/companies/golem-ai/jobs/staff-backend-engineer_paris